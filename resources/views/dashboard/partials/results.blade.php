@include('dashboard.partials.results-head', ['patients' => $patients, 'search' => $search, 'stats' => $stats])
@include('dashboard.partials.results-body', ['patients' => $patients, 'search' => $search])
