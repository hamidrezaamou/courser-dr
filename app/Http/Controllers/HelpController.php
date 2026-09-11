<?php

namespace App\Http\Controllers;

use App\Support\SiteSettings;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function index(): View
    {
        return view('help.index', [
            'support' => [
                'telegram' => (string) SiteSettings::effective('support.telegram', ''),
                'phone' => (string) SiteSettings::effective('support.phone', ''),
                'email' => (string) SiteSettings::effective('support.email', ''),
                'sla_hours' => (int) SiteSettings::effective('support.sla_hours', 24),
                'notes' => (string) SiteSettings::effective('support.notes', ''),
            ],
            'retentionNote' => (string) SiteSettings::effective(
                'privacy.retention_note',
                'پرونده‌ها طبق سیاست مطب نگه داشته می‌شوند. حذف امن فقط توسط مدیر انجام می‌شود.'
            ),
        ]);
    }
}
