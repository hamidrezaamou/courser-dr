<x-app-layout>
    <div
        class="py-4 sm:py-6"
        x-data="surgeryBookingForm({
            optionsUrl: @js(route('appointments.surgery-options')),
            slotsUrl: @js(route('appointments.slots')),
            hospitals: @js(($hospitals ?? collect())->map(fn ($h) => ['id' => $h->id, 'name' => $h->name])->values()),
            initialDate: @js(old('scheduled_date')),
            initialTime: @js(old('scheduled_time')),
            initialHospitalId: @js(old('hospital_id')),
            initialTypeId: @js(old('surgery_type_id')),
            initialSubtypeId: @js(old('surgery_subtype_id')),
            initialTypeLabel: @js(old('surgery_type')),
            initialException: @js((bool) old('is_exception')),
            initialEyeSide: @js(old('eye_side')),
            cooldownUrl: @js(route('surgery-appointments.cooldown-check')),
            patientId: @js($patient->id),
        })"
    >
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('surgery-appointments.store', $patient) }}"
                enctype="multipart/form-data"
                class="panel fade-up space-y-5 p-4 sm:p-6"
                @submit="prepareSubmit($event)"
            >
                @csrf

                @include('surgery-appointments.partials.form-fields', [
                    'patient' => $patient,
                    'showUploads' => true,
                    'dateAsSelect' => true,
                ])

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary btn-accent-warm">ثبت نوبت عمل</button>
                    <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>

    @include('surgery-appointments.partials.booking-script')
    <script>window.name = 'surgery-register';</script>
</x-app-layout>
