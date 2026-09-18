<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminCompanyController extends Controller
{
    public function index(): Response
    {
        $this->authorizeSuperAdmin();
        $filters = request()->only(['company_search', 'company_status', 'user_search', 'user_role']);

        return Inertia::render('admin/Companies', [
            'companies' => Company::query()
                ->withCount(['users', 'milkBatches'])
                ->when($filters['company_search'] ?? null, function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('contact_email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->when(($filters['company_status'] ?? null) === 'active', fn ($query) => $query->whereNull('archived_at'))
                ->when(($filters['company_status'] ?? null) === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
                ->latest()
                ->paginate(10, ['*'], 'companies_page')
                ->withQueryString()
                ->through(fn (Company $company) => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'contact_email' => $company->contact_email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'archived_at' => $company->archived_at?->toDayDateTimeString(),
                    'users_count' => $company->users_count,
                    'milk_batches_count' => $company->milk_batches_count,
                ]),
            'roles' => ['company_admin', 'tester'],
            'platformRoles' => ['super_admin', 'company_admin', 'tester'],
            'users' => User::query()
                ->with('company')
                ->withCount('milkBatches')
                ->when($filters['user_search'] ?? null, function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when(in_array($filters['user_role'] ?? null, ['super_admin', 'company_admin', 'tester'], true), fn ($query) => $query->where('role', $filters['user_role']))
                ->latest()
                ->paginate(10, ['*'], 'users_page')
                ->withQueryString()
                ->through(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'company_id' => $user->company_id,
                    'company' => $user->company?->name,
                    'is_active' => $user->is_active,
                    'force_password_change' => $user->force_password_change,
                    'milk_batches_count' => $user->milk_batches_count,
                    'created_at' => $user->created_at?->toDayDateTimeString(),
                ]),
            'auditLogs' => AuditLog::query()
                ->with('actor')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'actor' => $log->actor?->name ?? 'System',
                    'action' => $log->action,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toDayDateTimeString(),
                ]),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $company = Company::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'contact_email' => $data['contact_email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        User::create([
            'company_id' => $company->id,
            'role' => 'company_admin',
            'is_active' => true,
            'force_password_change' => true,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
        ]);

        $this->audit('company.created', $company, ['name' => $company->name]);

        return back();
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $company->update([
            ...$data,
            'slug' => $company->name === $data['name']
                ? $company->slug
                : Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
        ]);

        $this->audit('company.updated', $company, ['name' => $company->name]);

        return back();
    }

    public function archive(Company $company): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $company->forceFill(['archived_at' => now()])->save();
        $company->users()->update(['is_active' => false]);

        $this->audit('company.archived', $company, ['name' => $company->name]);

        return back();
    }

    public function restore(Company $company): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $company->forceFill(['archived_at' => null])->save();

        $this->audit('company.restored', $company, ['name' => $company->name]);

        return back();
    }

    public function storePlatformUser(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id', 'required_unless:role,super_admin'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'company_admin', 'tester'])],
        ]);

        User::create([
            'company_id' => $data['role'] === 'super_admin' ? null : $data['company_id'],
            'role' => $data['role'],
            'is_active' => true,
            'force_password_change' => true,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->audit('user.created', null, ['email' => $data['email'], 'role' => $data['role']]);

        return back();
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id', 'required_unless:role,super_admin'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(['super_admin', 'company_admin', 'tester'])],
        ]);

        $user->update([
            'company_id' => $data['role'] === 'super_admin' ? null : $data['company_id'],
            'role' => $data['role'],
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $this->audit('user.updated', $user, ['email' => $user->email, 'role' => $user->role]);

        return back();
    }

    public function resetUserPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'force_password_change' => true,
        ])->save();

        $this->audit('user.password_reset', $user, ['email' => $user->email]);

        return back();
    }

    public function deactivateUser(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        abort_if($user->id === request()->user()->id, 422, 'You cannot deactivate your own admin account.');

        $user->forceFill(['is_active' => false])->save();

        $this->audit('user.deactivated', $user, ['email' => $user->email]);

        return back();
    }

    public function activateUser(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $user->forceFill(['is_active' => true])->save();

        $this->audit('user.activated', $user, ['email' => $user->email]);

        return back();
    }

    public function destroyUser(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        abort_if($user->id === request()->user()->id, 422, 'You cannot delete your own admin account.');
        abort_if($user->milkBatches()->exists(), 422, 'Deactivate users who already have prediction records.');

        $email = $user->email;
        $user->delete();

        $this->audit('user.deleted', null, ['email' => $email]);

        return back();
    }

    public function storeUser(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['company_admin', 'tester'])],
        ]);

        User::create([
            'company_id' => $company->id,
            'role' => $data['role'],
            'is_active' => true,
            'force_password_change' => true,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->audit('company_user.created', $company, ['email' => $data['email'], 'role' => $data['role']]);

        return back();
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);
    }

    private function audit(string $action, Company|User|null $model = null, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => request()->user()?->id,
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->id,
            'metadata' => $metadata,
        ]);
    }
}
