<?php

namespace App\Http\Controllers;

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

        return Inertia::render('admin/Companies', [
            'companies' => Company::query()
                ->withCount(['users', 'milkBatches'])
                ->latest()
                ->get()
                ->map(fn (Company $company) => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'contact_email' => $company->contact_email,
                    'phone' => $company->phone,
                    'address' => $company->address,
                    'users_count' => $company->users_count,
                    'milk_batches_count' => $company->milk_batches_count,
                ]),
            'roles' => ['company_admin', 'tester'],
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
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
        ]);

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
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return back();
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);
    }
}
