@php
    $school = auth()->user()->school;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('accounts.cash_book.title') }} | {{ $date->format('Y-m-d') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Bengali', sans-serif; color: #000; margin: 24px; font-size: 14px; }
        h1, h2 { margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: 700; }
        .bar { margin-bottom: 16px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        @media print { .bar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <form method="GET" action="{{ route('accounts.cash-book') }}" class="bar">
        <label for="date">{{ __('accounts.common.date') }}</label>
        <input type="date" id="date" name="date" value="{{ $date->format('Y-m-d') }}">
        <button type="submit">{{ __('accounts.common.show') }}</button>
        <button type="button" onclick="window.print()">{{ __('accounts.cash_book.print') }}</button>
        <a href="{{ route('accounts.index') }}">{{ __('accounts.cash_book.back') }}</a>
    </form>

    <h1>{{ $school?->name_bn ?? $school?->name }}</h1>
    <h2>{{ __('accounts.cash_book.daily') }}: {{ $date->format('Y-m-d') }}</h2>

    <h3>{{ __('accounts.cash_book.receipts') }}</h3>
    <table>
        <thead><tr><th>{{ __('accounts.cash_book.student') }}</th><th>{{ __('accounts.cash_book.method') }}</th><th class="num">{{ __('accounts.common.amount') }}</th></tr></thead>
        <tbody>
            @forelse ($receipts as $payment)
                <tr>
                    <td>{{ $payment->invoice?->student?->name_bn ?? $payment->invoice?->student?->name ?? '-' }}</td>
                    <td>{{ $payment->method->label() }}</td>
                    <td class="num">{{ \App\Support\Helpers\LocaleFormat::taka($payment->amount, true) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('accounts.common.none') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="2">{{ __('accounts.cash_book.total_in') }}</td><td class="num">{{ \App\Support\Helpers\LocaleFormat::taka($totalIn, true) }}</td></tr></tfoot>
    </table>

    <h3>{{ __('accounts.cash_book.expenses') }}</h3>
    <table>
        <thead><tr><th>{{ __('accounts.common.category') }}</th><th>{{ __('accounts.common.payee') }}</th><th class="num">{{ __('accounts.common.amount') }}</th></tr></thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->category->label() }}</td>
                    <td>{{ $expense->payee }}</td>
                    <td class="num">{{ \App\Support\Helpers\LocaleFormat::taka($expense->amount, true) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('accounts.common.none') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="2">{{ __('accounts.cash_book.total_out') }}</td><td class="num">{{ \App\Support\Helpers\LocaleFormat::taka($totalOut, true) }}</td></tr></tfoot>
    </table>

    <p><strong>{{ __('accounts.cash_book.day_balance') }}: {{ \App\Support\Helpers\LocaleFormat::taka($closing, true) }}</strong></p>
</body>
</html>
