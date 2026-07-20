@extends('layouts.legal')

@section('title', 'Data Deletion Instructions')
@section('meta_description', 'How to request deletion of your CMO AI account and personal data.')

@section('content')
<div class="legal-head">
    <span class="legal-badge"><i class="ti ti-trash"></i> Legal</span>
    <h1>Data Deletion Instructions</h1>
    <p class="legal-updated">Last updated: {{ date('F j, Y') }}</p>
</div>

<div class="legal-body">
    <p>CMO AI respects your right to control your personal data. This page explains how users can request deletion of account data, including information obtained through Facebook Login or other social platform connections, as required by Meta and applicable privacy laws.</p>

    <div class="legal-callout">
        <i class="ti ti-info-circle"></i>
        <div>
            <strong>Quick request</strong>
            <p>Email <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a> from your registered account email with the subject line <strong>Data Deletion Request</strong>. We typically process requests within 30 days.</p>
        </div>
    </div>

    <h2>1. What you can delete yourself (in the app)</h2>
    <p>After logging in, you can remove much of your data without contacting support:</p>
    <ul>
        <li><strong>Social accounts</strong> — go to <em>Social accounts</em> and disconnect each connected platform. This revokes our access tokens and removes linked publishing credentials.</li>
        <li><strong>Content</strong> — delete posts, drafts, and library items from Content Library, Post planning, and Ai Post Library.</li>
        <li><strong>Brand assets</strong> — remove uploaded images, videos, and files from Brand Data Source / Content Library.</li>
        <li><strong>Brand workspace</strong> — delete a brand from Brand settings if you no longer need that workspace.</li>
    </ul>

    <h2>2. Request full account deletion</h2>
    <p>To delete your entire CMO AI account and associated personal data:</p>
    <ol>
        <li>Send an email to <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a> from the email address linked to your account.</li>
        <li>Include your full name and, if available, your brand name or account ID.</li>
        <li>Use subject: <strong>Data Deletion Request</strong>.</li>
        <li>We may verify your identity before processing the request.</li>
    </ol>
    <p>Once verified, we will delete or anonymize your account data within <strong>30 days</strong>, unless retention is required by law (e.g. billing records).</p>

    <h2>3. Facebook / Meta data deletion</h2>
    <p>If you connected Facebook or Instagram through Meta:</p>
    <ul>
        <li>Disconnect the account in CMO AI under <em>Social accounts</em>, or</li>
        <li>Remove CMO AI from your Facebook Settings → <em>Business integrations</em> / <em>Apps and websites</em>, or</li>
        <li>Submit a deletion request to <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a> as described above.</li>
    </ul>
    <p>When we receive a valid Meta data deletion callback or user request, we delete:</p>
    <ul>
        <li>Facebook user ID and linked social account records</li>
        <li>Stored OAuth access tokens</li>
        <li>Page names, handles, and connection metadata associated with your brand</li>
        <li>Scheduled or draft posts tied solely to that connection</li>
    </ul>
    <p>We do not retain Facebook access tokens after disconnection or confirmed deletion.</p>

    <h2>4. Data that may be retained</h2>
    <p>After deletion, we may retain limited information when necessary for:</p>
    <ul>
        <li>Legal, tax, or accounting obligations (e.g. invoices)</li>
        <li>Security logs and fraud prevention (anonymized where possible)</li>
        <li>Aggregated, non-identifiable analytics</li>
    </ul>

    <h2>5. Data deletion status</h2>
    <p>After we complete your request, we will email confirmation to your registered address. If you do not receive a response within 30 days, contact <a href="mailto:hello@cmoai.app">hello@cmoai.app</a>.</p>

    <h2>6. Related policies</h2>
    <ul>
        <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
        <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
    </ul>

    <h2>7. Contact</h2>
    <p>Data protection inquiries:</p>
    <ul>
        <li>Email: <a href="mailto:privacy@cmoai.app">privacy@cmoai.app</a></li>
        <li>Support: <a href="mailto:hello@cmoai.app">hello@cmoai.app</a></li>
    </ul>
</div>
@endsection
