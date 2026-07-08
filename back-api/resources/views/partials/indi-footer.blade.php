@props([
    'overlay' => false,
    'theme' => 'light',
])

@php
    $isDark = $theme === 'dark';

    if ($overlay) {
        $wrapperClasses = 'pointer-events-none fixed inset-x-0 bottom-3 z-[60] flex justify-center px-3';
        $panelClasses = $isDark
            ? 'pointer-events-auto rounded-[1rem] bg-white/80 px-5 py-2.5 shadow-[0_10px_30px_rgba(15,23,42,0.14)] backdrop-blur-md transition hover:bg-white/88'
            : 'pointer-events-auto rounded-[1rem] bg-white/90 px-5 py-2.5 shadow-[0_10px_30px_rgba(15,23,42,0.06)] backdrop-blur-md transition hover:bg-white';
    } else {
        $wrapperClasses = $isDark
            ? 'border-t border-white/8 bg-slate-950 px-4 py-4'
            : 'border-t border-slate-200/80 bg-[#f7f7f8] px-4 py-4';
        $panelClasses = 'mx-auto w-full max-w-7xl';
    }

    $labelClasses = $isDark && ! $overlay ? 'text-white/45' : 'text-slate-400';
@endphp

<div class="{{ $wrapperClasses }}">
    <div class="{{ $panelClasses }}">
        <a
            href="https://indi-lab.com/"
            target="_blank"
            rel="noopener noreferrer"
            class="mx-auto flex w-fit flex-col items-center justify-center gap-1.5 text-center transition hover:opacity-80"
        >
            <span class="text-[8px] font-semibold uppercase tracking-[0.24em] {{ $labelClasses }}">
                Desarrollado por
            </span>

            <img
                src="{{ asset('INDI Lab - Logo Emergencia.png') }}"
                alt="INDI Lab"
                class="h-9 w-auto object-contain"
            >
        </a>
    </div>
</div>
