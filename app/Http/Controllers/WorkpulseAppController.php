<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class WorkpulseAppController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.workpulse');
    }
}
