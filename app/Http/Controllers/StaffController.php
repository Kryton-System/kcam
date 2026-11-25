<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Staff\Models\Staff;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Support\Helpers\Search;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $query = Staff::query()->orderBy('name');
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            Search::contains($query, ['name', 'name_bn', 'designation', 'phone'], mb_substr($search, 0, 100));
        }

        return view('staff.index', [
            'staff' => $query->paginate(15)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('staff.form', ['staff' => new Staff(['status' => 'active'])]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        Staff::create($request->staffData());

        return redirect()->route('staff.index')->with('success', __('staff.flash.added'));
    }

    public function edit(Staff $staff): View
    {
        return view('staff.form', ['staff' => $staff]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->staffData());

        return redirect()->route('staff.index')->with('success', __('staff.flash.updated'));
    }
}
