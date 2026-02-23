@php
$locale = App::getLocale();
$names = [
'en' => 'English',
'ro' => 'Română',
'hu' => 'Magyar',
'de' => 'Deutsch',
];

$svgFlags = [
'en' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="20" height="15" style="border: 1px solid rgba(0,0,0,0.1);">
    <clipPath id="a">
        <path d="M0 0h60v30H0z" />
    </clipPath>
    <g clip-path="url(#a)">
        <path d="M0 0h60v30H0z" fill="#012169" />
        <path d="m0 0 60 30M60 0 0 30" stroke="#fff" stroke-width="6" />
        <path d="m0 0 60 30M60 0 0 30" stroke="#c8102e" stroke-width="4" />
        <path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10" />
        <path d="M30 0v30M0 15h60" stroke="#c8102e" stroke-width="6" />
    </g>
</svg>',
'ro' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" width="20" height="15" style="border: 1px solid rgba(0,0,0,0.1);">
    <path d="M0 0h3v2H0z" fill="#002b7f" />
    <path d="M1 0h2v2H1z" fill="#fcd116" />
    <path d="M2 0h1v2H2z" fill="#ce1126" />
</svg>',
'hu' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" width="20" height="15" style="border: 1px solid rgba(0,0,0,0.1);">
    <path d="M0 0h3v2H0z" fill="#436f4d" />
    <path d="M0 0h3v1.333H0z" fill="#fff" />
    <path d="M0 0h3v.667H0z" fill="#ce1126" />
</svg>',
'de' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 3" width="20" height="15" style="border: 1px solid rgba(0,0,0,0.1);">
    <path d="M0 0h5v3H0z" />
    <path d="M0 1h5v2H0z" fill="#d00" />
    <path d="M0 2h5v1H0z" fill="#ffce00" />
</svg>',
];
@endphp

<li class="nav-item dropdown">
    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open language menu">
        <span class="avatar avatar-xs shadow-none border-0 bg-transparent" style="width: 24px; height: 18px;">
            {!! $svgFlags[$locale] ?? $svgFlags['en'] !!}
        </span>
        <div class="d-none d-xl-block ps-2">
            <div>{{ $names[$locale] ?? 'English' }}</div>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        @foreach($names as $lang => $name)
        <a href="{{ route('lang.switch', $lang) }}" class="dropdown-item">
            <span class="avatar avatar-xs me-2 shadow-none border-0 bg-transparent" style="width: 20px; height: 15px;">
                {!! $svgFlags[$lang] !!}
            </span>
            {{ $name }}
        </a>
        @endforeach
    </div>
</li>