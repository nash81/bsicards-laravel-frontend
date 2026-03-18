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
    @endphp

    <div class="row">
        <div class="col-xl-5">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Card Details') }}</h3>
                </div>
                <div class="site-card-body">
                    <div class="site-alert site-alert-info">
                        <strong>{{ __('Name') }}:</strong> {{ $card->nameoncard ?? '-' }}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>{{ __('Card Number') }}</span><strong>{{ $card->card_number ?? '**** **** **** ****' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>{{ __('Expiry') }}</span><strong>{{ $card->expiry_month ?? '--' }}/{{ $card->expiry_year ?? '--' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>{{ __('CVV') }}</span><strong>{{ $card->cvv ?? '***' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>{{ __('Balance') }}</span><strong>${{ number_format((float) ($card->balance ?? 0), 2) }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>{{ __('Status') }}</span><strong>{{ $status ?: '-' }}</strong></li>
                    </ul>

                    <div class="action-btns mt-3 d-flex flex-wrap gap-2">
                        @if ($isBlocked)
                            <a href="{{ route('user.digitalvisavirtualunblock', $card->cardid ?? '') }}" class="site-btn-sm primary-btn">{{ __('Unblock') }}</a>
                        @else
                            <a href="{{ route('user.digitalvisavirtualblock', $card->cardid ?? '') }}" class="site-btn-sm red-btn">{{ __('Block') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="site-card mt-3">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Fund Card') }}</h3>
                </div>
                <div class="site-card-body">
                    <form action="{{ route('user.digitalvisavirtualloadfunds') }}" method="POST">
                        @csrf
                        <input type="hidden" name="cardid" value="{{ $card->cardid ?? '' }}">
                        <div class="form-group">
                            <label class="form-label">{{ __('Amount') }}</label>
                            <input type="number" min="0.01" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="alert alert-info mt-2 mb-3">
                            <i data-lucide="info"></i>
                            {{ __('Fund loading fee of') }} <strong>{{ number_format((float) $general->bsiload_fee, 2) }}%</strong> {{ __('will be charged.') }}
                        </div>
                        <button type="submit" class="site-btn primary-btn">{{ __('Load Funds') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Transactions') }}</h3>
                </div>
                <div class="site-card-body p-0">
                    <div class="site-custom-table">
                        <div class="contents">
                            <div class="site-table-list site-table-head">
                                <div class="site-table-col">{{ __('Date') }}</div>
                                <div class="site-table-col">{{ __('Type') }}</div>
                                <div class="site-table-col">{{ __('Amount') }}</div>
                                <div class="site-table-col">{{ __('Status') }}</div>
                            </div>
                            @forelse ($transactions as $trx)
                                <div class="site-table-list">
                                    <div class="site-table-col">{{ $trx->createdAt ?? '-' }}</div>
                                    <div class="site-table-col">{{ strtoupper($trx->type ?? '-') }}</div>
                                    <div class="site-table-col">{{ $trx->currency ?? 'USD' }} {{ $trx->amount ?? '0.00' }}</div>
                                    <div class="site-table-col">
                                        <span class="site-badge {{ ($trx->status ?? '') === 'success' ? 'badge-primary' : 'badge-pending' }}">{{ ucfirst($trx->status ?? 'pending') }}</span>
                                    </div>
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
@endsection

