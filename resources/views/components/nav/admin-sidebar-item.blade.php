@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
    @if($active) aria-current="page" @endif
    {{ $attributes->class([
        'group relative flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-[rgb(var(--cp-ink))]',
        'bg-white/10 text-white' => $active,
        'text-stone-300 hover:bg-white/5 hover:text-white' => ! $active,
    ]) }}>
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border {{ $active ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-300' : 'border-white/10 text-stone-400 group-hover:border-white/20 group-hover:text-white' }}">
        {{ $icon }}
    </span>
    <span class="min-w-0 flex-1">{{ $slot }}</span>
    @if($active)
        <span class="absolute inset-y-2 -right-4 w-0.5 bg-emerald-400" aria-hidden="true"></span>
    @endif
</a>
