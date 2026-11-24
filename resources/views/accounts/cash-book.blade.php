@php
    use App\Support\Helpers\BanglaHelper;
    $school = auth()->user()->school;
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ক্যাশ বুক | Cash book | {{ $date->format('Y-m-d') }}</title>
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
        <label for="date">তারিখ / Date</label>
        <input type="date" id="date" name="date" value="{{ $date->format('Y-m-d') }}">
        <button type="submit">দেখুন / Show</button>
        <button type="button" onclick="window.print()">প্রিন্ট / Print</button>
        <a href="{{ route('accounts.index') }}">ফিরে যান / Back</a>
    </form>

    <h1>{{ $school?->name_bn ?? $school?->name }}</h1>
    <h2>দৈনিক ক্যাশ বুক / Daily cash book: {{ $date->format('Y-m-d') }}</h2>

    <h3>আয় / Receipts</h3>
    <table>
        <thead><tr><th>শিক্ষার্থী / Student</th><th>মাধ্যম / Method</th><th class="num">পরিমাণ / Amount</th></tr></thead>
        <tbody>
            @forelse ($receipts as $payment)
                <tr>
                    <td>{{ $payment->invoice?->student?->name_bn ?? $payment->invoice?->student?->name ?? '-' }}</td>
                    <td>{{ $payment->method->value }}</td>
                    <td class="num">{{ BanglaHelper::formatTaka($payment->amount, true) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">কিছু নেই / None</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="2">মোট আয় / Total in</td><td class="num">{{ BanglaHelper::formatTaka($totalIn, true) }}</td></tr></tfoot>
    </table>

    <h3>ব্যয় / Expenses</h3>
    <table>
        <thead><tr><th>খাত / Category</th><th>প্রাপক / Payee</th><th class="num">পরিমাণ / Amount</th></tr></thead>
        <tbody>
            @forelse ($expenses as $expense)
                <tr>
                    <td>{{ $expense->category->label() }}</td>
                    <td>{{ $expense->payee }}</td>
                    <td class="num">{{ BanglaHelper::formatTaka($expense->amount, true) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">কিছু নেই / None</td></tr>
            @endforelse
        </tbody>
        <tfoot><tr><td colspan="2">মোট ব্যয় / Total out</td><td class="num">{{ BanglaHelper::formatTaka($totalOut, true) }}</td></tr></tfoot>
    </table>

    <p><strong>দিনশেষে জের / Day balance (in minus out): {{ BanglaHelper::formatTaka($closing, true) }}</strong></p>
</body>
</html>
