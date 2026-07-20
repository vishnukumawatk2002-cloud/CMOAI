@extends('layouts.legal')

@section('title', 'Terms of Service')
@section('meta_description', 'Terms of Service for CMO AI — rules for using our platform.')

@section('content')
<div class="legal-head">
    <span class="legal-badge"><i class="ti ti-file-text"></i> Legal</span>
    <h1>Terms of Service</h1>
    <p class="legal-updated">Last updated: {{ date('F j, Y') }}</p>
</div>

<div class="legal-body">
    <p>These Terms of Service ("Terms") govern your access to and use of CMO AI's website, application, and related services (collectively, the "Service"). By creating an account or using the Service, you agree to these Terms.</p>

    <h2>1. Eligibility</h2>
    <p>You must be at least 18 years old and able to form a binding contract. If you use the Service on behalf of a company, you represent that you have authority to bind that organization to these Terms.</p>

    <h2>2. Account registration</h2>
    <p>You are responsible for maintaining the confidentiality of your login credentials and for all activity under your account. Notify us immediately at <a href="mailto:hello@cmoai.app">hello@cmoai.app</a> if you suspect unauthorized access.</p>

    <h2>3. Subscriptions and billing</h2>
    <p>Paid plans are billed according to the pricing shown at checkout or on our website. Fees are non-refundable except where required by law or explicitly stated in writing. We may change pricing with reasonable notice; continued use after a price change constitutes acceptance.</p>
    <p>Free trials or promotional credits may be subject to additional limits described at signup.</p>

    <h2>4. Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Service for unlawful, harmful, fraudulent, or misleading purposes</li>
        <li>Publish content that infringes intellectual property, privacy, or other rights</li>
        <li>Generate or distribute spam, hate speech, harassment, or prohibited content under applicable platform policies</li>
        <li>Attempt to reverse engineer, scrape, overload, or disrupt the Service</li>
        <li>Share account access with unauthorized users or resell the Service without permission</li>
        <li>Upload malware or attempt unauthorized access to our systems or third-party accounts</li>
    </ul>
    <p>You are solely responsible for content created, scheduled, or published through your account, including AI-generated drafts you approve.</p>

    <h2>5. Social platform connections</h2>
    <p>When you connect social accounts, you authorize CMO AI to access and use platform APIs as permitted by you to publish content on your behalf. Your use must comply with each platform's terms (Facebook, Instagram, Meta, LinkedIn, X, YouTube, etc.). We are not responsible for actions taken by third-party platforms, including account restrictions or content removal.</p>

    <h2>6. AI-generated content</h2>
    <p>AI outputs are provided for assistance only. You must review all generated content before publishing. We do not guarantee accuracy, originality, legal compliance, or suitability for your audience. You retain responsibility for final published materials.</p>

    <h2>7. Your content and license</h2>
    <p>You retain ownership of Brand Data and content you upload. You grant CMO AI a worldwide, non-exclusive license to host, process, reproduce, and display your content solely to operate and improve the Service, including AI training and generation limited to your workspace.</p>

    <h2>8. Our intellectual property</h2>
    <p>The Service, including software, design, branding, and documentation, is owned by CMO AI and protected by intellectual property laws. These Terms do not grant you any rights to our trademarks or proprietary technology except as needed to use the Service.</p>

    <h2>9. Availability and modifications</h2>
    <p>We strive for reliable uptime but do not guarantee uninterrupted access. We may modify, suspend, or discontinue features with reasonable notice when possible. We may update these Terms; material changes will be posted on this page.</p>

    <h2>10. Termination</h2>
    <p>You may cancel your account at any time. We may suspend or terminate access if you violate these Terms, fail to pay fees, or if required for legal or security reasons. Upon termination, your right to use the Service ends; certain provisions survive termination.</p>

    <h2>11. Disclaimers</h2>
    <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>

    <h2>12. Limitation of liability</h2>
    <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, CMO AI AND ITS AFFILIATES SHALL NOT BE LIABLE FOR INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS, DATA, OR GOODWILL, ARISING FROM YOUR USE OF THE SERVICE. OUR TOTAL LIABILITY FOR ANY CLAIM SHALL NOT EXCEED THE AMOUNT YOU PAID US IN THE TWELVE (12) MONTHS BEFORE THE CLAIM.</p>

    <h2>13. Indemnification</h2>
    <p>You agree to indemnify and hold harmless CMO AI from claims, damages, and expenses arising from your content, your use of the Service, or your violation of these Terms or third-party rights.</p>

    <h2>14. Governing law</h2>
    <p>These Terms are governed by the laws of India, without regard to conflict-of-law principles. Disputes shall be subject to the exclusive jurisdiction of courts in Mumbai, Maharashtra, unless otherwise required by applicable consumer protection law.</p>

    <h2>15. Contact</h2>
    <p>Questions about these Terms:</p>
    <ul>
        <li>Email: <a href="mailto:legal@cmoai.app">legal@cmoai.app</a></li>
        <li>Support: <a href="mailto:hello@cmoai.app">hello@cmoai.app</a></li>
    </ul>
</div>
@endsection
