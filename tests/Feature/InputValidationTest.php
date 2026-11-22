<?php

declare(strict_types=1);

use App\Domain\Attendance\Models\AttendanceRecord;
use App\Domain\Fees\Models\Invoice;
use App\Domain\Notices\Models\Notice;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\School;
use App\Domain\School\Models\SchoolClass;
use App\Domain\School\Models\Section;
use App\Domain\Student\Models\Enrollment;
use App\Domain\Student\Models\Guardian;
use App\Domain\Student\Models\Student;
use App\Models\User;
use App\Support\Enums\UserRole;
use App\Support\Tenancy\CurrentSchool;
use Spatie\Activitylog\Models\Activity;

/**
 * Build a school with an owner, current academic year, class and section.
 *
 * @return array{school: School, owner: User, class: SchoolClass, section: Section}
 */
function schoolWithClass(): array
{
    $school = School::factory()->create();
    $owner = schoolUser(UserRole::Owner, $school);
    AcademicYear::factory()->create(['school_id' => $school->id, 'is_current' => true]);
    $class = SchoolClass::factory()->create(['school_id' => $school->id]);
    $section = Section::factory()->create(['school_id' => $school->id, 'class_id' => $class->id]);

    return compact('school', 'owner', 'class', 'section');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function admissionPayload(SchoolClass $class, Section $section, array $overrides = []): array
{
    return array_merge([
        'name' => 'Sabbir Hossain',
        'name_bn' => 'সাব্বির হোসেন',
        'dob' => '2020-02-10',
        'gender' => 'male',
        'guardian_name' => 'Belal Hossain',
        'guardian_relation' => 'father',
        'guardian_mobile' => '01799887766',
        'class_id' => $class->id,
        'section_id' => $section->id,
        'roll_no' => 5,
    ], $overrides);
}

describe('student admission input', function (): void {
    test('a valid admission is stored', function (): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section))
            ->assertRedirect(route('students.index'))
            ->assertSessionHasNoErrors();

        expect(Student::where('name', 'Sabbir Hossain')->exists())->toBeTrue();
    });

    test('a class from another school is rejected', function (): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();
        $foreign = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($foreign['class'], $foreign['section']))
            ->assertSessionHasErrors(['class_id', 'section_id']);

        expect(Student::count())->toBe(0);
    });

    test('a section that belongs to a different class is rejected', function (): void {
        ['owner' => $owner, 'school' => $school, 'class' => $class] = schoolWithClass();
        $otherClass = SchoolClass::factory()->create(['school_id' => $school->id, 'name' => 'Second class']);
        $otherSection = Section::factory()->create(['school_id' => $school->id, 'class_id' => $otherClass->id]);

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $otherSection))
            ->assertSessionHasErrors(['section_id']);
    });

    test('closed lists reject unknown values', function (string $field, string $value): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section, [$field => $value]))
            ->assertSessionHasErrors([$field]);
    })->with([
        'gender' => ['gender', 'robot'],
        'relation' => ['guardian_relation', '<script>'],
        'blood group' => ['blood_group', 'Z9'],
    ]);

    test('mobile numbers must be Bangladeshi mobile numbers', function (string $mobile): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section, ['guardian_mobile' => $mobile]))
            ->assertSessionHasErrors(['guardian_mobile']);
    })->with(['12345', '0171234567', '02123456789', 'abcdefghijk', '+8801212345678']);

    test('Bangla digits and +88 prefixes are normalised to a canonical mobile number', function (string $input): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section, ['guardian_mobile' => $input]))
            ->assertSessionHasNoErrors();

        expect(Guardian::first()->mobile)->toBe('01712345678');
    })->with(['০১৭১২৩৪৫৬৭৮', '+8801712345678', '8801712345678', ' 01712345678 ']);

    test('dates must be in the past and roll numbers positive integers', function (string $field, mixed $value): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section, [$field => $value]))
            ->assertSessionHasErrors([$field]);
    })->with([
        'future dob' => ['dob', '2999-01-01'],
        'absurd dob' => ['dob', '1800-01-01'],
        'negative roll' => ['roll_no', -3],
        'huge name' => ['name', str_repeat('a', 300)],
        'array name' => ['name', ['x']],
    ]);

    test('control characters are stripped and names are stored as typed', function (): void {
        ['owner' => $owner, 'class' => $class, 'section' => $section] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/students', admissionPayload($class, $section, ['name' => "Ra\x00him <b>Uddin</b>"]))
            ->assertSessionHasNoErrors();

        // Stored verbatim (minus control characters); escaping happens on output.
        expect(Student::first()->name)->toBe('Rahim <b>Uddin</b>');
    });
});

describe('search and filter input', function (): void {
    test('an array search term is a validation error, not a server error', function (): void {
        ['owner' => $owner] = schoolWithClass();

        $this->actingAs($owner)
            ->get('/students?search[]=x')
            ->assertSessionHasErrors(['search']);
    });

    test('LIKE wildcards in a search term are matched literally', function (): void {
        ['owner' => $owner, 'school' => $school] = schoolWithClass();
        Student::factory()->create(['school_id' => $school->id, 'name' => 'Rahim Uddin', 'name_bn' => null, 'student_code' => 'STU-1']);
        Student::factory()->create(['school_id' => $school->id, 'name' => 'Karim Mia', 'name_bn' => null, 'student_code' => 'STU-2']);

        $this->actingAs($owner)->get('/students?search=%25')
            ->assertOk()
            ->assertDontSee('Rahim Uddin')
            ->assertDontSee('Karim Mia');

        $this->actingAs($owner)->get('/students?search=Rah')
            ->assertOk()
            ->assertSee('Rahim Uddin')
            ->assertDontSee('Karim Mia');
    });

    test('class filter must be an id in the current school', function (): void {
        ['owner' => $owner] = schoolWithClass();
        $foreign = schoolWithClass();

        $this->actingAs($owner)
            ->get('/students?class_id='.$foreign['class']->id)
            ->assertSessionHasErrors(['class_id']);
    });
});

describe('attendance input', function (): void {
    test('future dates and unknown statuses are rejected', function (string $date, string $status): void {
        ['owner' => $owner, 'school' => $school] = schoolWithClass();
        $enrollment = Enrollment::factory()->create(['school_id' => $school->id]);

        $this->actingAs($owner)
            ->post('/attendance', ['date' => $date, 'attendance' => [$enrollment->id => $status]])
            ->assertSessionHasErrors();
    })->with([
        'future date' => ['2999-01-01', 'present'],
        'bad status' => ['2026-03-01', 'on_the_moon'],
    ]);

    test('non numeric enrollment keys are rejected', function (): void {
        ['owner' => $owner] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/attendance', ['date' => '2026-03-01', 'attendance' => ['abc' => 'present']])
            ->assertSessionHasErrors();
    });

    test('enrollments of another school are never written', function (): void {
        ['owner' => $owner] = schoolWithClass();
        $foreign = schoolWithClass();
        $foreignEnrollment = Enrollment::factory()->create(['school_id' => $foreign['school']->id]);

        $this->actingAs($owner)
            ->post('/attendance', ['date' => '2026-03-01', 'attendance' => [$foreignEnrollment->id => 'absent']]);

        expect(CurrentSchool::runAsPlatform(fn (): int => AttendanceRecord::count()))->toBe(0);
    });

    test('a valid submission is saved', function (): void {
        ['owner' => $owner, 'school' => $school] = schoolWithClass();
        $enrollment = Enrollment::factory()->create(['school_id' => $school->id]);

        $this->actingAs($owner)
            ->post('/attendance', ['date' => '2026-03-01', 'attendance' => [$enrollment->id => 'absent']])
            ->assertSessionHasNoErrors();

        expect(AttendanceRecord::count())->toBe(1);
    });
});

describe('fee collection input', function (): void {
    /**
     * @return array{0: User, 1: Invoice}
     */
    function accountantWithInvoice(int $due = 100000): array
    {
        $school = School::factory()->create();
        $accountant = schoolUser(UserRole::Accountant, $school);
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'total' => $due,
            'paid' => '0.00',
            'due' => $due,
            'status' => 'unpaid',
        ]);

        return [$accountant, $invoice];
    }

    test('paying more than the due is a validation error, not a server error', function (): void {
        [$accountant, $invoice] = accountantWithInvoice(100000);

        $this->actingAs($accountant)
            ->post("/fees/{$invoice->id}/collect", ['amount' => '1500', 'method' => 'cash'])
            ->assertSessionHasErrors(['amount']);
    });

    test('amount and method are validated', function (mixed $amount, mixed $method): void {
        [$accountant, $invoice] = accountantWithInvoice();

        $this->actingAs($accountant)
            ->post("/fees/{$invoice->id}/collect", ['amount' => $amount, 'method' => $method])
            ->assertSessionHasErrors();
    })->with([
        'zero' => ['0', 'cash'],
        'negative' => ['-5', 'cash'],
        'three decimals' => ['10.123', 'cash'],
        'text' => ['ten', 'cash'],
        'array' => [['1'], 'cash'],
        'bad method' => ['100', 'barter'],
    ]);

    test('Bangla digits are accepted for the amount', function (): void {
        [$accountant, $invoice] = accountantWithInvoice();

        $this->actingAs($accountant)
            ->post("/fees/{$invoice->id}/collect", ['amount' => '৫০০', 'method' => 'cash'])
            ->assertSessionHasNoErrors();

        expect($invoice->fresh()->paid)->toBe(50000);
    });

    test('an invoice of another school cannot be collected', function (): void {
        [$accountant] = accountantWithInvoice();
        [, $foreignInvoice] = accountantWithInvoice();

        $this->actingAs($accountant)
            ->post("/fees/{$foreignInvoice->id}/collect", ['amount' => '100', 'method' => 'cash'])
            ->assertNotFound();
    });

    test('collecting a fee writes an activity log entry', function (): void {
        [$accountant, $invoice] = accountantWithInvoice();

        $this->actingAs($accountant)
            ->post("/fees/{$invoice->id}/collect", ['amount' => '100', 'method' => 'cash']);

        expect(Activity::where('description', 'fee_collected')->count())->toBe(1);
    });

    test('the fee list search term must be a string', function (): void {
        [$accountant] = accountantWithInvoice();

        $this->actingAs($accountant)
            ->get('/fees?search[]=x')
            ->assertSessionHasErrors(['search']);
    });
});

describe('notice input', function (): void {
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    function noticePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Holiday',
            'body' => 'School is closed on Friday.',
            'audience' => 'all',
            'channel' => 'app',
        ], $overrides);
    }

    test('a valid notice is stored for the current school', function (): void {
        ['owner' => $owner, 'school' => $school] = schoolWithClass();

        $this->actingAs($owner)->post('/notices', noticePayload())->assertSessionHasNoErrors();

        expect(Notice::first()->school_id)->toBe($school->id);
    });

    test('a target class from another school is rejected', function (): void {
        ['owner' => $owner] = schoolWithClass();
        $foreign = schoolWithClass();

        $this->actingAs($owner)
            ->post('/notices', noticePayload(['audience' => 'class', 'target_class_id' => $foreign['class']->id]))
            ->assertSessionHasErrors(['target_class_id']);
    });

    test('class audience needs a target class and the body has a length cap', function (): void {
        ['owner' => $owner] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/notices', noticePayload(['audience' => 'class']))
            ->assertSessionHasErrors(['target_class_id']);

        $this->actingAs($owner)
            ->post('/notices', noticePayload(['body' => str_repeat('a', 5001)]))
            ->assertSessionHasErrors(['body']);
    });

    test('sms notices are capped so one notice cannot burn the credit balance', function (): void {
        ['owner' => $owner] = schoolWithClass();

        $this->actingAs($owner)
            ->post('/notices', noticePayload(['channel' => 'sms', 'body' => str_repeat('a', 700)]))
            ->assertSessionHasErrors(['body']);
    });
});
