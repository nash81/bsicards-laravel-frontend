<?php

namespace App\Http\Controllers\Api;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\WithdrawalSchedule;
use App\Models\WithdrawAccount;
use App\Models\WithdrawMethod;
use App\Traits\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Txn;

class WithdrawController extends Controller
{
    use Payment;

    /**
     * List all active withdraw methods.
     */
    public function methods(Request $request)
    {
        $methods = WithdrawMethod::where('status', 1)
            ->with('gateway')
            ->get()
            ->map(function (WithdrawMethod $method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'type' => $method->type, // manual / auto
                    'icon' => $method->icon ? asset($method->icon) : null,
                    'currency' => $method->currency,
                    'charge' => (float) $method->charge,
                    'charge_type' => $method->charge_type,
                    'rate' => (float) $method->rate,
                    'min_withdraw' => (float) $method->min_withdraw,
                    'max_withdraw' => (float) $method->max_withdraw,
                    'required_time' => (int) ($method->required_time ?? 0),
                    'required_time_format' => $method->required_time_format,
                    'fields' => $this->decodeMethodFields($method->fields),
                    'gateway_code' => optional($method->gateway)->gateway_code,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $methods,
        ]);
    }

    /**
     * List current user's withdraw accounts.
     */
    public function accounts(Request $request)
    {
        $accounts = WithdrawAccount::with('method')
            ->where('user_id', $request->user()->id)
            ->get()
            ->reject(fn (WithdrawAccount $account) => ! optional($account->method)->status)
            ->values()
            ->map(function (WithdrawAccount $account) {
                return [
                    'id' => $account->id,
                    'method_name' => $account->method_name,
                    'withdraw_method_id' => $account->withdraw_method_id,
                    'credentials' => $this->decodeCredentials($account->credentials),
                    'method' => [
                        'id' => $account->method?->id,
                        'name' => $account->method?->name,
                        'type' => $account->method?->type,
                        'currency' => $account->method?->currency,
                        'charge' => (float) ($account->method?->charge ?? 0),
                        'charge_type' => $account->method?->charge_type,
                        'rate' => (float) ($account->method?->rate ?? 1),
                        'min_withdraw' => (float) ($account->method?->min_withdraw ?? 0),
                        'max_withdraw' => (float) ($account->method?->max_withdraw ?? 0),
                    ],
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Create a withdraw account for user.
     */
    public function storeAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'withdraw_method_id' => 'required|integer|exists:withdraw_methods,id',
            'method_name' => 'nullable|string|max:255',
            'credentials' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $method = WithdrawMethod::where('id', $request->withdraw_method_id)
            ->where('status', 1)
            ->first();

        if (! $method) {
            return response()->json(['status' => false, 'message' => 'Withdraw method not found.'], 404);
        }

        $credentialsInput = $this->extractCredentialsInput($request);
        try {
            $credentials = $this->normalizeCredentials($method, $credentialsInput, [], $request);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
        $validationError = $this->validateRequiredCredentials($credentials);
        if ($validationError) {
            return response()->json(['status' => false, 'message' => $validationError], 422);
        }

        $account = WithdrawAccount::create([
            'user_id' => $request->user()->id,
            'withdraw_method_id' => $method->id,
            'method_name' => $request->method_name ?: $method->name,
            'credentials' => json_encode($credentials),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Withdraw account created successfully.',
            'data' => ['id' => $account->id],
        ]);
    }

    /**
     * Update an existing user withdraw account.
     */
    public function updateAccount(Request $request, int $accountId)
    {
        $account = WithdrawAccount::with('method')
            ->where('user_id', $request->user()->id)
            ->where('id', $accountId)
            ->first();

        if (! $account) {
            return response()->json(['status' => false, 'message' => 'Withdraw account not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'method_name' => 'nullable|string|max:255',
            'credentials' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $credentialsInput = $this->extractCredentialsInput($request);
        try {
            $credentials = $this->normalizeCredentials(
                $account->method,
                $credentialsInput,
                $this->decodeCredentials($account->credentials),
                $request
            );
        } catch (\RuntimeException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }
        $validationError = $this->validateRequiredCredentials($credentials);
        if ($validationError) {
            return response()->json(['status' => false, 'message' => $validationError], 422);
        }

        $account->update([
            'method_name' => $request->method_name ?: $account->method_name,
            'credentials' => json_encode($credentials),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Withdraw account updated successfully.',
        ]);
    }

    /**
     * Delete a withdraw account owned by current user.
     */
    public function deleteAccount(Request $request, int $accountId)
    {
        $account = WithdrawAccount::where('user_id', $request->user()->id)
            ->where('id', $accountId)
            ->first();

        if (! $account) {
            return response()->json(['status' => false, 'message' => 'Withdraw account not found.'], 404);
        }

        $account->delete();

        return response()->json([
            'status' => true,
            'message' => 'Withdraw account deleted successfully.',
        ]);
    }

    /**
     * Return account details and withdraw charge preview.
     */
    public function accountDetails(Request $request, int $accountId)
    {
        $amount = (float) $request->get('amount', 0);

        $withdrawAccount = WithdrawAccount::with('method')
            ->where('user_id', $request->user()->id)
            ->where('id', $accountId)
            ->first();

        if (! $withdrawAccount) {
            return response()->json(['status' => false, 'message' => 'Withdraw account not found.'], 404);
        }

        $method = $withdrawAccount->method;
        $charge = (float) $method->charge;
        if ($method->charge_type !== 'fixed') {
            $charge = ($charge / 100) * $amount;
        }

        return response()->json([
            'status' => true,
            'data' => [
                'account_id' => $withdrawAccount->id,
                'name' => $withdrawAccount->method_name,
                'credentials' => $this->decodeCredentials($withdrawAccount->credentials),
                'charge' => $charge,
                'charge_type' => $method->charge_type,
                'min_withdraw' => (float) $method->min_withdraw,
                'max_withdraw' => (float) $method->max_withdraw,
                'rate' => (float) $method->rate,
                'pay_currency' => $method->currency,
                'processing_time' => (int) $method->required_time > 0
                    ? 'Processing Time: '.$method->required_time.$method->required_time_format
                    : 'This Is Automatic Method',
            ],
        ]);
    }

    /**
     * Initiate a withdrawal request.
     */
    public function initiate(Request $request)
    {
        $user = $request->user();

        if (! setting('user_withdraw', 'permission') || ! $user->withdraw_status) {
            return response()->json(['status' => false, 'message' => 'Withdraw currently unavailable.'], 403);
        }

        if (! setting('kyc_withdraw') && ! $user->kyc) {
            return response()->json(['status' => false, 'message' => 'Please verify your KYC.'], 422);
        }

        $withdrawOffDays = WithdrawalSchedule::where('status', 0)->pluck('name')->toArray();
        $today = Carbon::now()->format('l');
        if (in_array($today, $withdrawOffDays, true)) {
            return response()->json(['status' => false, 'message' => 'Today is the off day of withdraw.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
            'withdraw_account' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $todayTransaction = Transaction::whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])
            ->whereDate('created_at', Carbon::today())
            ->count();
        $dayLimit = (float) setting('withdraw_day_limit', 'fee');
        if ($dayLimit > 0 && $todayTransaction >= $dayLimit) {
            return response()->json(['status' => false, 'message' => 'Today withdraw limit has been reached.'], 422);
        }

        $amount = (float) $request->amount;

        $withdrawAccount = WithdrawAccount::with('method.gateway')
            ->where('user_id', $user->id)
            ->where('id', (int) $request->withdraw_account)
            ->first();

        if (! $withdrawAccount || ! $withdrawAccount->method || ! $withdrawAccount->method->status) {
            return response()->json(['status' => false, 'message' => 'Withdraw account not found.'], 404);
        }

        $withdrawMethod = $withdrawAccount->method;

        if ($amount < (float) $withdrawMethod->min_withdraw || $amount > (float) $withdrawMethod->max_withdraw) {
            $symbol = setting('currency_symbol', 'global');
            return response()->json([
                'status' => false,
                'message' => 'Please withdraw the amount within the range '.$symbol.$withdrawMethod->min_withdraw.' to '.$symbol.$withdrawMethod->max_withdraw,
            ], 422);
        }

        $charge = $withdrawMethod->charge_type === 'percentage'
            ? ((float) $withdrawMethod->charge / 100) * $amount
            : (float) $withdrawMethod->charge;
        $totalAmount = $amount + $charge;

        if ((float) $user->balance < $totalAmount) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $payAmount = $amount * (float) $withdrawMethod->rate;
        $type = $withdrawMethod->type === 'auto' ? TxnType::WithdrawAuto : TxnType::Withdraw;

        DB::beginTransaction();
        try {
            $user->decrement('balance', $totalAmount);

            $txnInfo = Txn::new(
                $amount,
                $charge,
                $totalAmount,
                $withdrawMethod->name,
                'Withdraw With '.$withdrawAccount->method_name,
                $type,
                TxnStatus::Pending,
                $withdrawMethod->currency,
                $payAmount,
                $user->id,
                null,
                'User',
                $this->decodeCredentials($withdrawAccount->credentials)
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to initiate withdraw.'], 500);
        }

        if ($withdrawMethod->type === 'auto' && optional($withdrawMethod->gateway)->gateway_code) {
            try {
                // Keep parity with web flow: trigger auto payout handling.
                $this->withdrawAutoGateway($withdrawMethod->gateway->gateway_code, $txnInfo);
            } catch (\Throwable $e) {
                // Leave transaction pending for admin/manual review when provider call fails.
                \Log::error('Withdraw auto gateway call failed', [
                    'gateway_code' => $withdrawMethod->gateway->gateway_code,
                    'tnx' => $txnInfo->tnx,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'type' => $withdrawMethod->type,
            'tnx' => $txnInfo->tnx,
            'amount' => $amount,
            'charge' => $charge,
            'final_amount' => $totalAmount,
            'pay_amount' => $payAmount,
            'currency' => $withdrawMethod->currency,
            'message' => $withdrawMethod->type === 'manual'
                ? 'Withdraw request submitted and pending review.'
                : 'Withdraw request submitted successfully.',
        ]);
    }

    /**
     * Check withdrawal status by transaction reference.
     */
    public function status(Request $request, string $tnx)
    {
        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('tnx', $tnx)
            ->whereIn('type', [TxnType::Withdraw, TxnType::WithdrawAuto])
            ->first();

        if (! $transaction) {
            return response()->json(['status' => false, 'message' => 'Withdrawal transaction not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'tnx' => $transaction->tnx,
            'amount' => (float) $transaction->amount,
            'final_amount' => (float) $transaction->final_amount,
            'method' => $transaction->method,
            'txn_status' => $transaction->status instanceof \BackedEnum
                ? $transaction->status->value
                : $transaction->status,
            'created_at' => $transaction->attributes['created_at'] ?? $transaction->created_at,
        ]);
    }

    private function decodeMethodFields($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function decodeCredentials($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function normalizeCredentials(
        WithdrawMethod $method,
        array $credentialsInput,
        array $oldCredentials = [],
        ?Request $request = null
    ): array
    {
        $fields = $this->decodeMethodFields($method->fields);
        $normalized = [];
        $uploadedFiles = $request?->file('credential_files', []);

        foreach ($fields as $index => $field) {
            $name = (string) ($field['name'] ?? ('field_'.$index));
            $validation = (string) ($field['validation'] ?? 'nullable');
            $type = (string) ($field['type'] ?? 'text');

            $inputValue = null;
            if ($inputValue === null && array_key_exists($name, $credentialsInput)) {
                $inputValue = is_array($credentialsInput[$name])
                    ? ($credentialsInput[$name]['value'] ?? null)
                    : $credentialsInput[$name];
            }
            if ($inputValue === null) {
                $inputValue = $this->findValueByFieldNameFromList($credentialsInput, $name);
            }
            if ($inputValue === null && isset($credentialsInput[$index]) && is_array($credentialsInput[$index])) {
                $inputValue = $credentialsInput[$index]['value'] ?? null;
            }
            if ($inputValue === null && isset($oldCredentials[$index]['value'])) {
                $inputValue = $oldCredentials[$index]['value'];
            }
            if ($inputValue === null && isset($oldCredentials[$name]['value'])) {
                $inputValue = $oldCredentials[$name]['value'];
            }

            if (strtolower($type) === 'file' && is_array($uploadedFiles) && isset($uploadedFiles[$name]) && $uploadedFiles[$name] instanceof UploadedFile) {
                $inputValue = $this->uploadCredentialFile($uploadedFiles[$name]);
            }

            $normalized[$name] = [
                'type' => $type,
                'validation' => $validation,
                'value' => is_scalar($inputValue) || $inputValue === null ? $inputValue : json_encode($inputValue),
            ];
        }

        if (empty($normalized)) {
            foreach ($credentialsInput as $key => $value) {
                if (is_array($value) && array_key_exists('name', $value)) {
                    $fieldName = (string) $value['name'];
                    $normalized[$fieldName] = [
                        'type' => (string) ($value['type'] ?? 'text'),
                        'validation' => (string) ($value['validation'] ?? 'nullable'),
                        'value' => $value['value'] ?? null,
                    ];
                    continue;
                }

                $fieldName = is_string($key) ? $key : 'field_'.$key;
                $normalized[$fieldName] = [
                    'type' => 'text',
                    'validation' => 'nullable',
                    'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
                ];
            }
        }

        return $this->canonicalizeCredentials($normalized);
    }

    private function findValueByFieldNameFromList(array $credentialsInput, string $fieldName)
    {
        foreach ($credentialsInput as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['name'] ?? null) === $fieldName) {
                return $item['value'] ?? null;
            }
        }

        return null;
    }

    private function canonicalizeCredentials(array $credentials): array
    {
        $normalized = [];

        foreach ($credentials as $key => $field) {
            if (! is_array($field)) {
                continue;
            }

            $fieldName = (string) ($field['name'] ?? (is_string($key) ? $key : 'field_'.$key));
            if ($fieldName === '') {
                continue;
            }

            $normalized[$fieldName] = [
                'type' => (string) ($field['type'] ?? 'text'),
                'validation' => (string) ($field['validation'] ?? 'nullable'),
                'value' => $field['value'] ?? null,
            ];
        }

        return $normalized;
    }

    private function extractCredentialsInput(Request $request): array
    {
        $credentials = $request->input('credentials');

        if (is_array($credentials)) {
            return $credentials;
        }

        if (is_string($credentials) && trim($credentials) !== '') {
            $decoded = json_decode($credentials, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function uploadCredentialFile(UploadedFile $file): string
    {
        $allowed = ['jpeg', 'jpg', 'png', 'gif', 'svg', 'pdf'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, $allowed, true)) {
            throw new \RuntimeException('Invalid file type for credential upload.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \RuntimeException('Credential file exceeds max size of 5MB.');
        }

        $name = Str::random(20).'.'.$ext;
        $destination = public_path('assets/global/images');
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        $file->move($destination, $name);

        return 'global/images/'.$name;
    }

    private function validateRequiredCredentials(array $credentials): ?string
    {
        foreach ($credentials as $name => $field) {
            $isRequired = strtolower((string) ($field['validation'] ?? 'nullable')) === 'required';
            $value = trim((string) ($field['value'] ?? ''));
            if ($isRequired && $value === '') {
                return (is_string($name) ? $name : 'A required field').' is required.';
            }
        }

        return null;
    }
}

