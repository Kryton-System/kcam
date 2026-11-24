<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Transport\Models\TransportRoute;
use App\Domain\Transport\Models\TransportVehicle;
use App\Http\Requests\Concerns\NormalizesInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRouteRequest extends FormRequest
{
    use NormalizesInput;

    public function authorize(): bool
    {
        return $this->user()?->can('manage-transport') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeInput(
            textFields: ['name', 'name_bn', 'stops'],
            digitFields: ['vehicle_id'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'name_bn' => ['nullable', 'string', 'max:150'],
            'stops' => ['nullable', 'string', 'max:2000'],
            'vehicle_id' => [
                'nullable', 'integer',
                Rule::exists('transport_vehicles', 'id')->where('school_id', $this->user()->school_id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var TransportRoute|null $route */
            $route = $this->route('transportRoute');
            $vehicle = $validator->errors()->has('vehicle_id') ? null : TransportVehicle::query()->find((int) $this->input('vehicle_id'));

            if ($route === null || $vehicle === null) {
                return;
            }

            $riders = $route->assignments()->count();

            if ($vehicle->capacity < $riders) {
                $validator->errors()->add('vehicle_id', "এই গাড়িতে {$riders} জনের আসন নেই / This vehicle has fewer seats than the {$riders} riders on the route.");
            }
        });
    }
}
