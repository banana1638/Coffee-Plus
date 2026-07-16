@php
    $maxCapacity = 10000;
    $currentOz = Auth::user()->tangki_oz ?? 0;
    $percentage = min(100, max(0, ($currentOz / $maxCapacity) * 100));
    $waveY = 100 - $percentage;

    $amp = ($percentage <= 0 || $percentage >= 100) ? 0 : 4;

    $path1 = "M0 100 V $waveY Q 25 " . ($waveY - $amp) . " 50 $waveY T 100 $waveY V 100 Z";
@endphp

<div class="group relative mx-auto h-48 w-48">
    <div class="pointer-events-none absolute inset-0 z-20 flex flex-col items-center justify-center">
        <span class="cp-tabular text-4xl font-bold {{ $percentage > 50 ? 'text-white' : 'text-[rgb(var(--cp-brand-strong))]' }}">
            {{ round($percentage) }}<span class="text-sm font-semibold">%</span>
        </span>
        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] {{ $percentage > 50 ? 'text-emerald-100' : 'text-[rgb(var(--cp-muted))]' }}">
            Capacity
        </span>
    </div>

    <svg viewBox="0 0 100 100" class="h-full w-full overflow-hidden rounded-full border-4 border-[rgb(var(--cp-surface))] bg-stone-100 shadow-[0_20px_45px_-26px_rgba(24,32,29,0.7)]" aria-hidden="true">
        <defs>
            <linearGradient id="tangkiFill" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#1b8b69" />
                <stop offset="100%" stop-color="#0d523f" />
            </linearGradient>
            <mask id="tangkiMask">
                <circle cx="50" cy="50" r="50" fill="white" />
            </mask>
        </defs>

        <g mask="url(#tangkiMask)">
            <path d="{{ $path1 }}" fill="url(#tangkiFill)" />
            <path d="M0 88 H100" fill="none" stroke="rgb(255 255 255 / 0.18)" stroke-width="0.7" />
            <path d="M0 76 H100" fill="none" stroke="rgb(255 255 255 / 0.14)" stroke-width="0.7" />
            <path d="M0 64 H100" fill="none" stroke="rgb(255 255 255 / 0.11)" stroke-width="0.7" />
        </g>
    </svg>

    <div class="absolute -inset-2 rounded-full border border-[rgb(var(--cp-caramel))]/25"></div>
</div>
