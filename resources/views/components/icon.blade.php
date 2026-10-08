@props(['name' => 'grid'])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('car')
            <path d="m5 7 2-4h10l2 4 2 3v8H3v-8zM3 10h18M7 14h1m8 0h1M6 18v3m12-3v3" />
        @break

        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M7 3v4m10-4v4M3 11h18m-14 4h2m4 0h2" />
        @break

        @case('people')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 5" />
        @break

        @case('box')
            <path d="m12 3 9 5v9l-9 5-9-5V8zM3 8l9 5 9-5m-9 5v9M8 5l9 5" />
        @break

        @case('wrench')
            <path d="M14 5a6 6 0 0 0-7 8L3 17a3 3 0 0 0 4 4l5-5a6 6 0 0 0 8-8l-4 4-4-4z" />
        @break

        @case('receipt')
            <path d="M5 3h14v19l-3-2-4 2-4-2-3 2zM9 7h6m-6 4h6m-6 4h3" />
        @break

        @case('chart')
            <path d="M3 3v18h18M7 16v-4m5 4V8m5 8V5" />
        @break

        @case('bell')
            <path d="M5 17h14l-2-3V9a5 5 0 0 0-10 0v5zM10 21h4" />
        @break

        @case('search')
            <circle cx="10" cy="10" r="6" />
            <path d="m15 15 6 6" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('arrow')
            <path d="M4 12h16m-6-6 6 6-6 6" />
        @break

        @case('check')
            <path d="m5 12 4 4L20 5" />
        @break

        @case('eye')
            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12" />
            <circle cx="12" cy="12" r="3" />
        @break

        @case('logout')
            <path d="M10 4H4v16h6m4-12 4 4-4 4m-6-4h13" />
        @break

        @default
            <rect x="3" y="3" width="7" height="7" rx="2" />
            <rect x="14" y="3" width="7" height="7" rx="2" />
            <rect x="3" y="14" width="7" height="7" rx="2" />
            <rect x="14" y="14" width="7" height="7" rx="2" />
    @endswitch
</svg>
