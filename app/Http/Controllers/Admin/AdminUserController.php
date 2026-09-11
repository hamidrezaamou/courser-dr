<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    /**
     * @return list<string>
     */
    private function staffRoles(): array
    {
        return [User::ROLE_ADMIN, User::ROLE_DOCTOR, User::ROLE_ASSISTANT];
    }

    public function index(Request $request): View
    {
        $role = $request->string('role')->toString();
        $q = trim($request->string('q')->toString());

        $users = User::query()
            ->whereIn('role', $this->staffRoles())
            ->when($role !== '' && in_array($role, $this->staffRoles(), true), fn ($query) => $query->where('role', $role))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('national_code', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'doctor' THEN 2 WHEN 'assistant' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'q' => $q,
            'adminSection' => 'users',
            'settingsSection' => 'users',
            'roleLabels' => $this->roleLabels(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => User::ROLE_ASSISTANT]),
            'adminSection' => 'users',
            'settingsSection' => 'users',
            'roleLabels' => $this->roleLabels(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'] ?: null,
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        ActivityLogger::log($user, 'created', null, [
            'name' => $user->name,
            'role' => $user->role,
            'national_code' => $user->national_code,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'کاربر «'.$user->name.'» ساخته شد.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        if (! in_array($user->role, $this->staffRoles(), true)) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'فقط کاربران پرسنل از این بخش ویرایش می‌شوند.');
        }

        return view('admin.users.form', [
            'user' => $user,
            'adminSection' => 'users',
            'settingsSection' => 'users',
            'roleLabels' => $this->roleLabels(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! in_array($user->role, $this->staffRoles(), true)) {
            abort(404);
        }

        $validated = $this->validateUser($request, $user);
        $old = $user->only(['name', 'national_code', 'mobile', 'role']);

        $payload = [
            'name' => $validated['name'],
            'national_code' => $validated['national_code'],
            'mobile' => $validated['mobile'] ?: null,
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        // Prevent demoting/removing the last admin (including self).
        if ($user->role === User::ROLE_ADMIN && $validated['role'] !== User::ROLE_ADMIN) {
            $otherAdmins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('id', '!=', $user->id)
                ->count();
            if ($otherAdmins === 0) {
                return back()->withInput()->with('error', 'نمی‌توان آخرین مدیر سیستم را از نقش ادمین خارج کرد.');
            }
        }

        $user->update($payload);

        ActivityLogger::log($user, 'updated', $old, $user->only(['name', 'national_code', 'mobile', 'role']));

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'کاربر «'.$user->name.'» به‌روز شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (! in_array($user->role, $this->staffRoles(), true)) {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        if ($user->role === User::ROLE_ADMIN) {
            $otherAdmins = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('id', '!=', $user->id)
                ->count();
            if ($otherAdmins === 0) {
                return back()->with('error', 'نمی‌توان آخرین مدیر سیستم را حذف کرد.');
            }
        }

        $snapshot = $user->only(['name', 'national_code', 'mobile', 'role']);
        ActivityLogger::log($user, 'deleted', $snapshot, null);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'کاربر حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:6', 'confirmed']
            : ['required', 'string', 'min:6', 'confirmed'];

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'national_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'national_code')->ignore($user?->id),
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'mobile')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in($this->staffRoles())],
            'password' => $passwordRules,
        ], [
            'name.required' => 'نام الزامی است.',
            'national_code.required' => 'کد ملی الزامی است.',
            'national_code.unique' => 'این کد ملی قبلاً ثبت شده.',
            'mobile.unique' => 'این موبایل قبلاً ثبت شده.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'password.min' => 'رمز عبور حداقل ۶ کاراکتر باشد.',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function roleLabels(): array
    {
        return [
            User::ROLE_ADMIN => 'مدیر',
            User::ROLE_DOCTOR => 'پزشک',
            User::ROLE_ASSISTANT => 'منشی / دستیار',
        ];
    }
}
