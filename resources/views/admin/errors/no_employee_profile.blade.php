@extends(backpack_view('layouts.top_left'))

@section('content')
<div class="row">
    <div class="col-md-12 text-center">
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">No Employee Profile Found!</h4>
            <p>Your user account is not linked to an employee profile. Please contact your administrator to set up your employee profile.</p>
        </div>
    </div>
</div>
@endsection
