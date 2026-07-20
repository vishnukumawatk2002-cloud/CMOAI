@extends('layouts.app')

@section('title', 'Reports — CMO AI')
@section('pageTitle', 'Reports')

@section('topbarExtra')
    <button type="button" class="btn btn-purple btn-sm" disabled title="Coming soon"><i class="ti ti-file-plus"></i> Generate report</button>
@endsection

@section('content')
<div class="mgmt-page">
    <div class="mgmt-head">
        <div>
            <h1 class="mgmt-title">White-label Reports</h1>
            <p class="mgmt-sub">Generate and share branded reports with your clients</p>
        </div>
        <button type="button" class="btn btn-purple btn-sm mgmt-head-btn" disabled title="Coming soon"><i class="ti ti-file-plus"></i> Generate report</button>
    </div>

    @unless ($hasWhiteLabel)
        <div class="report-upgrade-banner">
            <div>
                <div class="report-upgrade-title">Upgrade for white-label reports</div>
                <div class="report-upgrade-sub">Agency and Enterprise plans include branded PDF reports for your clients.</div>
            </div>
            <a href="{{ route('onboarding.plan') }}" class="btn btn-purple btn-sm">View plans</a>
        </div>
    @endunless

    <div class="report-types-grid">
        @foreach ($reportTypes as $type)
            <button type="button" class="report-card" disabled title="Coming soon">
                <div class="report-icon" style="background:{{ $type['bg'] }}">
                    <i class="ti {{ $type['icon'] }}" style="color:{{ $type['color'] }}"></i>
                </div>
                <div>
                    <div class="report-card-title">{{ $type['title'] }}</div>
                    <div class="report-card-desc">{{ $type['desc'] }}</div>
                </div>
            </button>
        @endforeach
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-title">Recent reports sent</div>
        @if ($recentReports->isEmpty())
            <p class="mgmt-empty">No reports sent yet. Generate your first report to share with clients.</p>
        @else
            <div class="mgmt-table-wrap">
                <table class="mgmt-table">
                    <thead>
                        <tr>
                            <th>Report</th>
                            <th>Brand</th>
                            <th>Period</th>
                            <th>Sent to</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentReports as $report)
                            <tr>
                                <td>{{ $report['type'] }}</td>
                                <td>{{ $report['brand'] }}</td>
                                <td>{{ $report['period'] }}</td>
                                <td>{{ $report['sent_to'] }}</td>
                                <td>{{ $report['date'] }}</td>
                                <td><button type="button" class="btn btn-ghost btn-sm">View</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
