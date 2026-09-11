<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Support\Digits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContactExportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isStaff(), 403);

        return view('settings.contacts');
    }

    public function json(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isStaff(), 403);

        return response()->json([
            'ok' => true,
            'contacts' => $this->contacts(),
        ]);
    }

    public function vcf(Request $request): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        $body = '';
        foreach ($this->contacts() as $row) {
            $body .= "BEGIN:VCARD\r\nVERSION:3.0\r\n";
            $body .= 'FN:'.$this->escape((string) $row['name'])."\r\n";
            foreach ($row['phones'] as $phone) {
                $body .= 'TEL;TYPE=CELL:'.$phone."\r\n";
            }
            $body .= "END:VCARD\r\n";
        }

        $filename = 'contacts-'.now()->format('Ymd-His').'.vcf';

        return response($body, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return list<array{name: string, phones: list<string>}>
     */
    private function contacts(): array
    {
        Patient::ensureMobileSecondaryColumn();

        $out = [];
        Patient::query()
            ->active()
            ->select(['id', 'name', 'mobile', 'mobile_secondary'])
            ->orderBy('id')
            ->chunkById(400, function ($rows) use (&$out) {
                foreach ($rows as $patient) {
                    $name = trim((string) $patient->name);
                    if ($name === '') {
                        continue;
                    }

                    $phones = [];
                    foreach ([$patient->mobile, $patient->mobile_secondary] as $raw) {
                        $phone = Digits::iranMobile((string) $raw);
                        if ($phone !== '' && ! in_array($phone, $phones, true)) {
                            $phones[] = $phone;
                        }
                    }

                    if ($phones === []) {
                        continue;
                    }

                    $out[] = [
                        'name' => $name,
                        'phones' => $phones,
                    ];
                }
            });

        return $out;
    }

    private function escape(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $value);
    }
}
