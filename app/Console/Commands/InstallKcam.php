<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\School\Actions\SetupSchoolAction;
use App\Domain\School\Models\School;
use App\Support\Helpers\NormalizesInputHelper;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class InstallKcam extends Command
{
    protected $signature = 'kcam:install
        {--name= : School name}
        {--name-bn= : School name in Bangla}
        {--phone= : School phone, 01XXXXXXXXX}
        {--address= : School address}
        {--owner-name= : Owner full name}
        {--owner-email= : Owner login email}
        {--owner-password= : Owner password (prompted when omitted, prefer the prompt)}';

    protected $description = 'Set up this install for one school: roles, school profile, owner account, academic year and classes';

    public function handle(SetupSchoolAction $setup): int
    {
        if (School::query()->exists()) {
            $this->error('This install already has a school. kcam:install only runs once.');

            return self::FAILURE;
        }

        $input = [
            'name' => $this->option('name') ?? $this->ask('School name'),
            'name_bn' => $this->option('name-bn'),
            'phone' => NormalizesInputHelper::canonicalMobile((string) ($this->option('phone') ?? $this->ask('School phone (01XXXXXXXXX)'))),
            'address' => $this->option('address'),
            'owner_name' => $this->option('owner-name') ?? $this->ask('Owner name'),
            'owner_email' => $this->option('owner-email') ?? $this->ask('Owner email'),
            'owner_password' => $this->option('owner-password') ?? $this->secret('Owner password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'name_bn' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'lowercase', 'email', 'max:255'],
            'owner_password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--force' => true]);

        $school = $setup->execute($validator->validated());

        $this->info("Installed {$school->name}. Sign in as {$input['owner_email']}.");

        return self::SUCCESS;
    }
}
