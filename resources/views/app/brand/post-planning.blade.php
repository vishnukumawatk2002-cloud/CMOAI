@extends('layouts.app')

@section('title', 'Post planning — '.$brand->name)
@section('pageTitle', 'Post planning')

@section('topbarExtra')
    <a href="{{ route('app.brand.knowledge-base') }}" class="btn btn-ghost btn-sm"><i class="ti ti-brain"></i> Knowledge base</a>
@endsection

@section('content')
    @include('app.brand.partials.library-board', [
        'brand' => $brand,
        'tab' => $tab,
        'routeName' => $routeName ?? 'app.brand.post-planning',
        'categories' => $categories,
        'kbReady' => $kbReady,
        'aiProvider' => $aiProvider,
        'showPlanningSelect' => true,
        'planningMixedContent' => $planningMixedContent ?? true,
        'connectedChannels' => $connectedChannels ?? collect(),
        'canAccessReels' => $canAccessReels ?? true,
    ])
@endsection
