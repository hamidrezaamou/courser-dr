@extends('prints.layout')

@section('title', 'چاپ همه برگه‌ها')

@section('styles')
    .print-all-sheet { page-break-after: always; margin-bottom: 2rem; }
    .print-all-sheet:last-child { page-break-after: auto; margin-bottom: 0; }
    .print-all-title { text-align: center; font-size: 12px; color: #666; margin-bottom: 8px; }
    .print-custom { font-size: 15px; line-height: 2; direction: rtl; text-align: right; }
    .print-custom .print-title { text-align: center; font-size: 19px; font-weight: 800; margin-bottom: 14px; }
    .print-custom .print-sheet-header {
        display: flex; align-items: center; justify-content: space-between; gap: 12px;
        margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #111;
    }
    .print-custom .print-sheet-footer {
        margin-top: 18px; padding-top: 10px; border-top: 1px solid #777; font-size: 12px; color: #333;
    }
    .print-custom .print-brand-img,
    .print-custom .print-sheet-header img { max-height: 64px; max-width: 160px; object-fit: contain; }
    .print-custom .print-inline-img { max-width: 100%; height: auto; display: block; margin: 6px auto; }
    .print-custom .print-hr { border: 0; border-top: 2px solid #111; margin: 12px 0; }
    .print-custom .print-info-box { border: 1px solid #111; border-radius: 6px; padding: 10px 12px; margin: 10px 0; }
    .print-custom table.print-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .print-custom table.print-table td { border: 1px solid #111; padding: 6px 8px; vertical-align: top; }
    .print-overlay-stage { position: relative; width: 100%; min-height: 200mm; aspect-ratio: 210 / 297; background: #fff; overflow: hidden; }
    .print-overlay-bg { width: 100%; height: 100%; object-fit: contain; display: block; position: absolute; inset: 0; }
    .print-overlay-pdf { width: 100%; height: 100%; border: 0; display: block; position: absolute; inset: 0; }
    .print-overlay-blank { position: absolute; inset: 0; background: #fff; }
    .print-overlay-tag { position: absolute; transform: translate(-50%, -50%); font-size: calc(10.5pt * var(--tag-size, 1)); font-weight: 700; white-space: nowrap; line-height: 1.15; }
    .print-overlay-obj { position: absolute; box-sizing: border-box; overflow: hidden; line-height: 1.35; }
    .print-overlay-obj--image img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .print-overlay-obj--text, .print-overlay-obj--tag { display: flex; align-items: center; }
@endsection

@section('content')
    @foreach($sheets as $sheet)
        <div class="print-all-sheet">
            @if(($sheet['show_header_spacer'] ?? true) && empty($sheet['builtin']))
                <div class="header-spacer"></div>
            @endif
            <div class="print-all-title">{{ $sheet['title'] }}</div>
            <div class="print-custom">{!! $sheet['html'] ?? '' !!}</div>
            @if(($sheet['show_signature'] ?? true) && empty($sheet['builtin']))
                <div class="sig">
                    <div class="box">
                        <strong>{{ $doctorName }}</strong>
                        @if(!empty($signatureUrl))
                            <img src="{{ $signatureUrl }}" alt="امضا" style="max-height:64px;display:block;margin:8px auto 0;">
                        @endif
                        <div class="line"></div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
    <script>window.addEventListener('load', function(){ window.print(); });</script>
@endsection
