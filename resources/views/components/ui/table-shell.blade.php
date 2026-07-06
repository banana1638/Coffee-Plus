<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm']) }}>
    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
</div>
