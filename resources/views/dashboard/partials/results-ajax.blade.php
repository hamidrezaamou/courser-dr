<div id="patient-ajax-head">
    @include('dashboard.partials.results-head', ['patients' => $patients, 'search' => $search, 'stats' => $stats])
</div>
<div id="patient-ajax-body">
    @include('dashboard.partials.results-body', ['patients' => $patients, 'search' => $search])
</div>
