@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0"><i class="la la-cube mr-1"></i> Products Dashboard</h1>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success shadow-sm border-0 mb-4">
                    <i class="la la-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger shadow-sm border-0 mb-4">
                    <i class="la la-exclamation-circle me-1"></i> {{ session('error') }}
                </div>
            @endif
        </div>
    </div>

    <div class="row mb-4">
        {{-- WinMentor Sync --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h3 class="card-title h5 mb-0 text-primary">
                        <i class="la la-file-csv me-1"></i> WinMentor Import
                    </h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small mb-4">
                        Trigger a product sync from the WinMentor <code>site.csv</code> file.
                    </p>

                    <div class="mb-4 mt-auto">
                        @if($lastSiteSyncLog)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Status</span>
                                <span class="badge bg-{{ $lastSiteSyncLog->status === 'completed' ? 'success' : ($lastSiteSyncLog->status === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $lastSiteSyncLog->status === 'completed' ? 'success' : ($lastSiteSyncLog->status === 'failed' ? 'danger' : 'warning') }} border-0 px-2 py-1">
                                    {{ ucfirst($lastSiteSyncLog->status) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Last run</span>
                                <span class="small fw-bold">{{ optional($lastSiteSyncLog->finished_at ?? $lastSiteSyncLog->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-success">
                                        <div class="small fw-bold">{{ $lastSiteSyncLog->created_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Created</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-primary">
                                        <div class="small fw-bold">{{ $lastSiteSyncLog->updated_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Updated</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-danger">
                                        <div class="small fw-bold">{{ $lastSiteSyncLog->failed_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Failed</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light border-0 small text-center py-2 mb-0">
                                No site.csv import has been run yet.
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('backpack.products.sync-site-csv') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="la la-refresh me-1"></i> Sync site.csv
                            </button>
                        </form>
                        @if($lastSiteSyncLog)
                        <a href="{{ backpack_url('products/sync-logs/'.$lastSiteSyncLog->id.'/show') }}" class="btn btn-link btn-sm text-decoration-none text-muted">
                            <i class="la la-external-link-alt me-1"></i> View detailed log
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Google Drive Sync --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h3 class="card-title h5 mb-0 text-success">
                        <i class="la la-google-drive me-1"></i> Google Drive Import
                    </h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small mb-4">
                        Trigger a product image sync from the Google Drive folder.
                    </p>

                    <div class="mb-4 mt-auto">
                        @if($lastGoogleDriveSyncLog)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Status</span>
                                <span class="badge bg-{{ $lastGoogleDriveSyncLog->status === 'completed' ? 'success' : ($lastGoogleDriveSyncLog->status === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $lastGoogleDriveSyncLog->status === 'completed' ? 'success' : ($lastGoogleDriveSyncLog->status === 'failed' ? 'danger' : 'warning') }} border-0 px-2 py-1">
                                    {{ ucfirst($lastGoogleDriveSyncLog->status) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Last run</span>
                                <span class="small fw-bold">{{ optional($lastGoogleDriveSyncLog->finished_at ?? $lastGoogleDriveSyncLog->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-primary">
                                        <div class="small fw-bold">{{ $lastGoogleDriveSyncLog->updated_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Updated</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-info">
                                        <div class="small fw-bold">{{ $lastGoogleDriveSyncLog->skipped_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Skipped</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-danger">
                                        <div class="small fw-bold">{{ $lastGoogleDriveSyncLog->failed_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Failed</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light border-0 small text-center py-2 mb-0">
                                No Google Drive import has been run yet.
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('backpack.products.sync-images-from-google-drive') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-success w-100 shadow-sm">
                                <i class="la la-image me-1"></i> Import from Drive
                            </button>
                        </form>
                        @if($lastGoogleDriveSyncLog)
                        <a href="{{ backpack_url('products/sync-logs/'.$lastGoogleDriveSyncLog->id.'/show') }}" class="btn btn-link btn-sm text-decoration-none text-muted">
                            <i class="la la-external-link-alt me-1"></i> View detailed log
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- BunnyCDN Sync --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom-0">
                    <h3 class="card-title h5 mb-0 text-info">
                        <i class="la la-cloud-upload-alt me-1"></i> BunnyCDN Sync
                    </h3>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small mb-4">
                        Trigger image sync to the BunnyCDN storage.
                    </p>

                    <div class="mb-4 mt-auto">
                        @if($lastBunnySyncLog)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Status</span>
                                <span class="badge bg-{{ $lastBunnySyncLog->status === 'completed' ? 'success' : ($lastBunnySyncLog->status === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $lastBunnySyncLog->status === 'completed' ? 'success' : ($lastBunnySyncLog->status === 'failed' ? 'danger' : 'warning') }} border-0 px-2 py-1">
                                    {{ ucfirst($lastBunnySyncLog->status) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Last run</span>
                                <span class="small fw-bold">{{ optional($lastBunnySyncLog->finished_at ?? $lastBunnySyncLog->created_at)->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-success">
                                        <div class="small fw-bold">{{ $lastBunnySyncLog->created_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">New</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-primary">
                                        <div class="small fw-bold">{{ $lastBunnySyncLog->updated_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Updated</div>
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <div class="bg-light rounded p-2 text-danger">
                                        <div class="small fw-bold">{{ $lastBunnySyncLog->failed_rows }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">Failed</div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light border-0 small text-center py-2 mb-0">
                                No Bunny image upload has been run yet.
                            </div>
                        @endif
                    </div>

                    <div class="d-grid gap-2">
                        <form method="POST" action="{{ route('backpack.products.sync-images-to-bunny') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-info w-100 shadow-sm">
                                <i class="la la-upload me-1"></i> Sync to Bunny
                            </button>
                        </form>
                        @if($lastBunnySyncLog)
                        <a href="{{ backpack_url('products/sync-logs/'.$lastBunnySyncLog->id.'/show') }}" class="btn btn-link btn-sm text-decoration-none text-muted">
                            <i class="la la-external-link-alt me-1"></i> View detailed log
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 bg-primary text-white rounded p-3">
                        <i class="la la-info-circle la-2x"></i>
                    </div>
                    <div>
                        <h4 class="h5 mb-1 fw-bold">Management Quick Links</h4>
                        <p class="text-muted small mb-0">
                            Manage your records using the <a href="{{ backpack_url('products/products') }}" class="fw-bold">Products CRUD</a>
                            or inspect historical imports in the <a href="{{ backpack_url('products/sync-logs') }}" class="fw-bold">Sync Logs</a> area.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
