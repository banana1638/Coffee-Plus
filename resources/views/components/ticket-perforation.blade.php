@props([
    'count' => 18,
])

<div {{ $attributes->merge(['class' => 'pointer-events-none flex justify-between gap-1 px-3']) }} aria-hidden="true">
    @for($i = 0; $i < $count; $i++)
        <span class="-mt-2 h-4 w-4 rounded-full bg-[rgb(var(--cp-canvas))]"></span>
    @endfor
</div>
