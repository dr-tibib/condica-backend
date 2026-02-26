@php
    $currentYear = date('Y');
    $currentMonth = date('n');
    $modalId = 'downloadCondicaModal';
@endphp

<div class="btn-group" role="group">
    <a href="{{ url($crud->route.'/download-condica') }}?year={{ $currentYear }}&month={{ $currentMonth }}" 
       class="btn btn-sm btn-success text-white btn-download-condica"
       onclick="this.classList.add('disabled'); this.innerHTML = '<span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span> Generare...';">
        <i class="la la-download"></i> Condica de prezență
    </a>
    <button type="button" class="btn btn-sm btn-success text-white dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="visually-hidden">Toggle Dropdown</span>
    </button>
    <div class="dropdown-menu shadow border-0">
        <button type="button" class="dropdown-item" id="btnOpenDownloadCondicaModal">
            <i class="la la-calendar"></i> Specifică luna...
        </button>
    </div>
</div>

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header">
                <h5 class="modal-title">Descarcă Condica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ url($crud->route.'/download-condica') }}" method="GET" onsubmit="var btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<span class=\'spinner-border spinner-border-sm\' role=\'status\' aria-hidden=\'true\'></span> Generare...';">
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-dark">An</label>
                        <select name="year" class="form-select">
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-dark">Luna</label>
                        <select name="month" class="form-select">
                            @php
                                $months = [
                                    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
                                    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
                                    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
                                ];
                            @endphp
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == date('n') ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="submit" class="btn btn-success text-white">Descarcă</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function() {
        var modalId = '{{ $modalId }}';
        var triggerId = 'btnOpenDownloadCondicaModal';
        
        function setup() {
            var modalEl = document.getElementById(modalId);
            var triggerEl = document.getElementById(triggerId);
            
            if (!modalEl || !triggerEl) return;

            if (modalEl.parentElement !== document.body) {
                var existing = document.body.querySelector('#' + modalId);
                if (existing && existing !== modalEl) existing.remove();
                document.body.appendChild(modalEl);
            }

            triggerEl.onclick = function(e) {
                e.preventDefault();
                try {
                    var m = bootstrap.Modal.getOrCreateInstance(modalEl);
                    if (m) m.show();
                } catch (err) {
                    console.error('Modal show error:', err);
                }
            };
        }

        setup();
        if (window.jQuery) {
            $(document).ajaxComplete(setup);
        }

        // Handle re-enabling buttons after a while because file downloads don't trigger navigation
        window.addEventListener('blur', function() {
            setTimeout(function() {
                document.querySelectorAll('.btn-download-condica').forEach(function(btn) {
                    btn.classList.remove('disabled');
                    btn.innerHTML = '<i class="la la-download"></i> Condica de prezență';
                });
                var modal = document.getElementById(modalId);
                if (modal) {
                    var btn = modal.querySelector('button[type=submit]');
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = 'Descarcă';
                    }
                    // Close modal after download start
                    var m = bootstrap.Modal.getInstance(modal);
                    if (m) m.hide();
                }
            }, 2000);
        });
    })();
</script>
