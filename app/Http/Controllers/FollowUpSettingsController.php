<?php

namespace App\Http\Controllers;

use App\Models\FollowUpCatalogItem;
use App\Models\FollowUpTemplate;
use App\Models\FollowUpTemplateStep;
use App\Models\Hospital;
use App\Models\PatientFollowUp;
use App\Models\SurgeryAppointment;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use App\Models\User;
use App\Services\PatientFollowUpService;
use App\Support\FollowUpTiming;
use App\Support\PatientFollowUps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FollowUpSettingsController extends Controller
{
    public function index(): View
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        PatientFollowUps::ensureTables();

        $templates = FollowUpTemplate::query()
            ->with(['steps.assignee', 'hospital', 'surgeryType', 'surgerySubtype'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('follow-ups.settings', [
            'settingsSection' => 'follow-ups',
            'templates' => $templates,
            'kinds' => FollowUpCatalogItem::query()->forGroup(FollowUpCatalogItem::GROUP_KIND)->ordered()->get(),
            'methods' => FollowUpCatalogItem::query()->forGroup(FollowUpCatalogItem::GROUP_METHOD)->ordered()->get(),
            'outcomes' => FollowUpCatalogItem::query()->forGroup(FollowUpCatalogItem::GROUP_OUTCOME)->ordered()->get(),
            'hospitals' => Hospital::query()->orderBy('name')->get(['id', 'name']),
            'surgeryTypes' => SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get(),
            'staff' => User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT])
                ->orderBy('name')
                ->get(['id', 'name']),
            'units' => FollowUpTiming::units(),
            'directions' => FollowUpTiming::directions(),
            'referenceEvents' => FollowUpTiming::referenceEvents(),
            'canManage' => (bool) auth()->user()?->canManageSettings(),
        ]);
    }

    public function storeCatalog(Request $request): RedirectResponse
    {
        $this->assertManager();
        PatientFollowUps::ensureTables();

        $validated = $request->validate([
            'group' => ['required', 'in:kind,method,outcome'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'label' => ['required', 'string', 'max:120'],
        ]);

        FollowUpCatalogItem::query()->firstOrCreate(
            ['group' => $validated['group'], 'slug' => $validated['slug']],
            [
                'label' => $validated['label'],
                'sort_order' => (int) FollowUpCatalogItem::query()->where('group', $validated['group'])->max('sort_order') + 10,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'مورد به فهرست اضافه شد.');
    }

    public function updateCatalog(Request $request, FollowUpCatalogItem $catalogItem): RedirectResponse
    {
        $this->assertManager();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $catalogItem->update([
            'label' => $validated['label'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $validated['sort_order'] ?? $catalogItem->sort_order,
        ]);

        return back()->with('success', 'فهرست به‌روز شد.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->assertManager();
        PatientFollowUps::ensureTables();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'applies_to' => ['required', 'in:surgery,visit'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
        ]);

        if ($validated['applies_to'] === FollowUpTemplate::APPLIES_SURGERY && empty($validated['surgery_type_id'])) {
            return back()->withErrors(['surgery_type_id' => 'برای الگوی عمل، نوع عمل را انتخاب کنید.'])->withInput();
        }

        $subtypeId = $validated['surgery_subtype_id'] ?? null;
        $typeId = $validated['surgery_type_id'] ?? null;
        if ($subtypeId) {
            $subtype = SurgerySubtype::query()->findOrFail($subtypeId);
            if ((int) $subtype->surgery_type_id !== (int) $typeId) {
                return back()->withErrors(['surgery_subtype_id' => 'زیرگروه با نوع عمل هم‌خوان نیست.'])->withInput();
            }
        }

        if ($validated['applies_to'] === FollowUpTemplate::APPLIES_VISIT) {
            $validated['hospital_id'] = null;
            $typeId = null;
            $subtypeId = null;
        }

        FollowUpTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'applies_to' => $validated['applies_to'],
            'hospital_id' => $validated['hospital_id'] ?? null,
            'surgery_type_id' => $typeId,
            'surgery_subtype_id' => $subtypeId,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'الگوی پیگیری ساخته شد. مراحل را اضافه کنید.');
    }

    public function updateTemplate(Request $request, FollowUpTemplate $template): RedirectResponse
    {
        $this->assertManager();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'surgery_type_id' => ['nullable', 'integer', 'exists:surgery_types,id'],
            'surgery_subtype_id' => ['nullable', 'integer', 'exists:surgery_subtypes,id'],
        ]);

        $typeId = $validated['surgery_type_id'] ?? $template->surgery_type_id;
        $subtypeId = array_key_exists('surgery_subtype_id', $validated)
            ? $validated['surgery_subtype_id']
            : $template->surgery_subtype_id;
        $hospitalId = array_key_exists('hospital_id', $validated)
            ? $validated['hospital_id']
            : $template->hospital_id;
        if ($template->applies_to === FollowUpTemplate::APPLIES_VISIT) {
            $hospitalId = null;
            $typeId = null;
            $subtypeId = null;
        } elseif ($subtypeId) {
            $subtype = SurgerySubtype::query()->findOrFail($subtypeId);
            if ((int) $subtype->surgery_type_id !== (int) $typeId) {
                return back()->withErrors(['surgery_subtype_id' => 'زیرگروه با نوع عمل هم‌خوان نیست.']);
            }
        }

        $wasActive = (bool) $template->is_active;
        $willChangeBinding = (int) ($template->hospital_id ?? 0) !== (int) ($hospitalId ?? 0)
            || (int) ($template->surgery_type_id ?? 0) !== (int) ($typeId ?? 0)
            || (int) ($template->surgery_subtype_id ?? 0) !== (int) ($subtypeId ?? 0);
        $previousSurgeryIds = $willChangeBinding
            ? $template->followUps()
                ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
                ->open()
                ->whereNotNull('surgery_appointment_id')
                ->pluck('surgery_appointment_id')
                ->unique()
                ->values()
            : collect();

        $template->update([
            'name' => $validated['name'],
            'description' => array_key_exists('description', $validated)
                ? $validated['description']
                : $template->description,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $template->is_active,
            'hospital_id' => $hospitalId,
            'surgery_type_id' => $typeId,
            'surgery_subtype_id' => $subtypeId,
        ]);

        $bindingChanged = $template->wasChanged(['hospital_id', 'surgery_type_id', 'surgery_subtype_id']);
        if ($bindingChanged) {
            $template->followUps()
                ->where('source', PatientFollowUp::SOURCE_TEMPLATE)
                ->open()
                ->delete();
        }

        $msg = 'الگو ذخیره شد.';
        $service = app(PatientFollowUpService::class);
        if ($template->is_active && (! $wasActive || $bindingChanged)) {
            $applied = $service->applyTemplateToExisting(
                $template->fresh(['steps', 'surgeryType'])
            );
            if ($applied > 0) {
                $msg .= ' برای '.fa_digits($applied).' نوبت موجود پیگیری ساخته شد.';
            }
        }
        if ($bindingChanged && $previousSurgeryIds->isNotEmpty()) {
            SurgeryAppointment::query()
                ->whereIn('id', $previousSurgeryIds)
                ->get()
                ->each(fn (SurgeryAppointment $surgery) => $service->ensureForSurgery($surgery));
        }

        return back()->with('success', $msg);
    }

    public function destroyTemplate(FollowUpTemplate $template): RedirectResponse
    {
        $this->assertManager();

        $removed = 0;
        DB::transaction(function () use ($template, &$removed) {
            $removed = app(PatientFollowUpService::class)->purgeTemplateFollowUps($template);
            $template->steps()->delete();
            $template->delete();
        });

        $msg = 'الگو حذف شد.';
        if ($removed > 0) {
            $msg .= ' '.$removed.' پیگیری ساخته‌شده از این الگو هم پاک شد.';
        }

        return back()->with('success', $msg);
    }

    public function storeStep(Request $request, FollowUpTemplate $template): RedirectResponse
    {
        $this->assertManager();

        $validated = $this->validateStep($request);

        FollowUpTemplateStep::create([
            'template_id' => $template->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'kind' => $validated['kind'],
            'method' => $validated['method'],
            'offset_amount' => $validated['offset_amount'],
            'offset_unit' => $validated['offset_unit'],
            'offset_direction' => $validated['offset_direction'],
            'reference_event' => $validated['reference_event'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'sort_order' => (int) $template->steps()->max('sort_order') + 10,
        ]);

        $applied = app(PatientFollowUpService::class)->applyTemplateToExisting(
            $template->fresh(['steps', 'surgeryType'])
        );

        $msg = 'مرحله به الگو اضافه شد.';
        if ($applied > 0) {
            $msg .= ' برای '.fa_digits($applied).' نوبت عمل/ویزیت موجود هم پیگیری ساخته شد.';
        } else {
            $msg .= ' اگر نوبت عمل یا ویزیتِ منطبق از ۹۰ روز پیش به بعد باشد، پیگیری‌اش در داشبورد می‌آید.';
        }

        return back()->with('success', $msg);
    }

    public function updateStep(Request $request, FollowUpTemplateStep $step): RedirectResponse
    {
        $this->assertManager();

        $validated = $this->validateStep($request);
        $step->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'kind' => $validated['kind'],
            'method' => $validated['method'],
            'offset_amount' => $validated['offset_amount'],
            'offset_unit' => $validated['offset_unit'],
            'offset_direction' => $validated['offset_direction'],
            'reference_event' => $validated['reference_event'],
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $step->sort_order,
        ]);

        $applied = app(PatientFollowUpService::class)->applyTemplateToExisting(
            $step->template()->with(['steps', 'surgeryType'])->first()
        );

        $msg = 'مرحله ذخیره شد.';
        if ($applied > 0) {
            $msg .= ' '.fa_digits($applied).' پیگیری جدید برای نوبت‌های موجود ساخته شد.';
        }

        return back()->with('success', $msg);
    }

    public function destroyStep(FollowUpTemplateStep $step): RedirectResponse
    {
        $this->assertManager();

        $removed = 0;
        DB::transaction(function () use ($step, &$removed) {
            $removed = app(PatientFollowUpService::class)->purgeStepFollowUps($step);
            $step->delete();
        });

        $msg = 'مرحله حذف شد.';
        if ($removed > 0) {
            $msg .= ' '.$removed.' پیگیری مربوط به این مرحله هم پاک شد.';
        }

        return back()->with('success', $msg);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStep(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'kind' => ['required', 'string', 'max:64'],
            'method' => ['required', 'string', 'max:64'],
            'offset_amount' => ['required', 'integer', 'min:0', 'max:3650'],
            'offset_unit' => ['required', 'in:minute,hour,day,week,month'],
            'offset_direction' => ['required', 'in:before,after'],
            'reference_event' => ['required', 'in:surgery_date,appointment_date,visit_date,patient_created,custom'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    private function assertManager(): void
    {
        abort_unless(PatientFollowUps::isAvailable(), 404);
        abort_unless((bool) auth()->user()?->canManageSettings(), 403);
    }
}
