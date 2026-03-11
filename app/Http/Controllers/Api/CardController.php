<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Models\Transaction;
use App\Enums\TxnStatus;
use App\Enums\TxnType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Txn;

class CardController extends Controller
{
    // -------------------------------------------------------
    // Shared helper – BSI API call
    // -------------------------------------------------------
    private function bsiCall(string $endpoint, array $body, GeneralSetting $general): ?object
    {
        $curl = curl_init();
        $data = json_encode($body);
        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://cards.bsigroup.tech/api/' . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_HTTPHEADER     => [
                'publickey: ' . $general->bsi_publickey,
                'secretkey: ' . $general->bsi_secretkey,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    // =======================================================
    // MASTERCARD
    // =======================================================

    public function masterList(Request $request)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $cards   = $this->bsiCall('getallcard', ['useremail' => $user->email], $general);
        $pending = $this->bsiCall('getpendingcards', ['useremail' => $user->email], $general);

        return response()->json([
            'status'  => true,
            'cards'   => isset($cards->code) && $cards->code == 200 ? $cards->data ?? [] : [],
            'pending' => isset($pending->code) && $pending->code == 200 ? $pending->data ?? [] : [],
        ]);
    }

    public function masterView(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $card  = $this->bsiCall('getcard', ['useremail' => $user->email, 'cardid' => $cardId], $general);
        $trans = $this->bsiCall('getcardtransactions', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (! isset($card->code) || $card->code != 200) {
            return response()->json(['status' => false, 'message' => 'Card not found.'], 404);
        }

        return response()->json([
            'status'       => true,
            'card'         => $card->data ?? $card,
            'transactions' => $trans->data ?? [],
        ]);
    }

    public function masterLoadFunds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cardid' => 'required|string',
            'amount' => 'required|numeric|min:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $fee     = round($request->amount * $general->bsiload_fee / 100, 2);
        $total   = $request->amount + $fee;

        if ($user->balance < $total) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $user->balance -= $total;
        $user->save();

        $result = $this->bsiCall('fundcard', [
            'useremail' => $user->email,
            'cardid'    => $request->cardid,
            'amount'    => $request->amount,
        ], $general);

        if (isset($result->code) && $result->code == 200) {
            Txn::new($total, $fee, $total, 'BSICards', 'MasterCard Loaded for ' . $user->email, TxnType::Subtract, TxnStatus::Success, null, null, $user->id);
            return response()->json(['status' => true, 'message' => 'Funds loaded successfully.']);
        }

        $user->balance += $total;
        $user->save();
        return response()->json(['status' => false, 'message' => 'Failed to load funds. Please try again.'], 500);
    }

    public function masterBlock(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('blockcard', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (isset($result->code) && $result->code == 200) {
            return response()->json(['status' => true, 'message' => 'Card blocked successfully.']);
        }
        return response()->json(['status' => false, 'message' => 'Failed to block card.'], 500);
    }

    public function masterUnblock(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('unblockcard', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (isset($result->code) && $result->code == 200) {
            return response()->json(['status' => true, 'message' => 'Card unblock requested.']);
        }
        return response()->json(['status' => false, 'message' => 'Failed to unblock card.'], 500);
    }

    // =======================================================
    // VISA CARD
    // =======================================================

    public function visaList(Request $request)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $cards   = $this->bsiCall('getallvisacard', ['useremail' => $user->email], $general);
        $pending = $this->bsiCall('getvisapendingcards', ['useremail' => $user->email], $general);

        return response()->json([
            'status'  => true,
            'cards'   => isset($cards->code) && $cards->code == 200 ? $cards->data ?? [] : [],
            'pending' => isset($pending->code) && $pending->code == 200 ? $pending->data ?? [] : [],
        ]);
    }

    public function visaView(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $card  = $this->bsiCall('getvisacard', ['useremail' => $user->email, 'cardid' => $cardId], $general);
        $trans = $this->bsiCall('getvisacardtransactions', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (! isset($card->code) || $card->code != 200) {
            return response()->json(['status' => false, 'message' => 'Card not found.'], 404);
        }

        return response()->json([
            'status'       => true,
            'card'         => $card->data ?? $card,
            'transactions' => $trans->data ?? [],
        ]);
    }

    public function visaLoadFunds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cardid' => 'required|string',
            'amount' => 'required|numeric|min:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $fee     = round($request->amount * $general->usbvisa_loadfee / 100, 2);
        $total   = $request->amount + $fee;

        if ($user->balance < $total) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $user->balance -= $total;
        $user->save();

        $result = $this->bsiCall('visafundcard', [
            'useremail' => $user->email,
            'cardid'    => $request->cardid,
            'amount'    => $request->amount,
        ], $general);

        if (isset($result->code) && $result->code == 200) {
            Txn::new($total, $fee, $total, 'BSICards', 'VisaCard Loaded for ' . $user->email, TxnType::Subtract, TxnStatus::Success, null, null, $user->id);
            return response()->json(['status' => true, 'message' => 'Visa card funded successfully.']);
        }

        $user->balance += $total;
        $user->save();
        return response()->json(['status' => false, 'message' => 'Failed to load funds.'], 500);
    }

    public function visaBlock(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('visablockcard', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (isset($result->code) && $result->code == 200) {
            return response()->json(['status' => true, 'message' => 'Visa card blocked.']);
        }
        return response()->json(['status' => false, 'message' => 'Failed to block card.'], 500);
    }

    // =======================================================
    // DIGITAL MASTERCARD
    // =======================================================

    public function digitalList(Request $request)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $cards = $this->bsiCall('getalldigital', ['useremail' => $user->email], $general);

        return response()->json([
            'status' => true,
            'cards'  => isset($cards->code) && $cards->code == 200 ? $cards->data ?? [] : [],
        ]);
    }

    public function digitalView(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();

        $card    = $this->bsiCall('getdigitalcard', ['useremail' => $user->email, 'cardid' => $cardId], $general);
        $check3ds= $this->bsiCall('check3ds',       ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (! isset($card->code) || $card->code != 200) {
            return response()->json(['status' => false, 'message' => 'Card not found.'], 404);
        }

        return response()->json([
            'status'   => true,
            'card'     => $card->data ?? $card,
            'check3ds' => $check3ds->data ?? null,
        ]);
    }

    public function digitalLoadFunds(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cardid' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $fee     = round($request->amount * ($general->digital_loadfee ?? 0) / 100, 2);
        $total   = $request->amount + $fee;

        if ($user->balance < $total) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance.'], 422);
        }

        $user->balance -= $total;
        $user->save();

        $result = $this->bsiCall('digitalfundcard', [
            'useremail' => $user->email,
            'cardid'    => $request->cardid,
            'amount'    => $request->amount,
        ], $general);

        if (isset($result->code) && $result->code == 200) {
            Txn::new($total, $fee, $total, 'BSICards', 'Digital Mastercard Loaded for ' . $user->email, TxnType::Subtract, TxnStatus::Success, null, null, $user->id);
            return response()->json(['status' => true, 'message' => 'Digital card funded successfully.']);
        }

        $user->balance += $total;
        $user->save();
        return response()->json(['status' => false, 'message' => 'Failed to load funds.'], 500);
    }

    public function digitalBlock(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('blockdigital', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        if (isset($result->code) && $result->code == 200) {
            return response()->json(['status' => true, 'message' => 'Digital card blocked.']);
        }
        return response()->json(['status' => false, 'message' => 'Failed to block card.'], 500);
    }

    public function digitalApply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:100',
            'lastname'  => 'required|string|max:100',
            'address'   => 'required|string',
            'city'      => 'required|string',
            'state'     => 'required|string',
            'country'   => 'required|string',
            'zip'       => 'required|string',
            'dob'       => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $fee     = $general->digifee ?? 4.50;

        if ($user->balance < $fee) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance. Fee required: $' . $fee], 422);
        }

        $user->balance -= $fee;
        $user->save();

        $body = array_merge($request->only(['firstname','lastname','address','city','state','country','zip','dob']), [
            'useremail' => $user->email,
        ]);
        $result = $this->bsiCall('newdigitalcard', $body, $general);

        if (isset($result->code) && $result->code == 200) {
            Txn::new($fee, 0, $fee, 'BSICards', 'New Virtual Digital MasterCard Issuance For ' . $user->email, TxnType::Subtract, TxnStatus::Success, null, null, $user->id);
            return response()->json(['status' => true, 'message' => 'Digital card application submitted.', 'data' => $result->data ?? null]);
        }

        $user->balance += $fee;
        $user->save();
        return response()->json(['status' => false, 'message' => $result->message ?? 'Failed to apply for card.'], 500);
    }

    public function digitalAddon(Request $request)
    {
        $validator = Validator::make($request->all(), ['cardid' => 'required|string']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $fee     = $general->digifee ?? 4.50;

        if ($user->balance < $fee) {
            return response()->json(['status' => false, 'message' => 'Insufficient balance. Fee required: $' . $fee], 422);
        }

        $user->balance -= $fee;
        $user->save();

        $result = $this->bsiCall('createaddon', [
            'useremail' => $user->email,
            'cardid'    => $request->cardid,
        ], $general);

        if (isset($result->code) && $result->code == 200) {
            Txn::new($fee, 0, $fee, 'BSICards', 'New Virtual Digital MasterCard Addon Issuance For ' . $user->email, TxnType::Subtract, TxnStatus::Success, null, null, $user->id);
            return response()->json(['status' => true, 'message' => 'Addon card applied successfully.', 'data' => $result->data ?? null]);
        }

        $user->balance += $fee;
        $user->save();
        return response()->json(['status' => false, 'message' => $result->message ?? 'Failed to apply addon card.'], 500);
    }

    /**
     * Check pending 3DS transaction for a digital card
     */
    public function check3ds(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('check3ds', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        return response()->json([
            'status' => true,
            'data'   => $result->data ?? null,
        ]);
    }

    /**
     * Check Google/Apple Pay wallet OTP
     */
    public function checkWalletOtp(Request $request, string $cardId)
    {
        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('checkwallet', ['useremail' => $user->email, 'cardid' => $cardId], $general);

        return response()->json([
            'status' => true,
            'data'   => $result->data ?? null,
        ]);
    }

    /**
     * Approve a pending 3DS transaction
     */
    public function approve3ds(Request $request, string $cardId)
    {
        $validator = Validator::make($request->all(), ['eventid' => 'required|string']);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user    = $request->user();
        $general = GeneralSetting::first();
        $result  = $this->bsiCall('approve3ds', [
            'useremail' => $user->email,
            'cardid'    => $cardId,
            'eventid'   => $request->eventid,
        ], $general);

        if (isset($result->code) && $result->code == 200) {
            return response()->json(['status' => true, 'message' => '3DS approved successfully.']);
        }
        return response()->json(['status' => false, 'message' => '3DS approval failed.'], 500);
    }
}

