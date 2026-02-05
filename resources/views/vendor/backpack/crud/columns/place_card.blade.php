@php
    $photoReference = null;
    $name = $entry->name;
    $address = $entry->address;

    // Check if we can get a photo reference and override name/address if linked
    if ($entry instanceof \App\Models\DelegationPlace) {
        $photoReference = $entry->photo_reference;
    } elseif ($entry instanceof \App\Models\Delegation) {
        if ($entry->delegationPlace) {
            $name = $entry->delegationPlace->name;
            $address = $entry->delegationPlace->address;
            $photoReference = $entry->delegationPlace->photo_reference;
        }
    }

    $apiKey = config('services.google_places.key');
    $imageUrl = $photoReference
        ? (str_starts_with($photoReference, 'http') ? $photoReference : "https://maps.googleapis.com/maps/api/place/photo?maxwidth=200&photo_reference={$photoReference}&key={$apiKey}")
        : null;
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
