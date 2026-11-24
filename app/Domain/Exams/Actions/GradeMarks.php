<?php

declare(strict_types=1);

namespace App\Domain\Exams\Actions;

/**
 * Bangladesh primary GPA scale, applied to the percentage of full marks.
 */
class GradeMarks
{
    /**
     * @return array{0: string, 1: float} letter grade and grade point
     */
    public function for(float $marks, int $fullMarks): array
    {
        $percent = $fullMarks > 0 ? ($marks / $fullMarks) * 100 : 0;

        return match (true) {
            $percent >= 80 => ['A+', 5.0],
            $percent >= 70 => ['A', 4.0],
            $percent >= 60 => ['A-', 3.5],
            $percent >= 50 => ['B', 3.0],
            $percent >= 40 => ['C', 2.0],
            $percent >= 33 => ['D', 1.0],
            default => ['F', 0.0],
        };
    }
}
