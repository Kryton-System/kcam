<?php

declare(strict_types=1);

namespace App\Domain\Transport\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\TransportVehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportVehicle extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = ['school_id', 'van_number', 'driver_name', 'driver_mobile', 'capacity'];

    protected function casts(): array
    {
        return ['capacity' => 'integer'];
    }

    protected static function newFactory(): TransportVehicleFactory
    {
        return TransportVehicleFactory::new();
    }

    public function routes(): HasMany
    {
        return $this->hasMany(TransportRoute::class, 'vehicle_id');
    }
}
