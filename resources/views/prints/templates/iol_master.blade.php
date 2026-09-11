@extends('prints.layout')

@section('title', 'تعیین لنز IOL Master')

@section('styles')
    .letter { font-size: 16px; line-height: 2.4; text-align: justify; margin-top: 1rem; }
@endsection

@section('content')
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> {{ fa_digits($todayJalali) }}</div>
    <div class="letter">
        <p><strong>همکار محترم اپتومتری</strong></p>
        <p>
            آقای/خانم <strong>{{ fa_digits($surgery->patient_name) }}</strong>
            با کد ملی <strong>{{ fa_digits($surgery->national_code) }}</strong>
            جهت تعیین لنز (IOL Master) برای تاریخ عمل
            <strong>{{ fa_digits($surgeryJalali) }}</strong>
            معرفی می‌گردد.
        </p>
    </div>
    <div class="sig">
        <div class="box">
            <strong>{{ $doctorName }}</strong>
            <div class="line"></div>
        </div>
    </div>
@endsection
