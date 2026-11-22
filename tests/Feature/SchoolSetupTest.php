<?php

declare(strict_types=1);

use App\Domain\Fees\Models\FeeHead;
use App\Domain\School\Actions\SetupSchoolAction;
use App\Domain\School\Models\School;
use App\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setup school action creates school, academic year, standard classes and sections', function (): void {
    $action = app(SetupSchoolAction::class);

    $school = $action->execute([
        'name' => 'Rose Bud Kindergarten',
        'name_bn' => 'রোজ বাড কিন্ডারগার্টেন',
        'phone' => '01811223344',
        'address' => 'Mirpur-10, Dhaka',
        'address_bn' => 'মিরপুর-১০, ঢাকা',
        'owner_name' => 'Tanvir Ahmed',
        'owner_email' => 'tanvir@rosebud.test',
        'owner_password' => 'secret1234',
        'academic_year_name' => '2026',
    ]);

    expect($school)->toBeInstanceOf(School::class)
        ->and($school->name)->toBe('Rose Bud Kindergarten')
        ->and($school->name_bn)->toBe('রোজ বাড কিন্ডারগার্টেন');

    // Verify owner user was created and assigned owner role
    $owner = User::where('school_id', $school->id)->where('email', 'tanvir@rosebud.test')->first();
    expect($owner)->not->toBeNull()
        ->and($owner->role)->toBe(UserRole::Owner);

    // Reads need a school context: act as the new owner
    $this->actingAs($owner);

    // Verify academic year was created
    expect($school->academicYears)->toHaveCount(1)
        ->and($school->currentAcademicYear()->name)->toBe('2026');

    // Verify 8 standard classes were created (Play to Class 5)
    expect($school->classes)->toHaveCount(8);

    // Verify sections were created for each class
    expect($school->sections)->toHaveCount(16); // 8 classes * 2 sections (A and B)

    // Verify default fee heads were created
    expect($school->hasMany(FeeHead::class)->count())->toBe(3);
});
