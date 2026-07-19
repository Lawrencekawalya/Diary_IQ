<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    public function edit(): Response
    {
        $user = request()->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id !== null, 403);

        $company = $user->company;

        return Inertia::render('company/Settings', [
            'company' => [
                'name' => $company?->name,
                'contact_email' => $company?->contact_email,
                'phone' => $company?->phone,
                'address' => $company?->address,
            ],
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $request->user()->company->update($request->validated());

        return back();
    }
}
