<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Student\Models\Enrollment;
use App\Domain\Transport\Actions\AssignStudentToRoute;
use App\Domain\Transport\Models\TransportAssignment;
use App\Domain\Transport\Models\TransportRoute;
use App\Domain\Transport\Models\TransportVehicle;
use App\Domain\Transport\TransportException;
use App\Http\Requests\AssignStudentRequest;
use App\Http\Requests\SaveRouteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TransportController extends Controller
{
    public function index(): View
    {
        return view('transport.index', [
            'routes' => TransportRoute::with('vehicle')->withCount('assignments')->orderBy('name')->get(),
            'vehicles' => TransportVehicle::withCount('routes')->orderBy('van_number')->get(),
        ]);
    }

    public function create(): View
    {
        return view('transport.route-form', ['route' => new TransportRoute, 'vehicles' => $this->vehicles()]);
    }

    public function store(SaveRouteRequest $request): RedirectResponse
    {
        $route = TransportRoute::create($request->validated());

        return redirect()->route('transport.routes.show', $route)->with('success', 'রুট যোগ হয়েছে / Route added.');
    }

    public function show(Request $request, TransportRoute $transportRoute): View
    {
        $transportRoute->load('vehicle');

        return view('transport.show', [
            'route' => $transportRoute,
            'assignments' => $this->roster($transportRoute),
            'candidates' => $request->user()->can('manage-transport') ? $this->candidates($request) : collect(),
        ]);
    }

    public function print(TransportRoute $transportRoute): View
    {
        $transportRoute->load('vehicle');

        return view('transport.print', ['route' => $transportRoute, 'assignments' => $this->roster($transportRoute)]);
    }

    public function edit(TransportRoute $transportRoute): View
    {
        return view('transport.route-form', ['route' => $transportRoute, 'vehicles' => $this->vehicles()]);
    }

    public function update(SaveRouteRequest $request, TransportRoute $transportRoute): RedirectResponse
    {
        $transportRoute->update($request->validated());

        return redirect()->route('transport.routes.show', $transportRoute)->with('success', 'রুট হালনাগাদ হয়েছে / Route updated.');
    }

    public function assign(AssignStudentRequest $request, TransportRoute $transportRoute, AssignStudentToRoute $action): RedirectResponse
    {
        try {
            $action->handle(
                $transportRoute,
                (int) $request->validated('enrollment_id'),
                $request->validated('stop'),
                $request->feePoisha(),
            );
        } catch (TransportException $e) {
            return back()->withInput()->withErrors(['enrollment_id' => $e->getMessage()]);
        }

        return redirect()->route('transport.routes.show', $transportRoute)->with('success', 'শিক্ষার্থী যোগ হয়েছে / Student assigned.');
    }

    public function unassign(TransportAssignment $assignment): RedirectResponse
    {
        $routeId = $assignment->route_id;
        $assignment->delete();

        return redirect()->route('transport.routes.show', $routeId)->with('success', 'শিক্ষার্থী সরানো হয়েছে / Student removed.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TransportVehicle>
     */
    private function vehicles()
    {
        return TransportVehicle::orderBy('van_number')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TransportAssignment>
     */
    private function roster(TransportRoute $route)
    {
        return $route->assignments()->with(['enrollment.student', 'enrollment.schoolClass', 'enrollment.section'])->orderBy('stop')->orderBy('id')->get();
    }

    /**
     * Active students of the current year who are not on any route yet.
     *
     * @return Collection<int, Enrollment>
     */
    private function candidates(Request $request)
    {
        $yearId = $request->user()->school?->currentAcademicYear()?->id;

        if ($yearId === null) {
            return collect();
        }

        return Enrollment::with(['student', 'schoolClass'])
            ->where('academic_year_id', $yearId)
            ->where('status', 'active')
            ->whereNotIn('id', TransportAssignment::query()->select('enrollment_id'))
            ->orderBy('class_id')->orderBy('roll_no')
            ->limit(500)->get();
    }
}
