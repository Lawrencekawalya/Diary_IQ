<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyUserController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user?->canManageCompanyUsers(), 403);

        return Inertia::render('company/Users', [
            'users' => User::query()
                ->where('company_id', $user->company_id)
                ->latest()
                ->get()
                ->map(fn (User $companyUser) => [
                    'id' => $companyUser->id,
                    'name' => $companyUser->name,
                    'email' => $companyUser->email,
                    'role' => $companyUser->role,
                    'created_at' => $companyUser->created_at?->toDayDateTimeString(),
                ]),
            'roles' => ['company_admin', 'tester'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageCompanyUsers(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['company_admin', 'tester'])],
        ]);

        User::create([
            'company_id' => $user->company_id,
            'role' => $data['role'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return back();
    }
}
