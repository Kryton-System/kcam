<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Notices\Models\Notice;
use App\Domain\School\Models\SchoolClass;
use App\Http\Requests\StoreNoticeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NoticeController extends Controller
{
    /**
     * Display a listing of notices.
     */
    public function index(): View
    {
        return view('notices.index', [
            'notices' => Notice::with(['author', 'targetClass'])->latest()->paginate(15),
            'classes' => SchoolClass::orderBy('order')->get(),
        ]);
    }

    /**
     * Store a newly created notice in storage.
     */
    public function store(StoreNoticeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Notice::create([
            'school_id' => $request->user()->school_id,
            'title' => $validated['title'],
            'title_bn' => $validated['title_bn'] ?? null,
            'body' => $validated['body'],
            'body_bn' => $validated['body_bn'] ?? null,
            'audience' => $validated['audience'],
            'target_class_id' => $validated['target_class_id'] ?? null,
            'channel' => $validated['channel'],
            'sent_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        activity()
            ->causedBy($request->user())
            ->withProperties(['audience' => $validated['audience'], 'channel' => $validated['channel']])
            ->log('notice_published');

        return back()->with('success', 'বিজ্ঞপ্তি সফলভাবে প্রকাশিত হয়েছে।');
    }
}
