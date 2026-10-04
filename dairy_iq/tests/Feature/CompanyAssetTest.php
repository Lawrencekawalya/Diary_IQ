<?php

use App\Models\CollectionCenter;
use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('company admin can view company assets page with their vehicles and centers', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $vehicle = Vehicle::factory()->for($company)->create(['plate_number' => 'UBA 100A']);
    $center = CollectionCenter::factory()->for($company)->create(['name' => 'Mbarara Center']);

    Vehicle::factory()->for($otherCompany)->create(['plate_number' => 'UBB 200B']);
    CollectionCenter::factory()->for($otherCompany)->create(['name' => 'Other Center']);

    $this->actingAs($admin)
        ->get(route('company.assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('company/Assets')
            ->has('vehicles', 1)
            ->where('vehicles.0.plate_number', 'UBA 100A')
            ->has('collectionCenters', 1)
            ->where('collectionCenters.0.name', 'Mbarara Center')
            ->has('districts')
        );
});

test('tester cannot access company assets page', function () {
    $company = Company::factory()->create();
    $tester = User::factory()->for($company)->create(['role' => 'tester']);

    $this->actingAs($tester)
        ->get(route('company.assets.index'))
        ->assertForbidden();
});

test('super admin cannot access company assets page', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'company_id' => null]);

    $this->actingAs($superAdmin)
        ->get(route('company.assets.index'))
        ->assertForbidden();
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('company.assets.index'))
        ->assertRedirect(route('login'));
});

test('company admin can create a new vehicle', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $response = $this->actingAs($admin)
        ->post(route('company.vehicles.store'), [
            'plate_number' => 'uba 999z',
            'driver_name' => 'David Mukasa',
        ]);

    $response->assertRedirect(route('company.assets.index'));

    $vehicle = Vehicle::where('company_id', $company->id)->firstOrFail();
    expect($vehicle->plate_number)->toBe('UBA 999Z')
        ->and($vehicle->driver_name)->toBe('David Mukasa')
        ->and($vehicle->is_active)->toBeTrue();
});

test('company admin cannot create vehicle with duplicate plate number in same company', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    Vehicle::factory()->for($company)->create(['plate_number' => 'UBA 123A']);

    $this->actingAs($admin)
        ->post(route('company.vehicles.store'), [
            'plate_number' => 'uba 123a',
            'driver_name' => 'Someone',
        ])
        ->assertSessionHasErrors(['plate_number']);

    expect(Vehicle::where('company_id', $company->id)->count())->toBe(1);
});

test('different companies can have vehicle with same plate number', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $admin2 = User::factory()->for($company2)->create(['role' => 'company_admin']);

    Vehicle::factory()->for($company1)->create(['plate_number' => 'UBA 123A']);

    $this->actingAs($admin2)
        ->post(route('company.vehicles.store'), [
            'plate_number' => 'UBA 123A',
            'driver_name' => 'Another Driver',
        ])
        ->assertSessionHasNoErrors();

    expect(Vehicle::where('plate_number', 'UBA 123A')->count())->toBe(2);
});

test('company admin can update an existing vehicle', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $vehicle = Vehicle::factory()->for($company)->create([
        'plate_number' => 'UBA 111A',
        'driver_name' => 'Old Driver',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('company.vehicles.update', $vehicle), [
            'plate_number' => 'UBA 111B',
            'driver_name' => 'New Driver',
            'is_active' => false,
        ])
        ->assertRedirect(route('company.assets.index'));

    $vehicle->refresh();
    expect($vehicle->plate_number)->toBe('UBA 111B')
        ->and($vehicle->driver_name)->toBe('New Driver')
        ->and($vehicle->is_active)->toBeFalse();
});

test('company admin cannot update vehicle of another company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $otherVehicle = Vehicle::factory()->for($otherCompany)->create(['plate_number' => 'UBX 999X']);

    $this->actingAs($admin)
        ->put(route('company.vehicles.update', $otherVehicle), [
            'plate_number' => 'UBX 999Y',
            'is_active' => true,
        ])
        ->assertForbidden();

    expect($otherVehicle->fresh()->plate_number)->toBe('UBX 999X');
});

test('company admin can delete an unused vehicle', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $vehicle = Vehicle::factory()->for($company)->create();

    $this->actingAs($admin)
        ->delete(route('company.vehicles.destroy', $vehicle))
        ->assertRedirect(route('company.assets.index'));

    expect(Vehicle::find($vehicle->id))->toBeNull();
});

test('company admin deleting vehicle with existing milk batches deactivates it', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $vehicle = Vehicle::factory()->for($company)->create(['is_active' => true]);
    MilkBatch::factory()->for($company)->create(['vehicle_id' => $vehicle->id]);

    $this->actingAs($admin)
        ->delete(route('company.vehicles.destroy', $vehicle))
        ->assertRedirect(route('company.assets.index'))
        ->assertSessionHas('status');

    expect(Vehicle::find($vehicle->id))->not->toBeNull()
        ->and($vehicle->fresh()->is_active)->toBeFalse();
});

test('company admin can create a collection center', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    $response = $this->actingAs($admin)
        ->post(route('company.collection-centers.store'), [
            'name' => 'Kiruhura Bulk Center',
            'district' => 'Kiruhura',
        ]);

    $response->assertRedirect(route('company.assets.index'));

    $center = CollectionCenter::where('company_id', $company->id)->firstOrFail();
    expect($center->name)->toBe('Kiruhura Bulk Center')
        ->and($center->district)->toBe('Kiruhura')
        ->and($center->is_active)->toBeTrue();
});

test('company admin cannot create duplicate collection center name in same company', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);

    CollectionCenter::factory()->for($company)->create(['name' => 'Main Center']);

    $this->actingAs($admin)
        ->post(route('company.collection-centers.store'), [
            'name' => 'Main Center',
            'district' => 'Kazo',
        ])
        ->assertSessionHasErrors(['name']);

    expect(CollectionCenter::where('company_id', $company->id)->count())->toBe(1);
});

test('company admin can update collection center', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $center = CollectionCenter::factory()->for($company)->create([
        'name' => 'Old Center Name',
        'district' => 'Kazo',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('company.collection-centers.update', $center), [
            'name' => 'Updated Center Name',
            'district' => 'Kiruhura',
            'is_active' => false,
        ])
        ->assertRedirect(route('company.assets.index'));

    $center->refresh();
    expect($center->name)->toBe('Updated Center Name')
        ->and($center->district)->toBe('Kiruhura')
        ->and($center->is_active)->toBeFalse();
});

test('company admin deleting collection center with existing milk batches deactivates it', function () {
    $company = Company::factory()->create();
    $admin = User::factory()->for($company)->create(['role' => 'company_admin']);
    $center = CollectionCenter::factory()->for($company)->create(['is_active' => true]);
    MilkBatch::factory()->for($company)->create(['collection_center_id' => $center->id]);

    $this->actingAs($admin)
        ->delete(route('company.collection-centers.destroy', $center))
        ->assertRedirect(route('company.assets.index'))
        ->assertSessionHas('status');

    expect(CollectionCenter::find($center->id))->not->toBeNull()
        ->and($center->fresh()->is_active)->toBeFalse();
});

test('tester cannot perform asset mutations', function () {
    $company = Company::factory()->create();
    $tester = User::factory()->for($company)->create(['role' => 'tester']);

    $this->actingAs($tester)
        ->post(route('company.vehicles.store'), [
            'plate_number' => 'UBA 555C',
        ])
        ->assertForbidden();

    $this->actingAs($tester)
        ->post(route('company.collection-centers.store'), [
            'name' => 'Illegal Center',
            'district' => 'Kazo',
        ])
        ->assertForbidden();
});
