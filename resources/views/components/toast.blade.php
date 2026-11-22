<div
    x-data="{
        messages: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.messages.push({ id, message, type });
            setTimeout(() => { this.remove(id); }, 4000);
        },
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id);
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type || 'success')"
    {{-- @js() escapes quotes, tags and entities for a JavaScript string inside an attribute. --}}
    x-init="
        @if (session('success')) add(@js(session('success')), 'success'); @endif
        @if (session('error')) add(@js(session('error')), 'error'); @endif
        @if (session('warning')) add(@js(session('warning')), 'warning'); @endif
    "
    class="fixed bottom-20 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
    style="display: none;"
    x-show="messages.length > 0"
>
    <template x-for="item in messages" :key="item.id">
        <div
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="pointer-events-auto rounded-xl p-4 shadow-lg border text-sm flex items-start gap-3"
            :class="{
                'bg-emerald-50 border-emerald-200 text-emerald-900': item.type === 'success',
                'bg-red-50 border-red-200 text-red-900': item.type === 'error',
                'bg-amber-50 border-amber-200 text-amber-900': item.type === 'warning',
                'bg-blue-50 border-blue-200 text-blue-900': item.type === 'info',
            }"
        >
            <div class="flex-1 font-medium" x-text="item.message"></div>
            <button
                type="button"
                @click="remove(item.id)"
                class="opacity-60 hover:opacity-100 text-current p-0.5 rounded-sm"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
