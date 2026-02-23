<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 overflow-hidden" style="height: 300px; position: relative;">
            <iframe
                width="100%"
                height="100%"
                style="border:0"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps/embed/v1/place?key={{ config('services.google_places.key') }}&q={{ $widget['place']->latitude }},{{ $widget['place']->longitude }}">
            </iframe>
            <div class="position-absolute bottom-0 start-0 m-3 p-2 bg-white rounded shadow-sm border border-light d-flex align-items-center" style="z-index: 10;">
                <i class="la la-map-marker la-2x text-primary me-2"></i>
                <div>
                    <div class="fw-bold">{{ $widget['place']->name }}</div>
                    <div class="small text-muted">{{ $widget['place']->address }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
