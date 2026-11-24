@php
    use App\Support\Helpers\BanglaHelper;
    $canSeeFees = auth()->user()->can('manage-transport');
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $route->name }} | রুট তালিকা / Route roster</title>
    <style>
        body { font-family: "Noto Sans Bengali", "DejaVu Sans", sans-serif; color: #000; margin: 24px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        .meta { margin: 2px 0; }
        .actions { margin-bottom: 12px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">প্রিন্ট / Print</button>
    </div>

    <h1>{{ $route->name_bn ?? $route->name }}</h1>
    <p class="meta">{{ auth()->user()->school?->name_bn ?? auth()->user()->school?->name }}</p>
    @if ($route->vehicle)
        <p class="meta">গাড়ি / Van: {{ $route->vehicle->van_number }} | চালক / Driver: {{ $route->vehicle->driver_name }} | মোবাইল / Mobile: {{ $route->vehicle->driver_mobile }}</p>
    @endif
    @if ($route->stopList())
        <p class="meta">স্টপেজ / Stops: {{ implode(', ', $route->stopList()) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>ক্রম / No.</th>
                <th>শিক্ষার্থী / Student</th>
                <th>শ্রেণি / Class</th>
                <th>স্টপ / Stop</th>
                @if ($canSeeFees)
                    <th>মাসিক ভাড়া / Fee</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($assignments as $assignment)
                <tr>
                    <td>{{ BanglaHelper::toBanglaDigits((string) $loop->iteration) }}</td>
                    <td>{{ $assignment->enrollment->student->name_bn ?? $assignment->enrollment->student->name }}</td>
                    <td>{{ $assignment->enrollment->schoolClass?->name_bn ?? $assignment->enrollment->schoolClass?->name }}</td>
                    <td>{{ $assignment->stop ?? '-' }}</td>
                    @if ($canSeeFees)
                        <td>{{ BanglaHelper::formatTaka($assignment->monthly_fee, true) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $canSeeFees ? 4 : 3 }}">মোট / Total ({{ BanglaHelper::toBanglaDigits((string) $assignments->count()) }})</th>
                @if ($canSeeFees)
                    <th>{{ BanglaHelper::formatTaka($assignments->sum('monthly_fee'), true) }}</th>
                @endif
            </tr>
        </tfoot>
    </table>
</body>
</html>
