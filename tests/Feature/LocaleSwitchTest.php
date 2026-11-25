<?php

declare(strict_types=1);

use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Support\Enums\UserRole;

test('site defaults to Bangla with the Bangla html lang attribute', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('lang="bn"', false);
});

test('visitor can switch the site to English and it is remembered', function (): void {
    $this->from(route('login'))
        ->get(route('locale.switch', 'en'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('locale', 'en');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('lang="en"', false);
});

test('visitor can switch back to Bangla', function (): void {
    $this->get(route('locale.switch', 'en'));
    $this->get(route('locale.switch', 'bn'))->assertSessionHas('locale', 'bn');

    $this->get(route('login'))->assertSee('lang="bn"', false);
});

test('unsupported locales are rejected', function (): void {
    $this->get('/language/fr')->assertNotFound();
});

test('admission page renders in English after switching language', function (): void {
    $school = School::factory()->create();
    SchoolClass::factory()->create(['school_id' => $school->id]);
    $owner = schoolUser(UserRole::Owner, $school);

    $this->actingAs($owner)->get(route('locale.switch', 'en'));

    $this->actingAs($owner)->get(route('students.create'))
        ->assertOk()
        ->assertSee('New student admission')
        ->assertSee('BDRIS birth registration number')
        ->assertSee('lang="en"', false)
        ->assertDontSee('নতুন শিক্ষার্থী ভর্তি');
});

test('admission page renders in Bangla by default', function (): void {
    $owner = schoolUser(UserRole::Owner);

    $this->actingAs($owner)->get(route('students.create'))
        ->assertOk()
        ->assertSee('নতুন শিক্ষার্থী ভর্তি')
        ->assertSee('জন্ম নিবন্ধন নম্বর (BDRIS)');
});
