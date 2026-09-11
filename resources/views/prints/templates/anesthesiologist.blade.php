@extends('prints.layout')

@section('title', 'معرفی به پزشک بیهوشی')

@section('styles')
    .letter { font-size: 16px; line-height: 2.2; text-align: justify; margin-top: 1rem; }
@endsection

@section('content')
    <div class="header-spacer"></div>
    <div class="header-date"><strong>تاریخ:</strong> {{ fa_digits($todayJalali) }}</div>
    <div class="letter">
        <p>
            احتراماً آقای/خانم <strong>{{ fa_digits($surgery->patient_name) }}</strong>
            به کد ملی <strong>{{ fa_digits($surgery->national_code) }}</strong>
            جهت عمل <strong>{{ fa_digits($surgery->surgery_type ?: '...........') }}</strong>
            در تاریخ <strong>{{ fa_digits($surgeryJalali) }}</strong>
            و تست GA به حضورتان معرفی می‌گردد.
        </p>
    </div>
    <div class="sig">
        <div class="box">
            <strong>{{ $doctorName }}</strong>
            <div class="line"></div>
        </div>
    </div>
@endsection
