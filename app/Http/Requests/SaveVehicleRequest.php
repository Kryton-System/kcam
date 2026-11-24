<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportVehicle;
use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVehicleRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-transport') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['van_number', 'driver_name'],
            digitFields: ['capacity'],
            mobileFields: ['driver_mobile'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var TransportVehicle|null $vehicle */
        $vehicle = $this->route('vehicle');

        return [
            'van_number' => [
                'required', 'string', 'max:50',
                Rule::unique('transport_vehicles', 'van_number')
                    ->where('school_id', $this->user()->school_id)
                    ->ignore($vehicle?->id),
            ],
            'driver_name' => ['required', 'string', 'max:150'],
            'driver_mobile' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var TransportVehicle|null $vehicle */
            $vehicle = $this->route('vehicle');

            if ($vehicle === null || $validator->errors()->has('capacity')) {
                return;
            }

            $busiest = TransportAssignment::query()
                ->whereIn('route_id', $vehicle->routes()->pluck('id'))
                ->selectRaw('route_id, count(*) as riders')
                ->groupBy('route_id')
                ->orderByDesc('riders')
                ->value('riders');

            if ((int) $this->input('capacity') < (int) $busiest) {
                $validator->errors()->add('capacity', "ইতিমধ্যে {$busiest} জন শিক্ষার্থী আছে / Capacity cannot be below the {$busiest} riders already assigned.");
            }
        });
    }
}
