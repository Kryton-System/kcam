<?php

declare(strict_types=1);

namespace App\Domain\Transport\Actions;

use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportRoute;
use App\Domain\Transport\TransportException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Puts one enrolled student on a route. The fee is whole poisha. The seat check and the
 * insert run in one transaction with the route row locked, so two clerks cannot both
 * take the last seat.
 */
class AssignStudentToRoute
{
    /**
     * @throws TransportException when the route has no vehicle, is full, or the student already rides
     */
    public function handle(TransportRoute $route, int $enrollmentId, ?string $stop, int $monthlyFeePoisha): TransportAssignment
    {
        return DB::transaction(function () use ($route, $enrollmentId, $stop, $monthlyFeePoisha): TransportAssignment {
            $locked = TransportRoute::query()->whereKey($route->id)->lockForUpdate()->with('vehicle')->firstOrFail();

            if ($locked->vehicle === null) {
                throw new TransportException('এই রুটে কোনো গাড়ি নেই / This route has no vehicle yet.');
            }

            if ($locked->assignments()->count() >= $locked->vehicle->capacity) {
                throw new TransportException('গাড়ির আসন পূর্ণ / The vehicle is full.');
            }

            try {
                return $locked->assignments()->create([
                    'enrollment_id' => $enrollmentId,
                    'stop' => $stop,
                    'monthly_fee' => $monthlyFeePoisha,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new TransportException('শিক্ষার্থী আগে থেকেই একটি রুটে আছে / This student already rides a route.');
            }
        });
    }
}
