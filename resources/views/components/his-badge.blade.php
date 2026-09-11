@props(['model'])

@if($model && method_exists($model, 'isFromHis') && $model->isFromHis())
    <span {{ $attributes->merge(['class' => 'his-badge', 'title' => 'این رکورد از نرم‌افزار مطب (HIS) آمده و فقط‌خواندنی است']) }}>
        از HIS
    </span>
@endif
