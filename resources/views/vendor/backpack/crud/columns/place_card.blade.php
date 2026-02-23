@php
    $photoReference = null;
    $name = $entry->name;
    $address = $entry->address;

    $apiKey = config('services.google_places.key');
    $imageUrl = null;
    if ($entry instanceof \App\Models\DelegationPlace) {
        $imageUrl = $entry->photo_url;
    } elseif ($entry instanceof \App\Models\Delegation) {
        $stops = $entry->stops;
        if ($stops->count() > 0) {
            $firstStop = $stops->first();
            $name = $firstStop->name;
            $address = $firstStop->address;
            if ($firstStop->delegationPlace) {
                 $imageUrl = $firstStop->delegationPlace->photo_url;
            }
            if ($stops->count() > 1) {
                $name .= ' <span class="badge bg-blue text-white">+' . ($stops->count() - 1) . ' stops</span>';
            }
        } elseif ($entry->delegationPlace) {
            $name = $entry->delegationPlace->name;
            $address = $entry->delegationPlace->address;
            $imageUrl = $entry->delegationPlace->photo_url;
        }
    }
@endphp

<div class="d-flex align-items-center p-2 border rounded bg-white" style="max-width: 350px;">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $name }}" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
    @else
        <div class="rounded me-3 d-flex align-items-center justify-content-center bg-light text-secondary" style="width: 80px; height: 80px;">
            <i class="la la-map-marker la-2x"></i>
        </div>
    @endif

    <div style="overflow: hidden;">
        <div class="fw-bold text-truncate" title="{{ $name }}">{{ $name }}</div>
        <div class="small text-muted text-truncate" title="{{ $address }}">{{ $address }}</div>
    </div>
</div>
