<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Notices\Models\Notice;
use App\Domain\School\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'title' => 'School Holiday Notice',
            'title_bn' => 'ছুটির বিজ্ঞপ্তি',
            'body' => 'School will remain closed tomorrow on account of Shab-e-Barat.',
            'body_bn' => 'আগামীকাল শব-ই-বরাত উপলক্ষে বিদ্যালয় বন্ধ থাকিবে।',
            'audience' => 'all',
            'target_class_id' => null,
            'channel' => 'app',
            'sent_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
