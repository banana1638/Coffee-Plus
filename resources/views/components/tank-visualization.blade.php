@php
    $maxCapacity = 10000;
    $currentOz = Auth::user()->tangki_oz ?? 0;
    $percentage = min(100, max(0, ($currentOz / $maxCapacity) * 100));
    $waveY = 100 - $percentage;

    $amp = ($percentage <= 0 || $percentage >= 100) ? 0 : 4;

    $path1 = "M0 100 V $waveY Q 25 " . ($waveY - $amp) . " 50 $waveY T 100 $waveY V 100 Z";
    $path2 = "M0 100 V $waveY Q 25 " . ($waveY + $amp) . " 50 $waveY T 100 $waveY V 100 Z";
    $path3 = "M0 100 V $waveY Q 25 " . ($waveY - $amp) . " 50 $waveY T 100 $waveY V 100 Z";
@endphp

<div class="relative w-48 h-48 mx-auto group">
    <div class="absolute inset-0 flex flex-col items-center justify-center z-20 pointer-events-none">
        <span class="text-4xl font-black {{ $percentage > 50 ? 'text-white' : 'text-blue-600' }} transition-colors duration-500">
            {{ round($percentage) }}<span class="text-sm font-bold">%</span>
        </span>
        <span class="text-[10px] font-black uppercase tracking-widest {{ $percentage > 50 ? 'text-blue-100' : 'text-gray-400' }} opacity-80">
            Capacity
        </span>
    </div>

    <svg viewBox="0 0 100 100" class="w-full h-full rounded-full border-4 border-white shadow-2xl bg-gray-50 overflow-hidden">
        <defs>
            <linearGradient id="waterGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="#60a5fa" />
                <stop offset="100%" stop-color="#1d4ed8" />
            </linearGradient>
            <mask id="roundMask">
                <circle cx="50" cy="50" r="50" fill="white" />
            </mask>
        </defs>

        <g mask="url(#roundMask)">
            <path fill="url(#waterGrad)">
                <animate 
                    attributeName="d" 
                    dur="2.5s" 
                    repeatCount="indefinite"
                    values="{{ $path1 }};{{ $path2 }};{{ $path3 }}" 
                />
            </path>
        </g>
    </svg>
    
    <div class="absolute -inset-2 rounded-full border-2 border-blue-200/30 animate-pulse"></div>
</div>