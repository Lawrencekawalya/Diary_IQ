<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcedPasswordChangeController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('auth/ForcePasswordChange');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'force_password_change' => false,
        ])->save();

        return redirect()->route('dashboard');
    }
}
