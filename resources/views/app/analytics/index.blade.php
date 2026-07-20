@extends('layouts.app')

@section('title', 'Analytics — CMO AI')
@section('pageTitle', 'Analytics')

@section('content')
<div class="stats-grid">
    <div class="stat-card"><div class="sc-label"><i class="ti ti-eye"></i> Total reach</div><div class="sc-num">{{ number_format($stats['reach'] ?? 0) }}</div></div>
    <div class="stat-card"><div class="sc-label"><i class="ti ti-heart"></i> Engagement</div><div class="sc-num">{{ $stats['engagement'] ?? 0 }}%</div></div>
    <div class="stat-card"><div class="sc-label"><i class="ti ti-send"></i> Published</div><div class="sc-num">{{ $stats['published'] ?? 0 }}</div></div>
    <div class="stat-card"><div class="sc-label"><i class="ti ti-calendar"></i> Scheduled</div><div class="sc-num">{{ $stats['scheduled'] ?? 0 }}</div></div>
</div>
<div class="card">
    <div class="card-title">Reach this week</div>
    <div class="chart">
        @foreach($weeklyReach ?? [32,48,56,72,66,90,100] as $i => $h)
        <div class="bar {{ $i >= 3 ? 'hi' : '' }}" style="height:{{ $h }}%"></div>
        @endforeach
    </div>
    <div class="chart-labels"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
</div>
@endsection
