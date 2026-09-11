<?php

namespace App\Http\Controllers;

use App\Models\SurgeryProgramGroup;
use App\Models\SurgeryProgramGroupItem;
use App\Models\SurgerySubtype;
use App\Models\SurgeryType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SurgeryProgramGroupController extends Controller
{
    public function index(): View
    {
        SurgeryProgramGroup::ensureTables();

        $types = SurgeryType::query()->ordered()->with(['subtypes' => fn ($q) => $q->ordered()])->get();
        $groups = SurgeryProgramGroup::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['items.surgeryType', 'items.surgerySubtype'])
            ->get();

        return view('surgery-program-groups.index', [
            'types' => $types,
            'groups' => $groups,
            'settingsSection' => 'program-groups',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        SurgeryProgramGroup::ensureTables();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'members' => ['nullable', 'array'],
            'members.*' => ['string', 'max:40'],
        ]);

        $members = $this->parseMembers($validated['members'] ?? [], null);

        $max = (int) SurgeryProgramGroup::query()->max('sort_order');
        $group = SurgeryProgramGroup::query()->create([
            'name' => trim($validated['name']),
            'is_active' => true,
            'sort_order' => $max + 1,
        ]);
        $group->syncMembers($members);

        return redirect()
            ->route('settings.surgery-program-groups')
            ->with('success', 'گروه برنامه «'.$group->name.'» ساخته شد.');
    }

    public function update(Request $request, SurgeryProgramGroup $surgeryProgramGroup): RedirectResponse
    {
        SurgeryProgramGroup::ensureTables();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*' => ['string', 'max:40'],
        ]);

        $members = $this->parseMembers($validated['members'] ?? [], (int) $surgeryProgramGroup->id);

        $surgeryProgramGroup->update([
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ]);
        $surgeryProgramGroup->syncMembers($members);

        return redirect()
            ->route('settings.surgery-program-groups')
            ->with('success', 'گروه برنامه به‌روزرسانی شد.');
    }

    public function destroy(SurgeryProgramGroup $surgeryProgramGroup): RedirectResponse
    {
        SurgeryProgramGroup::ensureTables();
        $name = $surgeryProgramGroup->name;
        $surgeryProgramGroup->items()->delete();
        $surgeryProgramGroup->days()->delete();
        $surgeryProgramGroup->delete();
        SurgeryProgramGroup::flushCache();

        return redirect()
            ->route('settings.surgery-program-groups')
            ->with('success', 'گروه «'.$name.'» حذف شد.');
    }

    /**
     * @param  list<string>  $raw
     * @return list<array{surgery_type_id: int, surgery_subtype_id: int|null}>
     */
    private function parseMembers(array $raw, ?int $ignoreGroupId): array
    {
        $wholeTypes = [];
        $subtypes = [];

        foreach ($raw as $token) {
            $token = trim((string) $token);
            if (str_starts_with($token, 'type:')) {
                $id = (int) substr($token, 5);
                if ($id > 0) {
                    $wholeTypes[$id] = true;
                }
            } elseif (str_starts_with($token, 'subtype:')) {
                $id = (int) substr($token, 8);
                if ($id > 0) {
                    $subtypes[$id] = true;
                }
            }
        }

        $out = [];
        foreach (array_keys($wholeTypes) as $typeId) {
            $this->assertAvailable((int) $typeId, null, $ignoreGroupId);
            $out[] = ['surgery_type_id' => (int) $typeId, 'surgery_subtype_id' => null];
        }

        if ($subtypes !== []) {
            $rows = SurgerySubtype::query()->whereIn('id', array_keys($subtypes))->get();
            foreach ($rows as $subtype) {
                $typeId = (int) $subtype->surgery_type_id;
                if (isset($wholeTypes[$typeId])) {
                    continue;
                }
                $this->assertAvailable($typeId, (int) $subtype->id, $ignoreGroupId);
                $out[] = [
                    'surgery_type_id' => $typeId,
                    'surgery_subtype_id' => (int) $subtype->id,
                ];
            }
        }

        if ($out === []) {
            throw ValidationException::withMessages([
                'members' => 'حداقل یک نوع عمل یا زیرگروه را به این گروه اضافه کنید.',
            ]);
        }

        return $out;
    }

    private function assertAvailable(int $typeId, ?int $subtypeId, ?int $ignoreGroupId): void
    {
        $query = SurgeryProgramGroupItem::query()->where('surgery_type_id', $typeId);
        if ($ignoreGroupId) {
            $query->where('surgery_program_group_id', '!=', $ignoreGroupId);
        }

        $conflict = $query->get()->first(function (SurgeryProgramGroupItem $item) use ($subtypeId) {
            if ($item->surgery_subtype_id === null) {
                return true;
            }
            if ($subtypeId === null) {
                return true;
            }

            return (int) $item->surgery_subtype_id === $subtypeId;
        });

        if (! $conflict) {
            return;
        }

        $conflict->loadMissing('group');
        $groupName = $conflict->group?->name ?: 'گروه دیگر';
        $label = $subtypeId
            ? (SurgerySubtype::query()->find($subtypeId)?->name ?: 'این زیرگروه')
            : (SurgeryType::query()->find($typeId)?->name ?: 'این نوع عمل');

        throw ValidationException::withMessages([
            'members' => "«{$label}» الان در گروه «{$groupName}» است. هر نوع/زیرگروه فقط در یک گروه برنامه می‌تواند باشد.",
        ]);
    }
}
