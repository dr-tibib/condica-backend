<div class="row">
    <div class="col-md-12">
        <div class="card mb-4 overflow-hidden">
            <div class="card-body d-flex align-items-center p-4">
                <div class="me-4">
                    @if($widget['tenant']->logo)
                        <img src="{{ asset('storage/' . $widget['tenant']->logo) }}" alt="{{ $widget['tenant']->company_name }}" class="rounded shadow-sm" style="width: 100px; height: 100px; object-fit: contain; background: #fff; padding: 5px; border: 1px solid #eee;">
                    @else
                        <div class="rounded shadow-sm d-flex align-items-center justify-content-center bg-light" style="width: 100px; height: 100px; border: 1px solid #eee;">
                            <i class="la la-building la-3x text-muted"></i>
                        </div>
                    @endif
                </div>
                <div class="flex-grow-1">
                    <h2 class="mb-1">{{ $widget['tenant']->company_name ?? $widget['tenant']->id }}</h2>
                    <p class="text-muted mb-3">
                        <i class="la la-id-card"></i> ID: <strong>{{ $widget['tenant']->id }}</strong>
                        @if($widget['tenant']->domains->count() > 0)
                            <span class="ms-3"><i class="la la-globe"></i> {{ $widget['tenant']->domains->first()->domain }}</span>
                        @endif
                    </p>
                    <div class="d-flex gap-2 mt-2">
                        @if($widget['tenant']->domains->count() > 0)
                            @php
                                $domain = $widget['tenant']->domains->first()->domain;
                                $adminUrl = (app()->isLocal() ? 'http://' : 'https://') . $domain . '/admin';
                                $kioskUrl = (app()->isLocal() ? 'http://' : 'https://') . $domain;
                            @endphp
                            <a href="{{ $adminUrl }}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="la la-external-link"></i> {{ __('central.visit_admin') }}
                            </a>
                            <a href="{{ $kioskUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="la la-tablet"></i> {{ __('Open Kiosk') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <div class="h3 mb-0">{{ $widget['tenant']->users->count() }}</div>
                    <div class="small text-muted text-uppercase fw-bold">{{ __('central.central_users') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
