<?php

use App\Models\Company;
use App\Models\MilkBatch;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows company scoped analytics data', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->for($company)->create();

    MilkBatch::factory()->for($company)->create([
        'batch_number' => 'DASH-HIGH-001',
        'prediction' => 'High',
        'district' => 'Mbarara',
        'liters_collected' => 100,
        'created_at' => now()->subDays(2),
    ]);
    MilkBatch::factory()->for($company)->create([
        'batch_number' => 'DASH-MEDIUM-001',
        'prediction' => 'Medium',
        'district' => 'Mbarara',
        'liters_collected' => 50,
        'created_at' => now()->subDay(),
    ]);
    MilkBatch::factory()->for($company)->create([
        'batch_number' => 'DASH-LOW-001',
        'prediction' => 'Low',
        'district' => 'Kampala',
        'liters_collected' => 20,
        'created_at' => now(),
    ]);
    MilkBatch::factory()->for($otherCompany)->create([
        'batch_number' => 'DASH-HIDDEN-001',
        'prediction' => 'High',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('summary.total', 3)
            ->where('summary.High', 1)
            ->where('summary.Medium', 1)
            ->where('summary.Low', 1)
            ->has('qualityTrend', 3)
            ->where('qualityTrend.0.batch_number', 'DASH-HIGH-001')
            ->where('qualityTrend.0.score', 3)
            ->has('districtAnalytics', 2)
            ->where('districtAnalytics.0.district', 'Mbarara')
            ->where('districtAnalytics.0.total', 2)
            ->where('districtAnalytics.0.liters', 150)
            ->has('latestBatches', 3)
            ->where('latestBatches.0.batch_number', 'DASH-LOW-001')
        );
});
