@props([
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
