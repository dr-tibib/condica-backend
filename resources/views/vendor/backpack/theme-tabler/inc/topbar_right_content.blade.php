<div class="nav-item dropdown">
    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open language menu">
        @php
            $locale = App::getLocale();
            $flags = [
                'en' => '🇬🇧',
                'ro' => '🇷🇴',
                'hu' => '🇭🇺',
                'de' => '🇩🇪',
            ];
            $names = [
                'en' => 'English',
                'ro' => 'Română',
                'hu' => 'Magyar',
                'de' => 'Deutsch',
            ];
        @endphp
        <span class="avatar avatar-sm" style="background: transparent; font-size: 1.5rem;">{{ $flags[$locale] ?? '🇬🇧' }}</span>
        <div class="d-none d-xl-block ps-2">
            <div>{{ $names[$locale] ?? 'English' }}</div>
        </div>
    </a>
    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        @foreach($flags as $lang => $flag)
        <a href="{{ route('lang.switch', $lang) }}" class="dropdown-item">
            <span class="avatar avatar-sm me-2" style="background: transparent; font-size: 1.2rem;">{{ $flag }}</span>
            {{ $names[$lang] }}
        </a>
        @endforeach
    </div>
</div>
