@extends('prints.layout')

@section('title', 'معرفی به آزمایشگاه')

@section('styles')
    .letter { font-size: 16px; line-height: 2.1; text-align: justify; margin-top: 1rem; }
    .tests { margin: 1.25rem 0 1.25rem 1.5rem; line-height: 2; }
    .tracking { margin-top: 1.5rem; font-size: 15px; }
@endsection

@section('content')
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> {{ fa_digits($todayJalali) }}</div>
    <div class="letter">
        <p><strong>مدیریت محترم آزمایشگاه</strong></p>
        <p>
            احتراماً آقای/خانم <strong>{{ fa_digits($surgery->patient_name) }}</strong>
            به کد ملی <strong>{{ fa_digits($surgery->national_code) }}</strong>
            جهت انجام آزمایشات زیر به حضورتان معرفی می‌گردد:
        </p>
    </div>
    <ul class="tests">
        <li>CBC - Diff</li>
        <li>BUN - Cr</li>
        <li>FBS</li>
    </ul>
    <div class="tracking">کد رهگیری: <strong>......................</strong></div>
    <div class="sig">
        <div class="box">
            <strong>{{ $doctorName }}</strong>
            <div class="line"></div>
        </div>
    </div>
@endsection
