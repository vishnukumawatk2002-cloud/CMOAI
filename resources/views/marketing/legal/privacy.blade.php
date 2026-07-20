@extends('layouts.legal')

@section('title', 'Privacy Policy')
@section('meta_description', 'Privacy Policy for CMO AI — how we collect, use, and protect your data.')

@section('content')
<div class="legal-head">
    <span class="legal-badge"><i class="ti ti-shield-lock"></i> Legal</span>
    <h1>Privacy Policy</h1>
    <p class="legal-updated">Last updated: {{ date('F j, Y') }}</p>
</div>

<div class="legal-body">
    <p>CMO AI ("we", "our", or "us") provides an AI-powered marketing platform that helps businesses create, plan, and publish social media content. This Privacy Policy explains how we collect, use, store, and share information when you use our website and application (the "Service").</p>

    <h2>1. Information we collect</h2>
    <h3>Account information</h3>
    <p>When you register, we collect your name, email address, password (stored securely hashed), and optional profile details.</p>
    <h3>Brand and business data</h3>
    <p>To generate on-brand content, you may provide brand names, descriptions, website URLs, reference links, uploaded images, videos, logos, and other marketing materials ("Brand Data").</p>
    <h3>Social account connections</h3>
    <p>If you connect social platforms (such as Facebook, Instagram, LinkedIn, X, or YouTube), we receive account identifiers, page/profile names, access tokens, and metadata required to publish content on your behalf. We do not read private messages, contacts, or unrelated personal data from connected accounts.</p>
    <h3>Usage and technical data</h3>
    <p>We automatically collect log data such as IP address, browser type, device information, pages visited, and actions taken within the Service to improve security and performance.</p>

    <h2>2. How we use your information</h2>
    <ul>
        <li>Provide, operate, and maintain the Service</li>
        <li>Generate AI content based on your Brand Data and instructions</li>
        <li>Schedule and publish posts to connected social accounts</li>
        <li>Process subscriptions and billing</li>
        <li>Send service-related emails (account, security, product updates)</li>
        <li>Improve our models, features, and user experience</li>
        <li>Detect fraud, abuse, and security incidents</li>
        <li>Comply with legal obligations</li>
    </ul>

    <h2>3. AI processing</h2>
    <p>Content generation may use third-party AI providers. Brand Data you submit may be processed to produce captions, images, posts, and schedules. We configure providers to process data only as needed to deliver the Service. Do not submit sensitive personal data you are not authorized to share.</p>

    <h2>4. How we share information</h2>
    <p>We do not sell your personal information. We may share data with:</p>
    <ul>
        <li><strong>Service providers</strong> — hosting, email, analytics, payment, and AI infrastructure partners under contractual confidentiality obligations</li>
        <li><strong>Social platforms</strong> — when you authorize publishing to Facebook, Instagram, LinkedIn, X, YouTube, or other networks</li>
        <li><strong>Legal requirements</strong> — when required by law, court order, or to protect rights and safety</li>
        <li><strong>Business transfers</strong> — in connection with a merger, acquisition, or asset sale, with notice where required</li>
    </ul>

    <h2>5. Data retention</h2>
    <p>We retain your information while your account is active and as needed to provide the Service, resolve disputes, enforce agreements, and meet legal requirements. You may request deletion as described in our <a href="{{ route('legal.data-deletion') }}">Data Deletion</a> page.</p>

    <h2>6. Security</h2>
    <p>We use industry-standard measures including encryption in transit, access controls, and secure storage for credentials and OAuth tokens. No method of transmission over the Internet is 100% secure; we cannot guarantee absolute security.</p>

    <h2>7. Your rights and choices</h2>
    <p>Depending on your location, you may have rights to access, correct, delete, or export your personal data, and to object to or restrict certain processing. To exercise these rights, contact us at <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a> or use the data deletion instructions on our website.</p>
    <p>You may disconnect social accounts at any time from Social accounts settings within the app.</p>

    <h2>8. Cookies</h2>
    <p>We use essential cookies and session storage to keep you logged in and remember preferences. We may use analytics cookies to understand product usage. You can control cookies through your browser settings.</p>

    <h2>9. International transfers</h2>
    <p>Your information may be processed in countries other than your own. We take steps to ensure appropriate safeguards when data is transferred internationally.</p>

    <h2>10. Children</h2>
    <p>The Service is not intended for users under 18. We do not knowingly collect personal information from children.</p>

    <h2>11. Changes to this policy</h2>
    <p>We may update this Privacy Policy from time to time. We will post the revised version on this page and update the "Last updated" date. Continued use of the Service after changes constitutes acceptance.</p>

    <h2>12. Contact us</h2>
    <p>Questions about this Privacy Policy:</p>
    <ul>
        <li>Email: <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a></li>
        <li>General support: <a href="mailto:hello@cmoai.app">hello@cmoai.app</a></li>
    </ul>
</div>
@endsection
