@php
    $canSeeFees = auth()->user()->can('manage-transport');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $route->name }} | {{ __('transport.print.title') }}</title>
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
        <button type="button" onclick="window.print()">{{ __('transport.common.print') }}</button>
    </div>

    <h1>{{ $route->name_bn ?? $route->name }}</h1>
    <p class="meta">{{ auth()->user()->school?->name_bn ?? auth()->user()->school?->name }}</p>
    @if ($route->vehicle)
        <p class="meta">{{ __('transport.print.van') }}: {{ $route->vehicle->van_number }} | {{ __('transport.common.driver') }}: {{ $route->vehicle->driver_name }} | {{ __('transport.common.mobile') }}: {{ $route->vehicle->driver_mobile }}</p>
    @endif
    @if ($route->stopList())
        <p class="meta">{{ __('transport.common.stops') }}: {{ implode(', ', $route->stopList()) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>{{ __('transport.print.no') }}</th>
                <th>{{ __('transport.common.student') }}</th>
                <th>{{ __('transport.common.class') }}</th>
                <th>{{ __('transport.common.stop') }}</th>
                @if ($canSeeFees)
                    <th>{{ __('transport.show.fee') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($assignments as $assignment)
                <tr>
                    <td>{{ \App\Support\Helpers\LocaleFormat::digits((string) $loop->iteration) }}</td>
                    <td>{{ $assignment->enrollment->student->name_bn ?? $assignment->enrollment->student->name }}</td>
                    <td>{{ $assignment->enrollment->schoolClass?->name_bn ?? $assignment->enrollment->schoolClass?->name }}</td>
                    <td>{{ $assignment->stop ?? '-' }}</td>
                    @if ($canSeeFees)
                        <td>{{ \App\Support\Helpers\LocaleFormat::taka($assignment->monthly_fee, true) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="{{ $canSeeFees ? 4 : 3 }}">{{ __('transport.print.total') }} ({{ \App\Support\Helpers\LocaleFormat::digits((string) $assignments->count()) }})</th>
                @if ($canSeeFees)
                    <th>{{ \App\Support\Helpers\LocaleFormat::taka($assignments->sum('monthly_fee'), true) }}</th>
                @endif
            </tr>
        </tfoot>
    </table>
</body>
</html>
