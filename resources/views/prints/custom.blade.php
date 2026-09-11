@extends('prints.layout')

@section('title', 'برگه چاپ')

@section('styles')
    .print-custom { font-size: 15px; line-height: 2; direction: rtl; text-align: right; }
    .print-custom .print-title { text-align: center; font-size: 19px; font-weight: 800; margin-bottom: 14px; }
    .print-custom .print-date { text-align: left; direction: ltr; margin-bottom: 1rem; }
    .print-custom .print-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin: 12px 0; }
    .print-custom .print-body { text-align: justify; }
    .print-custom .print-note { margin-top: 16px; padding: 10px; border-right: 3px solid #000; }
    .print-custom .print-rx-box { min-height: 180px; border: 1px solid #000; border-radius: 6px; padding: 12px; margin-top: 10px; }
    .print-custom .print-muted { color: #666; font-size: 12px; margin-top: 8px; }
    .print-custom ul { margin: 12px 24px 12px 0; line-height: 2; }
    .print-custom p { margin: 0 0 .65rem; }
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
    .print-overlay-stage { position: relative; width: 100%; min-height: 240mm; aspect-ratio: 210 / 297; background: #fff; overflow: hidden; }
    .print-overlay-bg { width: 100%; height: 100%; object-fit: contain; display: block; position: absolute; inset: 0; }
    .print-overlay-pdf { width: 100%; height: 100%; border: 0; display: block; position: absolute; inset: 0; }
    .print-overlay-blank { position: absolute; inset: 0; background: #fff; }
    .print-overlay-tag { position: absolute; transform: translate(-50%, -50%); font-size: calc(10.5pt * var(--tag-size, 1)); font-weight: 700; white-space: nowrap; line-height: 1.15; }
    .print-overlay-obj { position: absolute; box-sizing: border-box; overflow: hidden; line-height: 1.35; }
    .print-overlay-obj--image img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .print-overlay-obj--text, .print-overlay-obj--tag { display: flex; align-items: center; }
@endsection

@section('content')
    @if(($showHeaderSpacer ?? true))
        <div class="header-spacer"></div>
    @endif
    <div class="print-custom @if(!empty($isOverlay)) print-custom--overlay @endif">
        {!! $customHtml ?? '' !!}
    </div>
    @if(($showSignature ?? true))
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
@endsection
