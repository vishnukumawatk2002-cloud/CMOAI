@extends('layouts.app')

@section('title', 'Content Library — '.$brand->name)
@section('pageTitle', 'Content Library')

@section('topbarExtra')
    <a href="{{ route('app.brand.knowledge-base') }}" class="btn btn-ghost btn-sm"><i class="ti ti-brain"></i> Knowledge base</a>
    <a href="{{ route('app.content.generate') }}" class="btn btn-green btn-sm"><i class="ti ti-sparkles"></i> Generate content</a>
@endsection

@section('content')
    @include('app.brand.partials.library-board', [
        'brand' => $brand,
        'tab' => $tab,
        'routeName' => $routeName ?? 'app.brand.content-library',
        'categories' => $categories,
        'kbReady' => $kbReady,
        'aiProvider' => $aiProvider,
        'aiFeatureLocked' => $aiFeatureLocked ?? false,
        'canAccessReels' => $canAccessReels ?? true,
    ])
@endsection
