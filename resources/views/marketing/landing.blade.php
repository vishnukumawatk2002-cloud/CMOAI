<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CMO AI — Your AI-Powered Chief Marketing Officer</title>
    <meta name="description" content="CMO AI learns your brand and auto-publishes content across every social platform.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @include('layouts.partials.head-assets', ['landing' => true, 'withScripts' => false])
</head>
<body>
<nav>
    <div class="nav-in">
        <a href="{{ route('landing') }}" class="logo">
            <div class="logo-icon"><i class="ti ti-speakerphone"></i></div>
            <span class="logo-text">CMO <span>AI</span></span>
        </a>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#how">How it works</a></li>
            <li><a href="#pricing">Pricing</a></li>
        </ul>
        <div class="nav-btns">
            @auth
                <a href="{{ route('app.dashboard') }}" class="btn btn-outline" style="font-size:13px;padding:8px 16px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ auth()->user()->full_name }}">
                    <i class="ti ti-user" style="margin-right:4px"></i>{{ auth()->user()->full_name ?: auth()->user()->email }}
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="btn btn-green" style="font-size:13px;padding:8px 16px">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline" style="font-size:13px;padding:8px 16px">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-green" style="font-size:13px;padding:8px 16px">Start free <i class="ti ti-arrow-right"></i></a>
            @endauth
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="hero-badge"><i class="ti ti-sparkles"></i> AI-Powered Social Media Automation</div>
        <h1>Your brand's <span class="hi2">AI Chief</span><br><span class="hi">Marketing Officer</span></h1>
        <p class="hero-sub">CMO AI learns your brand, creates on-brand content, schedules it, and auto-publishes across every platform — no team needed.</p>
        <div class="hero-btns">
            @auth
                <a href="{{ route('app.dashboard') }}" class="btn btn-green btn-lg">Go to Dashboard <i class="ti ti-arrow-right"></i></a>
            @else
                <a href="{{ route('register') }}" class="btn btn-green btn-lg">Start for free <i class="ti ti-arrow-right"></i></a>
            @endauth
            <a href="#how" class="btn btn-outline btn-lg">See how it works</a>
        </div>
        <div class="hero-note">Trusted by <strong>500+ brands</strong> in India · No credit card required</div>
        <div class="stats-row">
            <div class="stat"><div class="stat-n">14</div><div class="stat-l">Automated steps</div></div>
            <div class="stat"><div class="stat-n">7</div><div class="stat-l">Social platforms</div></div>
            <div class="stat"><div class="stat-n">30×</div><div class="stat-l">Faster content</div></div>
            <div class="stat"><div class="stat-n">0</div><div class="stat-l">Manual publishing</div></div>
        </div>
    </div>
</section>

<div class="mockup-wrap">
    <div class="container">
        <div class="browser">
            <div class="browser-bar">
                <div class="b-dots"><span style="background:#FF5C7A"></span><span style="background:#FFB547"></span><span style="background:#22E3AE"></span></div>
                <div class="b-url"><i class="ti ti-lock"></i> app.cmoai.app/dashboard</div>
            </div>
            <div class="dash-in">
                <div class="dp-side">
                    <div class="dp-logo"><div class="dp-logo-i"><i class="ti ti-speakerphone" style="color:#fff;font-size:12px"></i></div>CMO <span>AI</span></div>
                    <div class="dp-item active"><i class="ti ti-layout-dashboard"></i> Dashboard</div>
                    <div class="dp-item"><i class="ti ti-sparkles"></i> Generate content</div>
                    <div class="dp-item"><i class="ti ti-folder"></i> Post Library</div>
                    <div class="dp-item"><i class="ti ti-calendar"></i> Schedule</div>
                    <div class="dp-item"><i class="ti ti-chart-bar"></i> Analytics</div>
                </div>
                <div class="dp-main">
                    <div class="dp-head">
                        <div class="dp-title">Good morning, Ravi 👋</div>
                        <button type="button" style="background:var(--green);color:#0B1120;border:none;border-radius:6px;padding:6px 12px;font-size:11px;cursor:pointer;font-family:'Inter',sans-serif;font-weight:700;display:flex;align-items:center;gap:4px"><i class="ti ti-plus" style="font-size:11px"></i> New content</button>
                    </div>
                    <div class="dp-cards">
                        <div class="dp-card"><div class="dp-card-l">Total reach</div><div class="dp-card-n">128K</div><div class="dp-card-t"><i class="ti ti-trending-up" style="font-size:10px"></i> +34%</div></div>
                        <div class="dp-card"><div class="dp-card-l">Posts published</div><div class="dp-card-n">87</div><div class="dp-card-t"><i class="ti ti-trending-up" style="font-size:10px"></i> +12</div></div>
                        <div class="dp-card"><div class="dp-card-l">Engagement</div><div class="dp-card-n">6.4%</div><div class="dp-card-t"><i class="ti ti-trending-up" style="font-size:10px"></i> +1.2%</div></div>
                        <div class="dp-card"><div class="dp-card-l">Scheduled</div><div class="dp-card-n">30</div><div class="dp-card-t" style="color:var(--text3)">Next 30 days</div></div>
                    </div>
                    <div class="dp-chart">
                        <div class="bar-i" style="height:32%"></div>
                        <div class="bar-i" style="height:48%"></div>
                        <div class="bar-i" style="height:55%"></div>
                        <div class="bar-i hi" style="height:70%"></div>
                        <div class="bar-i hi" style="height:65%"></div>
                        <div class="bar-i hi" style="height:88%"></div>
                        <div class="bar-i hi" style="height:100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="features" id="features">
    <div class="container">
        <div class="sec-label">Everything you need</div>
        <h2 class="sec-h">One CMO for every platform</h2>
        <p class="sec-sub">From brand knowledge to published post — CMO AI runs the full content cycle so you focus on the business.</p>
        <div class="feat-grid">
            <div class="feat-card"><div class="feat-icon" style="background:var(--purple-lt)"><i class="ti ti-cpu" style="color:var(--purple2)"></i></div><h3>Learns your brand</h3><p>Upload your website, PDFs, and assets. CMO AI builds a knowledge base that understands your tone, audience, and products.</p></div>
            <div class="feat-card"><div class="feat-icon" style="background:rgba(181,168,255,.1)"><i class="ti ti-pencil" style="color:var(--purple2)"></i></div><h3>Generates on-brand content</h3><p>Posts, carousels, reel scripts, image captions — all written in your exact brand voice, in seconds.</p></div>
            <div class="feat-card"><div class="feat-icon" style="background:var(--green-lt)"><i class="ti ti-calendar" style="color:var(--green)"></i></div><h3>Bulk scheduling</h3><p>Schedule 30 posts across 5 accounts for 30 days in a single click. No spreadsheets, no manual time slots.</p></div>
            <div class="feat-card"><div class="feat-icon" style="background:rgba(255,181,71,.1)"><i class="ti ti-send" style="color:var(--warning)"></i></div><h3>Auto-publishing</h3><p>At the right time, CMO AI publishes to Facebook, Instagram, LinkedIn, X, YouTube, and more — completely automatically.</p></div>
            <div class="feat-card"><div class="feat-icon" style="background:var(--purple-lt)"><i class="ti ti-chart-bar" style="color:var(--purple2)"></i></div><h3>Analytics + AI insights</h3><p>Track reach, engagement, and followers across all platforms. AI tells you what to post next and exactly when.</p></div>
            <div class="feat-card"><div class="feat-icon" style="background:var(--green-lt)"><i class="ti ti-users" style="color:var(--green)"></i></div><h3>Multi-brand workspaces</h3><p>Manage multiple brands or clients from one account. Each brand gets its own AI knowledge base and analytics.</p></div>
        </div>
    </div>
</section>

<section class="hiw" id="how">
    <div class="container" style="text-align:center">
        <div class="sec-label" style="text-align:center">The flow</div>
        <h2 class="sec-h" style="text-align:center">Set up once. Run forever.</h2>
        <div class="steps-row">
            <div class="hiw-step"><div class="hiw-num on">1</div><h4>Create brand</h4><p>Name, logo, industry, social accounts</p></div>
            <div class="hiw-step"><div class="hiw-num on">2</div><h4>Upload assets</h4><p>Website, PDFs, images, guidelines</p></div>
            <div class="hiw-step"><div class="hiw-num on">3</div><h4>AI learns</h4><p>Brand knowledge base built automatically</p></div>
            <div class="hiw-step"><div class="hiw-num">4</div><h4>Generate &amp; approve</h4><p>AI creates content, you bulk approve</p></div>
            <div class="hiw-step"><div class="hiw-num">5</div><h4>Auto-publish</h4><p>Posts go out on their own, on schedule</p></div>
        </div>
    </div>
</section>

<section class="plat-section">
    <div class="container" style="text-align:center">
        <div class="sec-label" style="text-align:center">Publishes to</div>
        <h2 class="sec-h" style="text-align:center;font-size:24px">Every platform your audience uses</h2>
        <div class="plat-row">
            <div class="plat"><i class="ti ti-brand-facebook" style="color:#1877F2"></i> Facebook</div>
            <div class="plat"><i class="ti ti-brand-instagram" style="color:#E1306C"></i> Instagram</div>
            <div class="plat"><i class="ti ti-brand-linkedin" style="color:#0A66C2"></i> LinkedIn</div>
            <div class="plat"><i class="ti ti-brand-x"></i> X / Twitter</div>
            <div class="plat"><i class="ti ti-brand-youtube" style="color:#FF0000"></i> YouTube</div>
            <div class="plat"><i class="ti ti-brand-pinterest" style="color:#E60023"></i> Pinterest</div>
            <div class="plat"><i class="ti ti-brand-threads"></i> Threads</div>
        </div>
    </div>
</section>

<section class="pricing" id="pricing">
    <div class="container">
        <div class="sec-label" style="text-align:center">Pricing</div>
        <h2 class="sec-h" style="text-align:center">Simple, flat pricing</h2>
        <div class="pricing-grid">
            @foreach($plans as $plan)
                @if($plan->slug === 'enterprise')
                <div class="p-card">
                    <p class="p-name">{{ $plan->name }}</p>
                    <p class="p-price p-price-custom">Custom</p>
                    <p class="p-period">tailored pricing</p>
                    @if($plan->subtitle)
                        <p class="p-subtitle">{{ $plan->subtitle }}</p>
                    @endif
                    <ul class="p-feats">
                        <li><i class="ti ti-check"></i> Unlimited brand workspaces</li>
                        <li><i class="ti ti-check"></i> SSO / SAML</li>
                        <li><i class="ti ti-check"></i> Dedicated manager</li>
                        <li><i class="ti ti-check"></i> SLA guarantee</li>
                        <li><i class="ti ti-check"></i> On-premise option</li>
                    </ul>
                    <a href="mailto:sales@cmoai.app" class="p-btn">Contact sales <i class="ti ti-arrow-right"></i></a>
                </div>
                @else
                <div class="p-card {{ $plan->slug === 'growth' ? 'featured' : '' }}">
                    @if($plan->slug === 'growth')
                        <div class="p-badge">Most popular</div>
                    @endif
                    <p class="p-name">{{ $plan->name }}</p>
                    <p class="p-price"><sup>₹</sup>{{ number_format($plan->price_monthly) }}</p>
                    <p class="p-period">per month</p>
                    @if($plan->subtitle)
                        <p class="p-subtitle">{{ $plan->subtitle }}</p>
                    @endif
                    <ul class="p-feats">
                        <li><i class="ti ti-check"></i> {{ $plan->formatLimit($plan->max_social_accounts) }} social accounts</li>
                        <li><i class="ti ti-check"></i> {{ $plan->formatLimit($plan->max_posts_per_month) }} posts / month</li>
                        @foreach($plan->editableFeatures() as $feature)
                            <li class="{{ $feature['enabled'] ? '' : 'p-feature-disabled' }}">
                                <i class="ti ti-{{ $feature['enabled'] ? 'check' : 'x' }}"></i>
                                {{ $feature['name'] }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="p-btn">Get {{ $plan->name }} <i class="ti ti-arrow-right"></i></a>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to post on autopilot?</h2>
        <p>Set up your brand in 10 minutes. CMO AI handles every post after that.</p>
        @auth
            <a href="{{ route('app.dashboard') }}" class="btn btn-green btn-lg">Go to Dashboard <i class="ti ti-arrow-right"></i></a>
        @else
            <a href="{{ route('register') }}" class="btn btn-green btn-lg">Start free — no card needed <i class="ti ti-arrow-right"></i></a>
        @endauth
    </div>
</section>

<footer>
    <div class="foot-in">
        <div class="foot-logo"><div class="foot-logo-i"><i class="ti ti-speakerphone" style="color:#fff;font-size:12px"></i></div>CMO <span>AI</span></div>
        <div class="foot-links">
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.data-deletion') }}">Data deletion</a>
            <a href="mailto:hello@cmoai.app">Contact</a>
        </div>
        <span style="color:var(--text3)">&copy; {{ date('Y') }} CMO AI. All rights reserved.</span>
    </div>
</footer>
</body>
</html>
