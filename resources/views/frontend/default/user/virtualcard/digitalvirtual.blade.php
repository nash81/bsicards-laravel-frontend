@extends('frontend::layouts.user')
@section('title')
    {{ __('Virtual MasterCard') }}
@endsection
@section('content')
    <div class="row">
        <!-- Get New Card Form -->
        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Get New Virtual MasterCard') }}</h3>
                </div>
                <div class="site-card-body">
                    <form action="{{ route('user.digitalnewvirtualcard') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Name on card') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" value="{{ $user->firstname }} {{ $user->lastname }}" readonly>
                                    <input type="hidden" name="nameoncard" value="{{ $user->firstname }} {{ $user->lastname }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Email') }}<span class="required">*</span></label>
                                    <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                                    <input type="hidden" name="useremail" value="{{ $user->email }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Date Of Birth') }}<span class="required">*</span></label>
                                    <input type="date" class="form-control" name="dob" id="dob" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Phone Number') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" name="phone" placeholder="Enter phone number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Country Code') }}<span class="required">*</span></label>
                                    <select class="form-control form-select" id="countrycode" name="countrycode" required>
                                        <option value="">{{ __('Select Code') }}</option>
                                        <option value="1">United States +1</option>
                                        <option value="44">United Kingdom +44</option>
                                        <option value="91">India +91</option>
                                        <option value="86">China +86</option>
                                        <option value="81">Japan +81</option>
                                        <option value="49">Germany +49</option>
                                        <option value="33">France +33</option>
                                        <option value="234">Nigeria +234</option>
                                        <option value="254">Kenya +254</option>
                                        <option value="27">South Africa +27</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Country') }}<span class="required">*</span></label>
                                    <select class="form-control form-select" id="country" name="country" required>
                                        <option value="">{{ __('Select Country') }}</option>
                                        <option value="US">United States</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="NG">Nigeria</option>
                                        <option value="KE">Kenya</option>
                                        <option value="ZA">South Africa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Address') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" name="address1" placeholder="Enter address" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('City') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" name="city" placeholder="Enter city" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('State') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" name="state" placeholder="Enter state" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Zip Code') }}<span class="required">*</span></label>
                                    <input type="text" class="form-control" name="postalcode" placeholder="Enter zip code" required>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i data-lucide="info"></i>
                            {{ __('Virtual MasterCard Issuance Fee') }}: <strong>$ {{ $general->digifee }}</strong> {{ __('will be debited from your balance') }}
                        </div>

                        <div class="action-btns mt-3">
                            <button type="submit" class="site-btn primary-btn">
                                <i data-lucide="credit-card"></i> {{ __('Proceed to Issue Card') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Issued Cards Table -->
        <div class="col-xl-12">
            <div class="site-card">
                <div class="site-card-header">
                    <h3 class="title-small">{{ __('Your Issued Virtual MasterCards') }}</h3>
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
                            @forelse ($virtualcards->data as $item)
                            <div class="site-table-list">
                                <div class="site-table-col">
                                    <div class="description">
                                        <div class="event-icon">
                                            <i data-lucide="credit-card"></i>
                                        </div>
                                        <div class="content">
                                            <div class="title">{{ $item->cardid }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="site-table-col">
                                    <div class="trx">{{ $item->nameoncard }}</div>
                                </div>
                                <div class="site-table-col">
                                    <span class="site-badge badge-primary">**** {{ $item->lastfour ?? '' }}</span>
                                </div>
                                <div class="site-table-col">
                                    <div class="action">
                                        <a href="{{ route('user.getdigitalcard',$item->cardid) }}" class="icon-btn">
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

