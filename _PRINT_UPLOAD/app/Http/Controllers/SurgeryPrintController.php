<?php

namespace App\Http\Controllers;

use App\Models\SurgeryAppointment;
use App\Support\PrintTemplateEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SurgeryPrintController extends Controller
{
    /** @var list<string> */
    public const TYPES = [
        'hospital',
        'anesthesiologist',
        'laboratory',
        'iol_master',
        'prescription',
    ];

    public function index(Request $request): View
    {
        $surgery = null;
        $id = (int) $request->input('id', 0);
        $hospitalId = $request->integer('hospital_id') ?: null;

        if ($id > 0) {
            $surgery = SurgeryAppointment::query()
                ->with(['hospital', 'patient', 'surgeryType', 'surgerySubtype'])
                ->find($id);
            if ($surgery?->hospital_id) {
                $hospitalId = (int) $surgery->hospital_id;
            }
        }

        $hospitals = \App\Models\Hospital::query()->orderBy('name')->get(['id', 'name']);

        if ($surgery) {
            $types = $this->typeCatalog($surgery);
        } else {
            $types = $this->typeCatalogForHospital($hospitalId);
        }

        return view('prints.index', [
            'surgery' => $surgery,
            'lookupId' => $id ?: null,
            'hospitalId' => $hospitalId,
            'types' => $types,
            'hospitals' => $hospitals,
            'selectedHospital' => $hospitalId
                ? $hospitals->firstWhere('id', $hospitalId)
                : null,
        ]);
    }

    public function hub(SurgeryAppointment $surgeryAppointment): View
    {
        $surgeryAppointment->loadMissing(['hospital', 'patient', 'surgeryType', 'surgerySubtype', 'checklist.items']);

        $checklistWarning = null;
        if (\App\Support\SurgeryChecklist::isAvailable()) {
            $checklist = $surgeryAppointment->checklist;
            if ($checklist) {
                $unchecked = $checklist->items->whereNull('checked_at')->count();
                if ($unchecked > 0) {
                    $checklistWarning = "هنوز {$unchecked} مورد از چک‌لیست عمل تیک نخورده است.";
                }
            }
        }

        return view('prints.hub', [
            'surgery' => $surgeryAppointment,
            'types' => $this->typeCatalog($surgeryAppointment),
            'checklistWarning' => $checklistWarning,
            'hospitals' => \App\Models\Hospital::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function printAll(SurgeryAppointment $surgeryAppointment): View
    {
        $surgeryAppointment->loadMissing(['hospital', 'patient', 'surgerySubtype', 'surgeryType']);

        $todayJalali = jalali(now(), 'Y/m/d');
        $surgeryJalali = jalali($surgeryAppointment->scheduled_date, 'Y/m/d');
        $surgeryTime = $surgeryAppointment->scheduled_time
            ? substr((string) $surgeryAppointment->scheduled_time, 0, 5)
            : '—';
        $vars = PrintTemplateEngine::varsForSurgery($surgeryAppointment, $todayJalali, $surgeryJalali, $surgeryTime);

        $shared = [
            'surgery' => $surgeryAppointment,
            'doctorName' => config('clinic.doctor_name'),
            'headerSpacer' => (int) config('clinic.print_header_spacer', 120),
            'todayJalali' => $todayJalali,
            'surgeryJalali' => $surgeryJalali,
            'surgeryTime' => $surgeryTime,
            'latestPrescription' => null,
            'checklistWarning' => null,
            'logoUrl' => \App\Support\ClinicBrand::logoDataUri(),
            'signatureUrl' => \App\Support\ClinicBrand::signatureDataUri(),
            'stampUrl' => \App\Support\ClinicBrand::stampDataUri(),
        ];

        $sheets = [];
        foreach ($this->typeCatalog($surgeryAppointment) as $type) {
            $key = $type['key'];
            $meta = PrintTemplateEngine::templateMeta($key);

            if ($meta['mode'] === 'overlay') {
                $sheets[] = [
                    'title' => $type['title'],
                    'html' => PrintTemplateEngine::renderOverlay($meta, $vars),
                ];

                continue;
            }

            if (PrintTemplateEngine::isCustomType($key) || PrintTemplateEngine::hasCustom($key)) {
                $body = PrintTemplateEngine::isCustomType($key)
                    ? PrintTemplateEngine::resolveBody($key)
                    : PrintTemplateEngine::customBody($key);
                $sheets[] = [
                    'title' => $type['title'],
                    'html' => PrintTemplateEngine::render($body, $vars),
                ];

                continue;
            }

            $sheetShared = array_merge($shared, [
                'qrDataUri' => \App\Support\QrImage::dataUri(
                    route('surgery-appointments.print', [$surgeryAppointment, $key]),
                    200
                ),
            ]);
            if ($key === 'prescription' && $surgeryAppointment->patient_id) {
                $sheetShared['latestPrescription'] = \App\Models\Prescription::query()
                    ->with('items')
                    ->where('patient_id', $surgeryAppointment->patient_id)
                    ->latest('id')
                    ->first();
            }

            $sheets[] = [
                'title' => $type['title'],
                'html' => view('prints.templates.'.$key, $sheetShared)->render(),
            ];
        }

        return view('prints.print-all', [
            'surgery' => $surgeryAppointment,
            'sheets' => $sheets,
            'doctorName' => $shared['doctorName'],
            'signatureUrl' => $shared['signatureUrl'],
            'headerSpacer' => $shared['headerSpacer'],
        ]);
    }

    public function show(SurgeryAppointment $surgeryAppointment, string $type): View
    {
        if (! PrintTemplateEngine::isKnownType($type)) {
            throw new NotFoundHttpException('نوع برگه چاپ نامعتبر است.');
        }

        $surgeryAppointment->loadMissing(['hospital', 'patient']);

        $latestPrescription = null;
        if ($type === 'prescription' && $surgeryAppointment->patient_id) {
            $latestPrescription = \App\Models\Prescription::query()
                ->with('items')
                ->where('patient_id', $surgeryAppointment->patient_id)
                ->latest('id')
                ->first();
        }

        $checklistWarning = null;
        if (\App\Support\SurgeryChecklist::isAvailable()) {
            $checklist = $surgeryAppointment->checklist ?? $surgeryAppointment->checklist()->with('items')->first();
            if ($checklist) {
                $unchecked = $checklist->items->whereNull('checked_at')->count();
                if ($unchecked > 0) {
                    $checklistWarning = "هنوز {$unchecked} مورد از چک‌لیست عمل تیک نخورده است.";
                }
            }
        }

        $todayJalali = jalali(now(), 'Y/m/d');
        $surgeryJalali = jalali($surgeryAppointment->scheduled_date, 'Y/m/d');
        $surgeryTime = $surgeryAppointment->scheduled_time
            ? substr((string) $surgeryAppointment->scheduled_time, 0, 5)
            : '—';

        $shared = [
            'surgery' => $surgeryAppointment,
            'doctorName' => config('clinic.doctor_name'),
            'headerSpacer' => (int) config('clinic.print_header_spacer', 120),
            'todayJalali' => $todayJalali,
            'surgeryJalali' => $surgeryJalali,
            'surgeryTime' => $surgeryTime,
            'latestPrescription' => $latestPrescription,
            'checklistWarning' => $checklistWarning,
            'logoUrl' => \App\Support\ClinicBrand::logoDataUri(),
            'signatureUrl' => \App\Support\ClinicBrand::signatureDataUri(),
            'stampUrl' => \App\Support\ClinicBrand::stampDataUri(),
            'qrDataUri' => \App\Support\QrImage::dataUri(
                route('surgery-appointments.print', [$surgeryAppointment, $type]),
                200
            ),
        ];

        $meta = PrintTemplateEngine::templateMeta($type);
        $vars = PrintTemplateEngine::varsForSurgery($surgeryAppointment, $todayJalali, $surgeryJalali, $surgeryTime);

        if ($meta['mode'] === 'overlay') {
            $shared['customHtml'] = PrintTemplateEngine::renderOverlay($meta, $vars);
            $shared['isOverlay'] = true;

            return view('prints.custom', $shared);
        }

        if (PrintTemplateEngine::isCustomType($type) || PrintTemplateEngine::hasCustom($type)) {
            $body = PrintTemplateEngine::isCustomType($type)
                ? PrintTemplateEngine::resolveBody($type)
                : PrintTemplateEngine::customBody($type);
            $shared['customHtml'] = PrintTemplateEngine::render(
                $body,
                $vars
            );

            return view('prints.custom', $shared);
        }

        return view('prints.templates.'.$type, $shared);
    }

    public function saveHospitalMeta(Request $request, SurgeryAppointment $surgeryAppointment): RedirectResponse
    {
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'print_note' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['hospital_id'])) {
            $surgeryAppointment->update(['hospital_id' => (int) $validated['hospital_id']]);
            $surgeryAppointment->refresh();
        }

        $hospital = $surgeryAppointment->hospital;
        if (! $hospital) {
            return back()->with('error', 'برای این نوبت بیمارستان ثبت نشده است.');
        }

        $hospital->update([
            'address' => $validated['address'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'print_note' => $validated['print_note'] ?: null,
        ]);

        return back()->with('success', 'بیمارستان و اطلاعات چاپ ذخیره شد.');
    }

    public function assignHospital(Request $request, SurgeryAppointment $surgeryAppointment): RedirectResponse
    {
        $validated = $request->validate([
            'hospital_id' => ['required', 'integer', 'exists:hospitals,id'],
        ]);

        $surgeryAppointment->update(['hospital_id' => (int) $validated['hospital_id']]);

        return back()->with('success', 'بیمارستان این برگه چاپ مشخص شد.');
    }

    /**
     * @return list<array{key:string,title:string,desc:string,icon:string,scope:string}>
     */
    private function typeCatalog(?SurgeryAppointment $surgery = null): array
    {
        return $this->mapTypeCatalog(PrintTemplateEngine::typesForSurgery($surgery));
    }

    /**
     * @return list<array{key:string,title:string,desc:string,icon:string,scope:string}>
     */
    private function typeCatalogForHospital(?int $hospitalId): array
    {
        return $this->mapTypeCatalog(PrintTemplateEngine::typesForHospital($hospitalId));
    }

    /**
     * @param  list<array{key:string,label:string,desc:string,scope?:string}>  $types
     * @return list<array{key:string,title:string,desc:string,icon:string,scope:string}>
     */
    private function mapTypeCatalog(array $types): array
    {
        $icons = [
            'hospital' => '🏥',
            'anesthesiologist' => '💉',
            'laboratory' => '🔬',
            'iol_master' => '👁',
            'prescription' => '📋',
        ];

        return array_map(function (array $type) use ($icons) {
            return [
                'key' => $type['key'],
                'title' => $type['label'],
                'desc' => $type['desc'],
                'icon' => $icons[$type['key']] ?? '📄',
                'scope' => $type['scope'] ?? 'عمومی',
            ];
        }, $types);
    }
}
