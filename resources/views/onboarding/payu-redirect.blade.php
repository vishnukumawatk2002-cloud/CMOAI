<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting to PayU — CMO AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
    @include('layouts.partials.head-assets')
    <style>
        .payu-redirect-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:linear-gradient(180deg,#F8FAFC 0%,#EEF2FF 100%)}
        .payu-redirect-card{max-width:420px;width:100%;background:#fff;border:1px solid #E2E8F0;border-radius:20px;padding:32px;text-align:center;box-shadow:0 20px 50px rgba(15,23,42,.08)}
        .payu-redirect-icon{width:64px;height:64px;border-radius:50%;background:#ECFDF5;color:#10B981;display:inline-flex;align-items:center;justify-content:center;font-size:30px;margin-bottom:16px}
        .payu-redirect-card h1{font-size:22px;margin:0 0 8px}
        .payu-redirect-card p{color:#64748B;margin:0 0 20px;line-height:1.5}
        .payu-spinner{width:28px;height:28px;border:3px solid #E2E8F0;border-top-color:#6C63FF;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body class="onboarding-page">
    <div class="payu-redirect-page">
        <div class="payu-redirect-card">
            <div class="payu-redirect-icon"><i class="ti ti-credit-card"></i></div>
            <h1>Redirecting to PayU</h1>
            <p>Please wait while we securely redirect you to complete payment for the <strong>{{ $planName }}</strong> plan.</p>
            <div class="payu-spinner" aria-hidden="true"></div>
        </div>
    </div>

    <form id="payu-form" method="POST" action="{{ $action }}">
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('payu-form').submit();
    </script>
</body>
</html>
