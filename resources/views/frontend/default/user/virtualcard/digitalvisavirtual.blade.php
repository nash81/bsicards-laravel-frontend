@extends('frontend::layouts.user')
@section('title')
    {{ __('Digital Visa Cards') }}
@endsection
@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Create New Digital Visa Card') }}</h3>
                </div>
                <div class="site-card-body">
                    <form action="{{ route('user.digitalvisavirtualnew') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('First Name') }}</label>
                                    <input type="text" class="form-control" value="{{ $user->first_name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Last Name') }}</label>
                                    <input type="text" class="form-control" value="{{ $user->last_name }}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i data-lucide="info"></i>
                            {{ __('A new card fee of') }} <strong>${{ number_format((float) $general->bsiissue_fee, 2) }}</strong> {{ __('plus a minimum load balance ($5 + :fee%) will be charged from your wallet.', ['fee' => number_format((float) $general->bsiload_fee, 2)]) }}
                        </div>

                        <div class="action-btns mt-3">
                            <button type="submit" class="site-btn primary-btn">
                                <i data-lucide="credit-card"></i> {{ __('New Card') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Your Issued Digital Visa Cards') }}</h3>
                </div>
                <div class="site-card-body p-0">
                    <div class="site-custom-table">
                        <div class="contents">
                            <div class="site-table-list site-table-head">
                                <div class="site-table-col">{{ __('Card ID') }}</div>
                                <div class="site-table-col">{{ __('Name on Card') }}</div>
                                <div class="site-table-col">{{ __('Last 4 Digits') }}</div>
                                <div class="site-table-col">{{ __('Action') }}</div>
                            </div>
                            @forelse (($virtualcards->data ?? []) as $item)
                                <div class="site-table-list">
                                    <div class="site-table-col">
                                        <div class="description">
                                            <div class="event-icon">
                                                <i data-lucide="credit-card"></i>
                                            </div>
                                            <div class="content">
                                                <div class="title">{{ $item->cardid ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="site-table-col">
                                        <div class="trx">{{ $item->nameoncard ?? '-' }}</div>
                                    </div>
                                    <div class="site-table-col">
                                        <span class="site-badge badge-primary">**** {{ $item->lastfour ?? '' }}</span>
                                    </div>
                                    <div class="site-table-col">
                                        <div class="action">
                                            <a href="{{ route('user.digitalvisavirtualview', $item->cardid) }}" class="icon-btn">
                                                <i data-lucide="eye"></i>{{ __('View Card') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="no-data-found">{{ __('No Cards Found') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

