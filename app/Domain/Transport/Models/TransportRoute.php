<?php

declare(strict_types=1);

namespace App\Domain\Transport\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\TransportRouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = ['school_id', 'vehicle_id', 'name', 'name_bn', 'stops'];

    protected static function newFactory(): TransportRouteFactory
    {
        return TransportRouteFactory::new();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'vehicle_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TransportAssignment::class, 'route_id');
    }

    /**
     * Stop names, one per line in the stored text.
     *
     * @return list<string>
     */
    public function stopList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->stops) ?: [])));
    }
}
