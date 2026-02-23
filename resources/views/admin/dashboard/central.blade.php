@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid p-0">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="h1 mb-0">{{ $stats['tenants_count'] }}</div>
                    <div class="text-muted mb-3">{{ __('central.tenants') }}</div>
                    <a href="{{ backpack_url('tenant') }}" class="btn btn-primary btn-sm">{{ __('central.manage_tenants') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="h1 mb-0">{{ $stats['domains_count'] }}</div>
                    <div class="text-muted mb-3">{{ __('central.domains') }}</div>
                    <a href="{{ backpack_url('domain') }}" class="btn btn-secondary btn-sm">{{ __('central.manage_domains') }}</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="h1 mb-0">{{ $stats['central_users_count'] }}</div>
                    <div class="text-muted mb-3">{{ __('central.central_users') }}</div>
                    <a href="{{ backpack_url('central-user') }}" class="btn btn-info btn-sm">{{ __('central.manage_users') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('central.latest_tenants') }}</h3>
                    <div class="card-actions">
                        <a href="{{ backpack_url('tenant') }}" class="btn btn-link btn-sm">{{ __('central.view_all') }}</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>{{ __('central.id') }}</th>
                                <th>{{ __('central.company_name') }}</th>
                                <th>{{ __('central.domains') }}</th>
                                <th>{{ __('central.created_at') }}</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                            <tr>
                                <td>{{ $tenant->id }}</td>
                                <td>{{ $tenant->company_name ?? __('central.n_a') }}</td>
                                <td>
                                    @foreach($tenant->domains as $domain)
                                        <span class="badge bg-blue-lt">{{ $domain->domain }}</span>
                                    @endforeach
                                </td>
                                <td class="text-muted">{{ $tenant->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <a href="{{ backpack_url('tenant/'.$tenant->id.'/show') }}" class="btn btn-light btn-xs">{{ __('central.view') }}</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">{{ __('central.no_tenants') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
