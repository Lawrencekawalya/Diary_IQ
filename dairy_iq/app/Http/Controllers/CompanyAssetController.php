<?php

namespace App\Http\Controllers;

use App\Models\CollectionCenter;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyAssetController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id !== null, 403);

        $companyId = (int) $user->company_id;

        return Inertia::render('company/Assets', [
            'vehicles' => Vehicle::query()
                ->where('company_id', $companyId)
                ->withCount('milkBatches')
                ->latest()
                ->get()
                ->map(fn (Vehicle $v) => [
                    'id' => $v->id,
                    'plate_number' => $v->plate_number,
                    'driver_name' => $v->driver_name,
                    'is_active' => $v->is_active,
                    'milk_batches_count' => $v->milk_batches_count,
                    'created_at' => $v->created_at?->toDayDateTimeString(),
                ]),
            'collectionCenters' => CollectionCenter::query()
                ->where('company_id', $companyId)
                ->withCount('milkBatches')
                ->latest()
                ->get()
                ->map(fn (CollectionCenter $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'district' => $c->district,
                    'is_active' => $c->is_active,
                    'milk_batches_count' => $c->milk_batches_count,
                    'created_at' => $c->created_at?->toDayDateTimeString(),
                ]),
            'districts' => config('dairyiq.uganda_districts', []),
        ]);
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id !== null, 403);

        $companyId = (int) $user->company_id;

        $request->merge([
            'plate_number' => strtoupper(trim((string) $request->input('plate_number'))),
        ]);

        $data = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'plate_number')->where('company_id', $companyId),
            ],
            'driver_name' => ['nullable', 'string', 'max:255'],
        ]);

        Vehicle::create([
            'company_id' => $companyId,
            'plate_number' => $data['plate_number'],
            'driver_name' => ! empty($data['driver_name']) ? trim((string) $data['driver_name']) : null,
            'is_active' => true,
        ]);

        return redirect()->route('company.assets.index')->with('status', 'Vehicle added successfully.');
    }

    public function updateVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id === $vehicle->company_id, 403);

        $companyId = (int) $user->company_id;

        $request->merge([
            'plate_number' => strtoupper(trim((string) $request->input('plate_number'))),
        ]);

        $data = $request->validate([
            'plate_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'plate_number')
                    ->where('company_id', $companyId)
                    ->ignore($vehicle->id),
            ],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $vehicle->update([
            'plate_number' => $data['plate_number'],
            'driver_name' => ! empty($data['driver_name']) ? trim((string) $data['driver_name']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()->route('company.assets.index')->with('status', 'Vehicle updated successfully.');
    }

    public function destroyVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id === $vehicle->company_id, 403);

        if ($vehicle->milkBatches()->exists()) {
            $vehicle->update(['is_active' => false]);

            return redirect()->route('company.assets.index')->with('status', 'Vehicle has associated milk batches, so it was deactivated instead of deleted.');
        }

        $vehicle->delete();

        return redirect()->route('company.assets.index')->with('status', 'Vehicle deleted successfully.');
    }

    public function storeCollectionCenter(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id !== null, 403);

        $companyId = (int) $user->company_id;

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'district' => $request->filled('district') ? trim((string) $request->input('district')) : null,
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collection_centers', 'name')->where('company_id', $companyId),
            ],
            'district' => ['nullable', 'string', 'max:255'],
        ]);

        CollectionCenter::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'district' => $data['district'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('company.assets.index')->with('status', 'Collection center added successfully.');
    }

    public function updateCollectionCenter(Request $request, CollectionCenter $collectionCenter): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id === $collectionCenter->company_id, 403);

        $companyId = (int) $user->company_id;

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'district' => $request->filled('district') ? trim((string) $request->input('district')) : null,
        ]);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collection_centers', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($collectionCenter->id),
            ],
            'district' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $collectionCenter->update([
            'name' => $data['name'],
            'district' => $data['district'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()->route('company.assets.index')->with('status', 'Collection center updated successfully.');
    }

    public function destroyCollectionCenter(Request $request, CollectionCenter $collectionCenter): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isCompanyAdmin() && $user->company_id === $collectionCenter->company_id, 403);

        if ($collectionCenter->milkBatches()->exists()) {
            $collectionCenter->update(['is_active' => false]);

            return redirect()->route('company.assets.index')->with('status', 'Collection center has associated milk batches, so it was deactivated instead of deleted.');
        }

        $collectionCenter->delete();

        return redirect()->route('company.assets.index')->with('status', 'Collection center deleted successfully.');
    }
}
