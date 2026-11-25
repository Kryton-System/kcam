<?php

declare(strict_types=1);

namespace App\Domain\Transport\Actions;

use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportRoute;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\TransportException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Puts one enrolled student on a route. The fee is whole poisha. The seat check and the
 * insert run in one transaction with the vehicle row locked, because several routes can
 * share one van and the seats are counted across all of them.
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
                throw new TransportException(__('transport.errors.no_vehicle'));
            }

            $vehicle = TransportVehicle::query()->whereKey($locked->vehicle->id)->lockForUpdate()->firstOrFail();

            if ($vehicle->riderCount() >= $vehicle->capacity) {
                throw new TransportException(__('transport.errors.vehicle_full'));
            }

            try {
                return $locked->assignments()->create([
                    'enrollment_id' => $enrollmentId,
                    'stop' => $stop,
                    'monthly_fee' => $monthlyFeePoisha,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new TransportException(__('transport.errors.already_assigned'));
            }
        });
    }
}
