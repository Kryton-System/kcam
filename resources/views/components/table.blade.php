@props([
    'headers' => [],
])

<div class="w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
    <div class="overflow-x-auto">
        <table data-stack class="min-w-full divide-y divide-slate-200 text-left text-sm">
            @if (! empty($headers))
                <thead class="bg-slate-100/90 text-slate-900 font-semibold">
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" class="px-4 py-3.5 whitespace-nowrap text-xs">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-slate-200/70 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
