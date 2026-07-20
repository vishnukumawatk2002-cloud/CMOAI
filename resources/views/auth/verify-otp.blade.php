<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify your email — CMO AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @include('layouts.partials.head-assets', ['withScripts' => false])
</head>
<body class="verify-page">
    <a href="{{ route('landing') }}" class="v-logo">
        <div class="v-logo-i"><i class="ti ti-speakerphone" style="color:#fff"></i></div>
        CMO <span>AI</span>
    </a>

    <div class="verify-card">
        <div class="steps">
            <div class="sd done"></div><div class="sl done"></div><div class="sd curr"></div><div class="sl"></div><div class="sd"></div><div class="sl"></div><div class="sd"></div>
        </div>
        <div class="mail-icon"><i class="ti ti-mail"></i></div>
        <h1>Check your email</h1>
        <p class="sub">We sent a 6-digit code to</p>
        <p class="email-hi">{{ $email }}</p>

        @if (session('status'))
            <div class="alert-bar success" style="margin:16px 0">{{ session('status') }}</div>
        @endif
        @if (session('mail_error'))
            <div class="alert-bar error" style="margin:16px 0">{{ session('mail_error') }}</div>
        @endif
        <!-- @if (session('dev_otp'))
            <div class="alert-bar success" style="margin:16px 0;text-align:left">
                <strong>Local dev mode:</strong> Email is logged, not sent to inbox.<br>
                Your OTP code: <strong style="font-size:18px;letter-spacing:4px">{{ session('dev_otp') }}</strong>
            </div>
        @endif -->
        @error('otp')
            <div class="alert-bar error" style="margin:16px 0">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('verification.otp') }}" id="otp-form">
            @csrf
            <div class="otp-label" style="margin-top:22px">Enter verification code</div>
            <div class="otp-wrap">
                @for ($i = 0; $i < 6; $i++)
                    <input class="otp-i" type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code" data-idx="{{ $i }}">
                @endfor
            </div>
            <input type="hidden" name="otp" id="otp-value">
            <button type="submit" class="vbtn" id="vbtn" disabled><i class="ti ti-shield-check"></i> Verify &amp; continue</button>
        </form>

        <form method="POST" action="{{ route('verification.resend') }}" id="resend-form">
            @csrf
            <div class="resend-row">Didn't receive it? <button type="submit" class="rbtn" id="rbtn" disabled>Resend code</button></div>
        </form>
        <div class="timer" id="timer">Resend available in <span id="cd">30</span>s</div>
        <div class="divider"></div>
        <div class="wrong">Wrong email? <a href="{{ route('register') }}">Go back and change it</a></div>
    </div>

    <script>
    const inputs = document.querySelectorAll('.otp-i');
    const otpValue = document.getElementById('otp-value');
    const vbtn = document.getElementById('vbtn');

    function syncOtp() {
        const code = [...inputs].map(i => i.value).join('');
        otpValue.value = code;
        vbtn.disabled = code.length !== 6;
    }

    inputs.forEach((inp, i) => {
        inp.addEventListener('input', () => {
            inp.value = inp.value.replace(/\D/g, '').slice(-1);
            if (inp.value && i < 5) inputs[i + 1].focus();
            inp.classList.toggle('filled', !!inp.value);
            syncOtp();
        });
        inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i - 1].focus();
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const d = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            d.split('').forEach((c, j) => { if (inputs[j]) { inputs[j].value = c; inputs[j].classList.add('filled'); } });
            if (inputs[Math.min(d.length, 5)]) inputs[Math.min(d.length, 5)].focus();
            syncOtp();
        });
    });
    inputs[0].focus();

    document.getElementById('otp-form').addEventListener('submit', () => syncOtp());

    let s = 30;
    const cd = document.getElementById('cd');
    const tm = document.getElementById('timer');
    const rb = document.getElementById('rbtn');
    const iv = setInterval(() => {
        s--; cd.textContent = s;
        if (s <= 0) { clearInterval(iv); tm.style.display = 'none'; rb.disabled = false; }
    }, 1000);
    </script>
</body>
</html>
