<?php

namespace App\Http\Controllers\Api;

use App\Enums\TxnStatus;
use App\Enums\TxnType;
use App\Enums\GatewayType;
use App\Http\Controllers\Controller;
use App\Models\DepositMethod;
use App\Models\Transaction;
use App\Services\MoncashService;
use App\Traits\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Txn;

class DepositController extends Controller
{
    use Payment;

    /**
     * List all active deposit gateways
     */
    public function gateways(Request $request)
    {
        $gateways = DepositMethod::where('status', 1)
            ->get()
            ->map(fn($g) => [
                'id'              => $g->id,
                'name'            => $g->name,
                'gateway_code'    => $g->gateway_code,
                'logo'            => $g->gateway_logo,
                'currency'        => $g->currency,
                'minimum_deposit' => (float) $g->minimum_deposit,
                'maximum_deposit' => (float) $g->maximum_deposit,
                'charge'          => (float) $g->charge,
                'charge_type'     => $g->charge_type,
                'rate'            => (float) $g->rate,
                'type'            => $g->type,   // auto / manual
            ]);

        return response()->json([
            'status' => true,
            'data'   => $gateways,
        ]);
    }

    /**
     * Initiate a deposit.
     *
     * For automatic gateways (e.g. MonCash, Stripe, PayPal) this returns
     * a `redirect_url` which the Flutter app should open in an in-app WebView
     * or external browser. The IPN/webhook at the server side will confirm the
     * payment and update the transaction status.
     *
     * For manual gateways it returns payment instructions and a transaction
     * reference so the user can upload proof later.
     */
    public function initiate(Request $request)
    {
        $user = $request->user();

        if (! setting('user_deposit', 'permission') || ! $user->deposit_status) {
            return response()->json(['status' => false, 'message' => 'Deposits are currently unavailable.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'gateway_code' => 'required|string',
            'amount'       => ['required', 'numeric', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $gatewayInfo = DepositMethod::code($request->gateway_code)->first();
        if (! $gatewayInfo) {
            return response()->json(['status' => false, 'message' => 'Gateway not found.'], 404);
        }

        $amount = (float) $request->amount;
        if ($amount < $gatewayInfo->minimum_deposit || $amount > $gatewayInfo->maximum_deposit) {
            $sym     = setting('currency_symbol', 'global');
            return response()->json([
                'status'  => false,
                'message' => "Amount must be between {$sym}{$gatewayInfo->minimum_deposit} and {$sym}{$gatewayInfo->maximum_deposit}.",
            ], 422);
        }

        $charge      = $gatewayInfo->charge_type === 'percentage'
            ? ($gatewayInfo->charge / 100) * $amount
            : (float) $gatewayInfo->charge;
        $finalAmount = $amount + $charge;
        $payAmount   = $finalAmount * $gatewayInfo->rate;

        $txnInfo = Txn::new(
            $amount, $charge, $finalAmount,
            $gatewayInfo->gateway_code,
            'Deposit With ' . $gatewayInfo->name,
            TxnType::Deposit,
            TxnStatus::Pending,
            $gatewayInfo->currency,
            $payAmount,
            $user->id
        );

        // Manual gateway – return payment details
        $gatewayCode = $gatewayInfo->gateway->gateway_code ?? $request->gateway_code;
        if ($gatewayInfo->type === GatewayType::Manual->value) {
            return response()->json([
                'status'          => true,
                'type'            => 'manual',
                'tnx'             => $txnInfo->tnx,
                'amount'          => $amount,
                'charge'          => $charge,
                'final_amount'    => $finalAmount,
                'pay_amount'      => $payAmount,
                'currency'        => $gatewayInfo->currency,
                'payment_details' => $gatewayInfo->payment_details ?? null,
                'field_options'   => $gatewayInfo->field_options ?? null,
                'message'         => 'Submit payment proof to complete your deposit.',
            ]);
        }

        // Automatic gateway – obtain redirect URL
        $redirectUrl = $this->getAutoGatewayUrl($gatewayCode, $txnInfo, $request->get('return_url'));

        if ($redirectUrl) {
            return response()->json([
                'status'       => true,
                'type'         => 'auto',
                'tnx'          => $txnInfo->tnx,
                'redirect_url' => $redirectUrl,
                'amount'       => $amount,
                'currency'     => $gatewayInfo->currency,
                'message'      => 'Open the redirect_url in a WebView to complete payment.',
            ]);
        }

        // Pending (e.g. crypto waiting for confirmations)
        return response()->json([
            'status'   => true,
            'type'     => 'pending',
            'tnx'      => $txnInfo->tnx,
            'amount'   => $amount,
            'currency' => $gatewayInfo->currency,
            'message'  => 'Your deposit is pending confirmation.',
        ]);
    }

    /**
     * Check the current status of a deposit by transaction reference
     */
    public function status(Request $request, string $tnx)
    {
        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('tnx', $tnx)
            ->firstOrFail();

        return response()->json([
            'status'     => true,
            'tnx'        => $transaction->tnx,
            'amount'     => (float) $transaction->amount,
            'final_amount' => (float) $transaction->final_amount,
            'method'     => $transaction->method,
            'txn_status' => $transaction->status instanceof \BackedEnum
                ? $transaction->status->value
                : $transaction->status,
            'created_at' => $transaction->attributes['created_at'],
        ]);
    }

    /**
     * Submit manual deposit proof (file upload or field data)
     */
    public function submitManualProof(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tnx'   => 'required|string',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $transaction = Transaction::where('user_id', $request->user()->id)
            ->where('tnx', $request->tnx)
            ->whereIn('type', [TxnType::ManualDeposit, TxnType::Deposit])
            ->firstOrFail();

        $manualData = [];
        if ($request->hasFile('proof')) {
            $path = $request->file('proof')->store('manual_deposits', 'public');
            $manualData['proof'] = $path;
        }
        if ($request->filled('manual_fields')) {
            $manualData = array_merge($manualData, $request->get('manual_fields', []));
        }

        $transaction->update([
            'manual_field_data' => json_encode($manualData),
            'type'              => TxnType::ManualDeposit,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Proof submitted. Your deposit will be reviewed shortly.',
        ]);
    }

    // -------------------------------------------------------
    // Private: get redirect URL from automatic gateway
    // -------------------------------------------------------
    private function getAutoGatewayUrl($gatewayCode, $txnInfo, ?string $returnUrl = null): ?string
    {
        // MonCash – most common in this project
        if ($gatewayCode === 'moncash') {
            try {
                $client  = new MoncashService();
                $payload = [
                    'amount'      => (float) number_format($txnInfo->amount, 2, '.', ''),
                    'orderId'     => $txnInfo->tnx,
                    'currency'    => $txnInfo->pay_currency ?: 'HTG',
                    'redirectUrl' => route('ipn.moncash', ['reftrn' => $txnInfo->tnx]),
                ];
                $response    = $client->createPayment($payload);
                $checkoutUrl = $client->extractCheckoutUrl($response);

                if ($checkoutUrl) {
                    $providerRef = $client->extractProviderReference($response);
                    if ($providerRef) {
                        Transaction::tnx($txnInfo->tnx)?->update(['approval_cause' => $providerRef]);
                    }
                    return $checkoutUrl;
                }
            } catch (\Throwable $e) {
                \Log::error('API MonCash init error: ' . $e->getMessage());
            }
            return null;
        }

        // For other gateways: call the existing depositAutoGateway which returns a redirect response.
        // We capture the URL from the redirect.
        try {
            Session::put('deposit_tnx', $txnInfo->tnx);
            $result = self::depositAutoGateway($gatewayCode, $txnInfo);

            // If it's a RedirectResponse, extract the URL
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return $result->getTargetUrl();
            }
        } catch (\Throwable $e) {
            \Log::error('API gateway init error [' . $gatewayCode . ']: ' . $e->getMessage());
        }

        return null;
    }
}


