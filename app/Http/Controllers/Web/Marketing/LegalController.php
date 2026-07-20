<?php

namespace App\Http\Controllers\Web\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('marketing.legal.privacy');
    }

    public function terms(): View
    {
        return view('marketing.legal.terms');
    }

    public function dataDeletion(): View
    {
        return view('marketing.legal.data-deletion');
    }
}
