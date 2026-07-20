<div class="modal-overlay @if($errors->any() || session('error') || ($showBrandModal ?? false)) is-open @endif" id="brand-modal" aria-hidden="{{ ($errors->any() || session('error') || ($showBrandModal ?? false)) ? 'false' : 'true' }}">

    <div class="cmo-modal" role="dialog" aria-modal="true" aria-labelledby="brand-modal-title">

        <div class="modal-head">

            <div>

                <h2 id="brand-modal-title">Create your brand</h2>

                <p>Tell CMO AI about your brand so it can learn its voice.</p>

            </div>

            <button type="button" class="close-btn" data-close-brand-modal aria-label="Close"><i class="ti ti-x"></i></button>

        </div>

        <form method="POST" action="{{ route('onboarding.brand.store') }}" enctype="multipart/form-data">

            @csrf

            <div class="modal-body">

                @if (session('error'))
                    <div class="alert-bar error" style="margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                        <div>
                            @if (session('upgrade_plan'))
                                <div style="font-weight:700;margin-bottom:2px">Plan limit exceeded</div>
                                <div>{{ session('error') }}</div>
                            @else
                                <span>{{ session('error') }}</span>
                            @endif
                        </div>
                        @if (session('upgrade_plan'))
                            <a href="{{ route('onboarding.plan') }}" class="btn btn-green btn-sm" style="white-space:nowrap;text-decoration:none">
                                Upgrade <i class="ti ti-arrow-right"></i>
                            </a>
                        @endif
                    </div>
                @endif
                @if ($errors->any())

                    <div class="alert-bar error" style="margin-bottom:12px">Please fix the errors below and try again.</div>

                @endif

                <div class="mfield-row">

                    <div class="mfield">

                        <label for="brand-name">Brand name *</label>

                        <input type="text" id="brand-name" name="name" value="{{ old('name') }}" placeholder="Acme Corp" required>

                        @error('name')<span class="field-error">{{ $message }}</span>@enderror

                    </div>

                    <div class="mfield">

                        <label for="brand-website">Website</label>

                        <input type="text" id="brand-website" name="website" value="{{ old('website') }}" placeholder="https://acmecorp.com">

                        @error('website')<span class="field-error">{{ $message }}</span>@enderror

                    </div>

                </div>

                <div class="mfield-row">

                    <div class="mfield">

                        <label for="brand-industry">Industry *</label>

                        <select id="brand-industry" name="industry" required>

                            <option value="">Select…</option>

                            @foreach (['SaaS / Tech', 'E-commerce', 'Real estate', 'Healthcare', 'Education', 'Agency', 'Other'] as $ind)

                                <option value="{{ $ind }}" @selected(old('industry') === $ind)>{{ $ind }}</option>

                            @endforeach

                        </select>

                        @error('industry')<span class="field-error">{{ $message }}</span>@enderror

                    </div>

                    <div class="mfield">

                        <label for="brand-country">Country *</label>

                        <select id="brand-country" name="country" required>

                            @foreach (['India'] as $c)

                                <option value="{{ $c }}" @selected(old('country', 'India') === $c)>{{ $c }}</option>

                            @endforeach

                        </select>

                        @error('country')<span class="field-error">{{ $message }}</span>@enderror

                    </div>

                </div>

                <div class="mfield-row">

                    <div class="mfield">

                        <label for="brand-language">Language</label>

                        <select id="brand-language" name="language">

                            @foreach (['English', 'Hindi', 'Tamil'] as $lang)

                                <option value="{{ $lang }}" @selected(old('language', 'English') === $lang)>{{ $lang }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div class="mfield">

                        <label for="brand-tone">Brand tone</label>

                        <select id="brand-tone" name="tone">

                            @foreach (['Professional', 'Casual & friendly', 'Bold & energetic', 'Educational'] as $t)

                                <option value="{{ $t }}" @selected(old('tone', 'Professional') === $t)>{{ $t }}</option>

                            @endforeach

                        </select>

                    </div>

                </div>

                <div class="mfield">

                    <label for="brand-logo">Brand logo</label>

                    <input type="file" id="brand-logo" name="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" hidden>

                    <div class="upload-zone" data-upload-trigger="brand-logo">

                        <i class="ti ti-upload"></i>

                        <p>Drop logo here or click to upload · PNG, SVG · Max 2 MB</p>

                    </div>

                    @error('logo')<span class="field-error">{{ $message }}</span>@enderror

                </div>

            </div>

            <div class="modal-foot">

                <button type="button" class="mf-cancel" data-close-brand-modal>Cancel</button>

                <button type="submit" class="mf-submit"><i class="ti ti-arrow-right"></i> Create brand &amp; pick plan</button>

            </div>

        </form>

    </div>

</div>

