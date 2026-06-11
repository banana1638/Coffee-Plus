<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        {{ $slot }}
    </div>
</div>
