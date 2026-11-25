@props([
    'name',
    'title' => null,
    'message' => null,
    'confirmText' => null,
    'cancelText' => null,
    'action' => '#',
    'method' => 'POST',
])

<x-modal :name="$name" maxWidth="sm">
    <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-lg bg-red-100 text-red-600 mb-4">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h3 class="text-base font-semibold text-slate-900 mb-2">{{ $title ?? __('common.confirm.title') }}</h3>
        <p class="text-sm text-slate-500 mb-6">{{ $message ?? __('common.confirm.message') }}</p>

        <form action="{{ $action }}" method="{{ $method === 'GET' ? 'GET' : 'POST' }}" class="flex items-center gap-3 justify-center">
            @if ($method !== 'GET' && $method !== 'POST')
                @method($method)
            @endif
            @csrf

            <x-button variant="secondary" x-on:click="show = false" type="button" class="flex-1">
                {{ $cancelText ?? __('common.confirm.cancel') }}
            </x-button>

            <x-button variant="danger" type="submit" class="flex-1">
                {{ $confirmText ?? __('common.confirm.confirm') }}
            </x-button>
        </form>
    </div>
</x-modal>
