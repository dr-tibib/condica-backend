@extends(backpack_view('blank'))

@section('header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Products</h1>
    </div>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <div class="col-md-6 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="card-title h5">WinMentor Import Actions</h3>
                <p class="text-body-secondary mb-3">
                    Trigger a product sync from the WinMentor <code>site.csv</code> file.
                </p>

                <form method="POST" action="{{ route('backpack.products.sync-site-csv') }}" class="mt-auto">
                    @csrf
                    <button type="submit" class="btn btn-primary w-50">
                        Sync site.csv
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="card-title h5">Last site.csv Import</h3>

                @if($lastSiteSyncLog)
                    <div class="mb-2">
                        <span class="badge bg-{{ $lastSiteSyncLog->status === 'completed' ? 'success' : ($lastSiteSyncLog->status === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($lastSiteSyncLog->status) }}
                        </span>
                    </div>

                    <p class="mb-1"><strong>Date:</strong> {{ optional($lastSiteSyncLog->finished_at ?? $lastSiteSyncLog->created_at)->format('Y-m-d H:i') }}</p>
                    <p class="mb-1"><strong>Imported:</strong> {{ $lastSiteSyncLog->created_rows }}</p>
                    <p class="mb-1"><strong>Updated:</strong> {{ $lastSiteSyncLog->updated_rows }}</p>
                    <p class="mb-3"><strong>Failed:</strong> {{ $lastSiteSyncLog->failed_rows }}</p>

                    <a href="{{ backpack_url('products/sync-logs/'.$lastSiteSyncLog->id.'/show') }}" class="btn btn-outline-primary btn-sm">
                        View sync log
                    </a>
                @else
                    <p class="text-body-secondary mb-0">No site.csv import has been run yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="card-title h5">Google Drive Image Import</h3>
                <p class="text-body-secondary mb-3">
                    Trigger a product image sync from the Google Drive folder.
                </p>

                <form method="POST" action="{{ route('backpack.products.sync-images-from-google-drive') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-50">
                        Import images from Google Drive
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="card-title h5">Last Google Drive Import</h3>

                @if($lastGoogleDriveSyncLog)
                    <div class="mb-2">
                        <span class="badge bg-{{ $lastGoogleDriveSyncLog->status === 'completed' ? 'success' : ($lastGoogleDriveSyncLog->status === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($lastGoogleDriveSyncLog->status) }}
                        </span>
                    </div>

                    <p class="mb-1"><strong>Date:</strong> {{ optional($lastGoogleDriveSyncLog->finished_at ?? $lastGoogleDriveSyncLog->created_at)->format('Y-m-d H:i') }}</p>
                    <p class="mb-1"><strong>Updated:</strong> {{ $lastGoogleDriveSyncLog->updated_rows }}</p>
                    <p class="mb-1"><strong>Skipped:</strong> {{ $lastGoogleDriveSyncLog->skipped_rows }}</p>
                    <p class="mb-3"><strong>Failed:</strong> {{ $lastGoogleDriveSyncLog->failed_rows }}</p>

                    <a href="{{ backpack_url('products/sync-logs/'.$lastGoogleDriveSyncLog->id.'/show') }}" class="btn btn-outline-primary btn-sm">
                        View sync log
                    </a>
                @else
                    <p class="text-body-secondary mb-0">No Google Drive image import has been run yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title h5">BunnyCDN Image Sync</h3>
                    <p class="text-body-secondary mb-3">
                        Trigger image sync to the BunnyCDN storage.
                    </p>

                    <form method="POST" action="{{ route('backpack.products.sync-images-to-bunny') }}" class="mt-auto">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-50">
                            Sync images to Bunny
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="card-title h5">Last Bunny Upload</h3>

                    @if($lastBunnySyncLog)
                        <div class="mb-2">
                            <span class="badge bg-{{ $lastBunnySyncLog->status === 'completed' ? 'success' : ($lastBunnySyncLog->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($lastBunnySyncLog->status) }}
                            </span>
                        </div>

                        <p class="mb-1"><strong>Date:</strong> {{ optional($lastBunnySyncLog->finished_at ?? $lastBunnySyncLog->created_at)->format('Y-m-d H:i') }}</p>
                        <p class="mb-1"><strong>Imported:</strong> {{ $lastBunnySyncLog->created_rows }}</p>
                        <p class="mb-1"><strong>Updated:</strong> {{ $lastBunnySyncLog->updated_rows }}</p>
                        <p class="mb-3"><strong>Failed:</strong> {{ $lastBunnySyncLog->failed_rows }}</p>

                        <a href="{{ backpack_url('products/sync-logs/'.$lastBunnySyncLog->id.'/show') }}" class="btn btn-outline-primary btn-sm">
                            View sync log
                        </a>
                    @else
                        <p class="text-body-secondary mb-0">No Bunny image upload has been run yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="card-title h5">Products Module</h3>
                <p class="text-body-secondary mb-0">
                    Use the Products CRUD to manage records and the Sync Logs area to inspect imports and failures.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
