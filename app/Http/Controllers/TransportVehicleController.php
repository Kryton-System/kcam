<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Transport\Models\TransportVehicle;
use App\Http\Requests\SaveVehicleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransportVehicleController extends Controller
{
    public function create(): View
    {
        return view('transport.vehicle-form', ['vehicle' => new TransportVehicle]);
    }

    public function store(SaveVehicleRequest $request): RedirectResponse
    {
        TransportVehicle::create($request->validated());

        return redirect()->route('transport.index')->with('success', __('transport.flash.vehicle_added'));
    }

    public function edit(TransportVehicle $vehicle): View
    {
        return view('transport.vehicle-form', ['vehicle' => $vehicle]);
    }

    public function update(SaveVehicleRequest $request, TransportVehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('transport.index')->with('success', __('transport.flash.vehicle_updated'));
    }
}
