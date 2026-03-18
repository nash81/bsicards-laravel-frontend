@extends('frontend::layouts.user')
@section('title')
    {{ __('Digital Visa Card') }}
@endsection
@section('content')
    @php
        $card = $virtualcards->data ?? null;
        $transactions = $card->transactions->data ?? [];
        $status = strtoupper((string) ($card->status ?? ''));
        $isBlocked = in_array($status, ['BLOCKED', 'FROZEN', 'INACTIVE'], true);
        $billingAddress = trim(implode(', ', array_filter([
            data_get($card, 'billing_address.billing_address1'),
            data_get($card, 'billing_address.billing_city'),
            data_get($card, 'billing_address.state'),
            data_get($card, 'billing_address.billing_country'),
            data_get($card, 'billing_address.billing_zip_code'),
            data_get($card, 'address1'),
            data_get($card, 'city'),
            data_get($card, 'state'),
            data_get($card, 'country'),
            data_get($card, 'postalCode'),
        ])));
    @endphp

    <div class="row">
        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Digital Visa Card') }}</h3>
                </div>
                <div class="site-card-body">
                    <div class="row g-4 align-items-start">
                        <div class="col-lg-4 col-xl-4">
                            <div class="virtual-bank-card visa-theme">
                                <div class="card-top">
                                    <span class="chip"></span>
                                    <span class="brand">VISA DIGITAL</span>
                                </div>
                                <div class="card-number">{{ preg_replace('/(\d{4})(?=\d)/', '$1 ', $card->card_number ?? '') }}</div>
                                <div class="card-bottom">
                                    <div>
                                        <div class="label">{{ __('Card Holder') }}</div>
                                        <div class="value">{{ $card->nameoncard ?? (($user->first_name ?? '').' '.($user->last_name ?? '')) }}</div>
                                    </div>
                                    <div>
                                        <div class="label">{{ __('Expires') }}</div>
                                        <div class="value">{{ $card->expiry_month ?? '--' }}/{{ $card->expiry_year ?? '--' }}</div>
                                    </div>
                                    <div>
                                        <div class="label">CVV</div>
                                        <div class="value">{{ $card->cvv ?? '***' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 col-xl-8">
                            <div class="card-info-grid">
                                <div class="info-block">
                                    <div class="k">{{ __('Balance (USD)') }}</div>
                                    <div class="v">${{ number_format((float) ($card->balance ?? 0), 2) }}</div>
                                </div>
                                <div class="info-block">
                                    <div class="k">{{ __('Network') }}</div>
                                    <div class="v">{{ ucfirst((string) data_get($card, 'brand', 'Visa')) }}</div>
                                </div>
                                <div class="info-block full">
                                    <div class="k">{{ __('Billing Address') }}</div>
                                    <div class="v small">{{ $billingAddress !== '' ? $billingAddress : '-' }}</div>
                                </div>
                                <div class="info-block">
                                    <div class="k">{{ __('Status') }}</div>
                                    <div class="v">
                                        @if ($isBlocked)
                                            <span class="site-badge badge-failed">{{ __('Blocked') }}</span>
                                        @else
                                            <span class="site-badge badge-success">{{ __('Active') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="info-block">
                                    <div id="otpDiv">
                                        <div class="k">{{ __('OTP Wallet') }}</div>
                                        <div class="text-muted">{{ __('Waiting for OTP code...') }}</div>
                                    </div>
                                </div>
                                <div class="info-actions full">
                                    <button type="button" class="site-btn-sm primary-btn" data-bs-toggle="modal" data-bs-target="#loadfunds">
                                        <i data-lucide="wallet"></i>{{ __('Load Funds') }}
                                    </button>
                                    @if ($isBlocked)
                                        <a href="{{ route('user.digitalvisavirtualunblock', $card->cardid ?? '') }}" class="site-btn-sm primary-btn">{{ __('Unblock') }}</a>
                                    @else
                                        <a href="{{ route('user.digitalvisavirtualblock', $card->cardid ?? '') }}" class="site-btn-sm red-btn" onclick="return confirm('{{ __('Are you sure you want to block the card?') }}')">{{ __('Block') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Card Transaction History') }}</h3>
                </div>
                <div class="site-card-body p-0">
                    <div class="site-custom-table">
                        <div class="contents">
                            <div class="site-table-list site-table-head">
                                <div class="site-table-col">{{ __('Date') }}</div>
                                <div class="site-table-col">{{ __('Type') }}</div>
                                <div class="site-table-col">{{ __('Merchant') }}</div>
                                <div class="site-table-col">{{ __('Amount') }}</div>
                            </div>
                            @forelse ($transactions as $trx)
                                @php
                                    $trxDateRaw = data_get($trx, 'date', data_get($trx, 'createdAt', data_get($trx, 'paymentDateTime')));
                                    $trxDate = $trxDateRaw ? \Carbon\Carbon::parse($trxDateRaw)->format('d M Y, h:i A') : '-';

                                    $direction = strtolower((string) data_get($trx, 'direction', ''));
                                    $trxType = $direction === 'incoming' ? __('Deposit') : __('Charge');

                                    $merchantValue = trim((string) data_get($trx, 'merchant', ''));
                                    $descriptionValue = trim((string) data_get($trx, 'description', '-'));
                                    $merchantDisplay = $merchantValue !== '' ? $merchantValue : ($descriptionValue !== '' ? $descriptionValue : '-');

                                    $amount = (float) data_get($trx, 'amount', 0);
                                    $statusRaw = strtoupper((string) data_get($trx, 'status', 'PENDING'));
                                    $statusClass = $statusRaw === 'DECLINE' ? 'badge-danger' : ($statusRaw === 'PENDING' ? 'badge-pending' : 'badge-primary');
                                @endphp
                                <div class="site-table-list">
                                    <div class="site-table-col">
                                        {{ $trxDate }}
                                        <div class="mt-1">
                                            <span class="site-badge {{ $statusClass }}">{{ $statusRaw }}</span>
                                        </div>
                                    </div>
                                    <div class="site-table-col">{{ $trxType }}</div>
                                    <div class="site-table-col">{{ $merchantDisplay }}</div>
                                    <div class="site-table-col">${{ number_format($amount, 2) }}</div>
                                </div>
                            @empty
                                <div class="no-data-found">{{ __('No Transactions Found') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loadfunds" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content site-table-modal">
                <div class="modal-body popup-body">
                    <button type="button" class="modal-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i data-lucide="x"></i>
                    </button>
                    <div class="popup-body-text">
                        <div class="title">{{ __('Load Funds') }}</div>
                        <p class="text-muted mb-3">{{ __('Load funds to your card') }}</p>

                        <form action="{{ route('user.digitalvisavirtualloadfunds') }}" method="POST">
                            @csrf
                            <input type="hidden" name="cardid" value="{{ $card->cardid ?? '' }}">
                            <div class="form-group">
                                <label class="form-label">{{ __('Enter Amount') }} (USD)</label>
                                <input type="number" step="0.01" min="5.01" name="amount" class="form-control" required>
                            </div>

                            <div class="alert alert-info mt-2 mb-3">
                                <i data-lucide="info"></i>
                                {{ __('Fund loading fee of') }} <strong>{{ number_format((float) $general->bsiload_fee, 2) }}%</strong> {{ __('will be charged.') }}
                            </div>

                            <div class="action-btns">
                                <button type="submit" class="site-btn-sm primary-btn">{{ __('Submit') }}</button>
                                <button type="button" class="site-btn-sm red-btn" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="text-danger mb-0">{{ __('Minimum amount must be greater than $5.00.') }}</p>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($card->cardid))
        @push('js')
            <script>
                (function () {
                    function checkOtpWallet() {
                        $.ajax({
                            url: '{{ route('user.digitalvisacheckotp', $card->cardid) }}',
                            type: 'GET',
                            success: function (response) {
                                $('#otpDiv').html(response);
                            },
                            error: function () {
                                $('#otpDiv').html('<div class="k">{{ __('OTP Wallet') }}</div><div class="text-muted">{{ __('Waiting for OTP code...') }}</div>');
                            }
                        });
                    }

                    checkOtpWallet();
                    setInterval(checkOtpWallet, 30000);
                })();
            </script>
        @endpush
    @endif
@endsection

