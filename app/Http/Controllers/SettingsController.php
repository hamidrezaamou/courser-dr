<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('settings.times');
    }
}
