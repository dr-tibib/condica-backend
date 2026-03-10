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
                <h3 class="card-title h5">Last Import</h3>

                @if($lastSyncLog)
                    <div class="mb-2">
                        <span class="badge bg-{{ $lastSyncLog->status === 'completed' ? 'success' : ($lastSyncLog->status === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($lastSyncLog->status) }}
                        </span>
                    </div>

                    <p class="mb-1"><strong>Date:</strong> {{ optional($lastSyncLog->finished_at ?? $lastSyncLog->created_at)->format('Y-m-d H:i') }}</p>
                    <p class="mb-1"><strong>Imported:</strong> {{ $lastSyncLog->created_rows }}</p>
                    <p class="mb-1"><strong>Updated:</strong> {{ $lastSyncLog->updated_rows }}</p>
                    <p class="mb-3"><strong>Failed:</strong> {{ $lastSyncLog->failed_rows }}</p>

                    <a href="{{ backpack_url('products/sync-logs/'.$lastSyncLog->id.'/show') }}" class="btn btn-outline-primary btn-sm">
                        View sync log
                    </a>
                @else
                    <p class="text-body-secondary mb-0">No product import has been run yet.</p>
                @endif
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
