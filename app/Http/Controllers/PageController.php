<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('questionnaires.index');
        }

        return view('pages.landing');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function apiDocs(): View
    {
        return view('pages.api-docs');
    }
}
