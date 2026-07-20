<?php

namespace App\Http\Controllers\Web\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hasWhiteLabel = (bool) $user->subscriptions()
            ->with('plan')
            ->latest()
            ->first()
            ?->plan
            ?->white_label_reports;

        $reportTypes = [
            ['title' => 'Monthly Performance', 'desc' => 'Reach, engagement, top posts per platform', 'icon' => 'ti-chart-bar', 'bg' => '#EEF0FF', 'color' => '#5B4FC9'],
            ['title' => 'Content Calendar Export', 'desc' => 'PDF/CSV of all scheduled posts', 'icon' => 'ti-calendar-stats', 'bg' => '#DCFCE7', 'color' => '#16A34A'],
            ['title' => 'Platform Deep Dive', 'desc' => 'Per-platform analytics with benchmarks', 'icon' => 'ti-brand-instagram', 'bg' => '#FEF9C3', 'color' => '#854D0E'],
            ['title' => 'Growth Report', 'desc' => 'Follower growth, reach trend over time', 'icon' => 'ti-trending-up', 'bg' => '#FEF2F2', 'color' => '#991B1B'],
            ['title' => 'White-label PDF', 'desc' => 'Branded with client logo, agency branding', 'icon' => 'ti-file-certificate', 'bg' => '#F0FDF4', 'color' => '#15803D'],
            ['title' => 'Campaign Report', 'desc' => 'Start-to-end campaign performance', 'icon' => 'ti-presentation-analytics', 'bg' => '#EFF6FF', 'color' => '#1D4ED8'],
        ];

        return view('app.reports.index', [
            'reportTypes' => $reportTypes,
            'recentReports' => collect(),
            'hasWhiteLabel' => $hasWhiteLabel,
        ]);
    }
}
