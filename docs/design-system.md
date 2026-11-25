# Design system

KCAM's interface is built with Tailwind CSS 4 and a set of Blade components in `resources/views/components`. There is no separate component library and no `tailwind.config.js`: design tokens live in the `@theme` block of `resources/css/app.css`. This page lists the tokens, the visual rules the current views follow, every component with its props and slots, and the gaps that still need shared components.

## Stack

- Tailwind CSS `^4.0.0` through `@tailwindcss/vite`, with Vite `^8.0.0` (`package.json`).
- Alpine behaviour (modal and toast) comes from the Alpine build bundled with Livewire through `@livewireScripts`. The app has no Livewire components (`app/Livewire` does not exist).
- `resources/js/app.js` has one job: it copies each table column title onto its cells as `data-label`, so stacked tables on phones can label every value.
- `resources/css/app.css` imports Tailwind and scans `resources/views/**/*.blade.php`, the Laravel pagination views and `storage/framework/views`.

## Tokens in resources/css/app.css

| Token | Value | Used by views |
|---|---|---|
| `--font-sans` | `'Inter', 'Hind Siliguri', 'Noto Sans Bengali', system-ui, -apple-system, sans-serif` | Yes, through `body` |
| `--font-bn` | `'Hind Siliguri', 'Noto Sans Bengali', sans-serif` | No |
| `--color-primary-50` to `--color-primary-900` | Green scale, `#f0fdf4` (50) to `#14532d` (900) | No |
| `--color-surface-bg` | `#f8fafc` | Only the `body` rule |
| `--color-surface-card` | `#ffffff` | No |
| `--color-surface-border` | `#e2e8f0` | No |
| `--color-text-main` | `#0f172a` | Only the `body` rule |
| `--color-text-muted` | `#64748b` | No |
| `--color-text-subtle` | `#94a3b8` | No |

Two rules sit outside `@theme`:

- `:root:lang(bn)` replaces `--font-sans` with the Bangla stack, so any page with `<html lang="bn">` uses Hind Siliguri first.
- `.touch-target` sets `min-height: 48px` and `min-width: 48px` (see Touch targets).

`body` uses `var(--font-sans)`, the surface background and the main text colour, and turns off the tap highlight on mobile.

The custom `primary`, `surface` and `text` tokens are not referenced by any view (no `bg-primary-*`, `text-main` or `bg-surface-*` class exists in `resources/views`). Views use Tailwind's default palette directly, so the token values and the rendered colours can drift apart. See Limitations.

## Colour and surface rules in use

Taken from the components and the views, not from a written style guide:

- Primary action: `emerald-600` background, `emerald-700` on hover (default button, success badge, logo tiles, active navigation).
- Secondary action: `slate-100` background with `slate-200` border; danger: `red-600`; ghost uses `slate-600` text; outline uses `emerald-700` text with an `emerald-600` border.
- Status badges: success `emerald`, danger `red`, warning `amber`, info `blue`, neutral `slate`. Each uses a 50-level background, a 700-level text and a faint ring.
- Dashboard figures: left border `emerald-600` (success), `amber-500` (warning) or `slate-300` (neutral).
- Stat cards: icon tile colours `emerald`, `blue`, `amber`, `purple`, `rose`. Trends: up in `emerald-600`, down in `rose-600`, other in `slate-500`.
- Text: headings `slate-900`, labels `slate-800`, secondary text `slate-500`, navigation `slate-600`. Errors use `text-red-600` with `font-medium`.
- Page background `slate-50`, cards white with a `slate-200/80` border.

Corner radius:

- `rounded-lg` (Tailwind 4 default `--radius-lg`, 0.5rem = 8px) is the largest radius in use. Cards, buttons, inputs, selects, textareas, tables, modals, toasts and stat cards all use it.
- `rounded-md` (0.375rem = 6px) is used on badges and the language switcher buttons.
- `rounded-sm` is used on the toast close button, and plain `rounded` on the login remember-me checkbox and a small tag in the app layout.
- No view uses `rounded-xl`, `rounded-2xl`, `rounded-3xl` or `rounded-full`, so the 8px ceiling holds for every current view. No automated check enforces it.

Shadows:

- `shadow-xs`: cards, tables, stat cards, logo tiles.
- `shadow-sm`: primary button, inputs, selects, textareas, modal, toast, login panel.
- `shadow-2xs`: landing page cards.
- No `shadow-md`, `shadow-lg` or `shadow-xl` appears in views.

Gradients and glass:

- No gradient classes or CSS gradients exist in `resources/views` or `resources/css`.
- No `backdrop-blur` or `backdrop-filter` is used. Translucency appears only as `bg-white/90` (landing header), `bg-white/50` (empty state) and `bg-slate-900/50` (modal backdrop).

Typography conventions visible in the components and screens:

- Page title: `text-xl md:text-2xl font-bold text-slate-900` with a `text-xs md:text-sm text-slate-500` subtitle.
- Card title: `text-base font-semibold`. Figures in dashboards use `tabular-nums`.
- Form labels `text-sm font-medium text-slate-800`, hints `text-xs text-slate-500`.

## Components

All components are in `resources/views/components`. "Used in" counts views outside the components folder. Props are listed with defaults in brackets. Every component merges extra HTML attributes onto its root element through `$attributes` unless noted.

| File | Tag | Props | Slots and notes | Used in |
|---|---|---|---|---|
| `app-layout.blade.php` | `<x-app-layout>` | None | `$title` (falls back to the app name), optional `header` slot, default slot for page content. Renders the desktop sidebar, the mobile top bar and bottom navigation, the language switcher, the toast and Livewire scripts. | 26 views |
| `guest-layout.blade.php` | `<x-guest-layout>` | None | `$title`, default slot for the form. Shows the switcher, the mark and tagline. | Login and register |
| `badge.blade.php` | `<x-badge>` | `variant` [neutral] (success, danger, warning, info, neutral), `size` [md] (sm, md, lg) | Default slot is the label. | 7 views |
| `button.blade.php` | `<x-button>` | `variant` [primary] (secondary, danger, ghost, outline), `size` [md] (sm, lg), `block` [false], `href` [null], `type` [button] | Default slot is the label. With `href` it renders an `<a>`, otherwise a `<button>`. | 23 views |
| `card.blade.php` | `<x-card>` | `title` [null], `subtitle` [null], `action` [null], `footer` [null] | Slots `action` (right of header) and `footer`. Default slot is the body. | 25 views |
| `confirm-modal.blade.php` | `<x-confirm-modal>` | `name` (required), `title`, `message`, `confirmText`, `cancelText`, `action` [#], `method` [POST] | Wraps `<x-modal>` with a red warning icon. `GET` submits as GET, other methods are sent with method spoofing. | Not used |
| `dashboard-figure.blade.php` | `<x-dashboard-figure>` | `label` (required), `value` (required), `note` [null], `tone` [neutral] (success, warning) | Single figure with a coloured left border. | 3 views |
| `empty-state.blade.php` | `<x-empty-state>` | `title` (required), `description` [null], `icon` [null] | Default slot holds actions shown below the text. Without `icon` a generic SVG is shown. | 15 views |
| `input.blade.php` | `<x-input>` | `name` (required), `label`, `type` [text], `placeholder` [empty], `required` [false], `error` [null], `hint` [null], `id` [name] | Shows `error`, or the first validation error for `name`, in red. Other attributes such as `value` go to the `<input>`. | 14 views |
| `language-switcher.blade.php` | `<x-language-switcher>` | `class` [empty] | Two links (`বাংলা`, `English`) to `locale.switch`. | Desktop sidebar, mobile top bar, guest layout, landing page |
| `modal.blade.php` | `<x-modal>` | `name` (required), `title` [null], `maxWidth` [md] (sm, md, lg, xl) | Default slot is the body. Title shows a header and a close button. | Only through `confirm-modal` |
| `nav-link.blade.php` | `<x-nav-link>` | `active` [false], `href` [#] | Named slot `icon` and default slot for the label. | Desktop sidebar and mobile navigation in `app-layout` only |
| `select.blade.php` | `<x-select>` | `name` (required), `label`, `options` [empty array of value to text], `placeholder` [null], `required` [false], `selected` [null], `error`, `hint`, `id` [name] | Default slot for extra `<option>` elements. Options from `options` and the slot are both rendered. | 12 views |
| `stat-card.blade.php` | `<x-stat-card>` | `title` (required), `value` (required), `subtitle` [null], `trend` [null], `trendType` [up] (down, neutral), `icon` [null], `color` [emerald] (blue, amber, purple, rose) | Shows a trend line when `trend` is set. | Not used |
| `table.blade.php` | `<x-table>` | `headers` [empty array] | Default slot holds `<tr>` rows for `<tbody>`. The table always carries `data-stack`, so it stacks on phones. | 15 views |
| `textarea.blade.php` | `<x-textarea>` | `name` (required), `label`, `rows` [3], `placeholder` [empty], `required` [false], `error`, `hint`, `id` [name] | Same error and hint behaviour as `input`. | 3 views |
| `toast.blade.php` | `<x-toast>` | None | Shows flash messages from the session keys `success`, `error` and `warning`, and listens for the window event `toast` with `detail` containing `message` and `type` (success, error, warning, info). Each message is removed after 4000 ms. | `app-layout`, `guest-layout` |

Opening and closing the modal from other code uses Alpine events on `window`:

```js
window.dispatchEvent(new CustomEvent('open-modal', { detail: 'delete-student' }));
window.dispatchEvent(new CustomEvent('close-modal', { detail: 'delete-student' }));
window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Saved', type: 'success' } }));
```

`toast` messages are translated text, so pass the output of `__()` in the `message` field. No page dispatches these events today.

## Touch targets

- `.touch-target` (48 by 48 px minimum) is applied to every `<x-button>` (the component always includes it), the bottom navigation links, the mobile top bar logout button and the offline reload button.
- The size classes on `<x-button>` (`min-h-[40px]` for `sm`, `min-h-[52px]` for `lg`) are expected to be overridden by `.touch-target`, because the custom rule is not inside a Tailwind layer. This follows from the cascade and has not been measured in a browser.
- The parent child switcher in `resources/views/portal/show.blade.php` (line 21) uses `min-h-[44px]` instead, so it is 4 px shorter than the shared target. The plan in `docs/ui-ux-ai-qa-plan.md` asks for 48 px.

## Responsive layout

- The breakpoint for desktop is `md` (768 px). Below it, the app shows a sticky top bar and a fixed bottom navigation (`h-16`), and the body adds bottom padding (`pb-20`) so content is not hidden behind the bar.
- The desktop sidebar is `md:fixed`, 16 rem (`md:w-64`) wide.
- Below 768 px (`max-width: 767px`), `table[data-stack]` turns each row into a block, hides the header and shows each cell's label through `data-label`.
- The bottom navigation includes the class `safe-area-bottom`, but no CSS rule defines it, so it has no effect.

## Building a screen

Start from a page that already exists. The students index (`resources/views/students/index.blade.php`) shows the standard structure. Simplified:

```blade
<x-app-layout>
    <x-slot:title>{{ __('students.index.title') }} | {{ __('app.name') }}</x-slot:title>

    <x-slot:header>
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">{{ __('students.index.title') }}</h1>
            <p class="text-xs md:text-sm text-slate-500 mt-1">{{ __('students.index.subtitle') }}</p>
        </div>
        <x-button :href="route('students.create')">{{ __('students.index.new_admission') }}</x-button>
    </x-slot:header>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <x-input name="search" :placeholder="__('students.index.search_placeholder')" :value="request('search')" />
            <x-select name="status" :selected="request('status')">
                <option value="active">{{ __('students.index.status_active') }}</option>
            </x-select>
            <x-button type="submit" variant="secondary">{{ __('messages.search') }}</x-button>
        </form>
    </x-card>

    @if ($students->isEmpty())
        <x-empty-state :title="__('students.index.empty_title')" :description="__('students.index.empty_description')">
            <x-button :href="route('students.create')">{{ __('students.index.new_admission') }}</x-button>
        </x-empty-state>
    @else
        <x-table :headers="[__('students.index.columns.name'), __('students.index.columns.status')]">
            @foreach ($students as $student)
                <tr>
                    <td class="px-4 py-3">{{ $student->name_bn ?? $student->name }}</td>
                    <td class="px-4 py-3"><x-badge variant="success">{{ $student->status?->label() ?? '-' }}</x-badge></td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $students->links() }}</div>
    @endif
</x-app-layout>
```

Rules that follow from the components:

- Put every visible string through `__()` (see [Localization](localization.md)). Format money with `LocaleFormat::taka()` and dates with `LocaleFormat::date()`.
- Pass `headers` to `<x-table>` as translated strings. `app.js` copies them to `data-label`, so the cells need no extra attributes on phones.
- Use one `<x-button>` with `variant="primary"` for the main action of a screen. The planning doc asks for one primary action per screen.
- Show validation errors by giving `<x-input>`, `<x-select>` and `<x-textarea>` the matching `name`. They read the error bag automatically.
- Use `<x-empty-state>` when a list has no rows, with its slot for the next action.
- Use `<x-badge>` for statuses and `<x-card>` to group a form or a list.

## Limitations

- The custom `primary`, `surface` and `text` tokens in `@theme` are unused by views. Colours come from Tailwind's default palette. Either use the tokens in views or remove them, so there is one source of colour truth.
- `--font-bn` is declared but unused.
- Shared components are missing or unused:
  - Modal: the component exists but no page opens it, so its behaviour is untested in use. It closes on Escape and on backdrop click only. It has no focus trap, no focus return to the opener and no body scroll lock. The close button has an English screen-reader label (`Close`) and appears only when `title` is set.
  - Dropdown or menu: no component exists, and no page has a menu built with Alpine.
  - Pagination: no override exists in `resources/views/vendor`, so every `->links()` call uses Laravel's default Tailwind paginator. Its labels are English on Bangla pages (see [Localization](localization.md)).
  - Loading states: no spinner, skeleton, `wire:loading` or disabled-while-submitting state exists. `<x-button>` has disabled styles but no `disabled` prop.
  - Stat card: `<x-stat-card>` is not used by any view.
  - Confirm modal: `<x-confirm-modal>` is not used by any view, so destructive actions currently have no shared confirmation pattern.
- Both layouts set `maximum-scale=1` and `user-scalable=0` in the viewport meta tag, which blocks pinch zoom. This conflicts with WCAG 1.4.4 (resize text).
- `.touch-target` and the size classes of `<x-button>` conflict as described above. The portal child switcher uses 44 px, not 48 px.
- Corner radius, shadow and colour rules are followed by convention only. No lint rule or test checks them.
- No browser or visual regression tests cover the components.
