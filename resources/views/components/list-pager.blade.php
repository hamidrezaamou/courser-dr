@props([
    'paginator',
    'label' => 'مورد',
])

@php
    $options = \App\Support\ListPagination::OPTIONS;
    $current = $paginator->perPage();
    $query = request()->except(['page', 'per_page']);
@endphp

@if ($paginator->total() > 0)
    <div {{ $attributes->class('list-pager') }}>
        <form method="GET" class="list-pager__per">
            @foreach ($query as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nested)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nested }}">
                    @endforeach
                @elseif ($value !== null && $value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label for="list-per-page">تعداد در صفحه</label>
            <select id="list-per-page" name="per_page" class="field-input" onchange="this.form.submit()">
                @foreach ($options as $n)
                    <option value="{{ $n }}" @selected($current === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </form>

        <div class="list-pager__meta">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            از {{ number_format($paginator->total()) }} {{ $label }}
        </div>

        <div class="list-pager__links">
            {{ $paginator->withQueryString()->onEachSide(1)->links() }}
        </div>
    </div>
@endif
