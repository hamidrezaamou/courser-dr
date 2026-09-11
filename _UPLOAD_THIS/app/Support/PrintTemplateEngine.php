<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\SurgeryAppointment;

class PrintTemplateEngine
{
    /**
     * @return list<array{key:string,label:string,desc:string,custom?:bool}>
     */
    public static function builtinTypes(): array
    {
        return [
            ['key' => 'hospital', 'label' => 'معرفی به بیمارستان', 'desc' => 'برگه معرفی با مشخصات بیمار و عمل'],
            ['key' => 'anesthesiologist', 'label' => 'معرفی به پزشک بیهوشی', 'desc' => 'تست GA و بیهوشی قبل از عمل'],
            ['key' => 'laboratory', 'label' => 'معرفی به آزمایشگاه', 'desc' => 'درخواست آزمایش‌های قبل از عمل'],
            ['key' => 'iol_master', 'label' => 'تعیین لنز (IOL Master)', 'desc' => 'معرفی برای محاسبات قدرت لنز'],
            ['key' => 'prescription', 'label' => 'نسخه پزشکی', 'desc' => 'برگه نسخه با مشخصات بیمار'],
        ];
    }

    /**
     * @return list<array{id:string,label:string,desc:string,body:string}>
     */
    public static function customForms(): array
    {
        $raw = SiteSettings::effective('print.custom_forms', '[]');
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '' || ! self::isCustomType($id)) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'label' => trim((string) ($row['label'] ?? 'فرم سفارشی')) ?: 'فرم سفارشی',
                'desc' => trim((string) ($row['desc'] ?? '')),
                'body' => (string) ($row['body'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{id:string,label:string,desc:string,body:string}>  $forms
     */
    public static function saveCustomForms(array $forms): void
    {
        $normalized = [];
        foreach ($forms as $form) {
            $id = trim((string) ($form['id'] ?? ''));
            if ($id === '' || ! self::isCustomType($id)) {
                continue;
            }
            $normalized[] = [
                'id' => $id,
                'label' => trim((string) ($form['label'] ?? 'فرم سفارشی')) ?: 'فرم سفارشی',
                'desc' => trim((string) ($form['desc'] ?? '')),
                'body' => self::sanitizeHtml((string) ($form['body'] ?? '')),
            ];
        }

        SiteSettings::put('print.custom_forms', json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return list<array{key:string,label:string,desc:string,custom:bool}>
     */
    public static function types(): array
    {
        $custom = array_map(fn (array $form) => [
            'key' => $form['id'],
            'label' => $form['label'],
            'desc' => $form['desc'] ?: 'فرم سفارشی',
            'custom' => true,
        ], self::customForms());

        $builtin = array_map(fn (array $type) => array_merge($type, ['custom' => false]), self::builtinTypes());

        return array_merge($builtin, $custom);
    }

    /**
     * @return list<string>
     */
    public static function typeKeys(): array
    {
        return array_column(self::types(), 'key');
    }

    public static function isCustomType(string $type): bool
    {
        return str_starts_with($type, 'custom_');
    }

    public static function isKnownType(string $type): bool
    {
        return in_array($type, self::typeKeys(), true);
    }

    /**
     * @return list<array{key:string,label:string,sample:string}>
     */
    public static function tagDefinitions(): array
    {
        return [
            ['key' => 'name', 'label' => 'نام بیمار', 'sample' => 'علی رضایی'],
            ['key' => 'nationalCode', 'label' => 'کد ملی', 'sample' => '0012345678'],
            ['key' => 'mobile', 'label' => 'موبایل', 'sample' => '09123456789'],
            ['key' => 'date', 'label' => 'تاریخ نوبت/عمل', 'sample' => '1404/06/10'],
            ['key' => 'today', 'label' => 'تاریخ امروز', 'sample' => '1404/06/07'],
            ['key' => 'time', 'label' => 'ساعت', 'sample' => '10:30'],
            ['key' => 'slotNumber', 'label' => 'شماره نوبت / ساعت', 'sample' => '10:30'],
            ['key' => 'hospital', 'label' => 'نام بیمارستان', 'sample' => 'بیمارستان سجاد'],
            ['key' => 'hospitalAddress', 'label' => 'آدرس بیمارستان', 'sample' => 'مشهد، بلوار …'],
            ['key' => 'hospitalPhone', 'label' => 'تلفن بیمارستان', 'sample' => '051-12345678'],
            ['key' => 'surgeryType', 'label' => 'نوع عمل', 'sample' => 'فaco'],
            ['key' => 'eyeType', 'label' => 'چشم', 'sample' => 'راست'],
            ['key' => 'visitType', 'label' => 'نوع ویزیت', 'sample' => 'ویزیت مطب'],
            ['key' => 'doctorName', 'label' => 'نام پزشک', 'sample' => 'دکتر …'],
            ['key' => 'clinicPhone', 'label' => 'تلفن مطب', 'sample' => '051-87654321'],
            ['key' => 'description', 'label' => 'توضیحات', 'sample' => '—'],
        ];
    }

    public static function settingsKey(string $type): string
    {
        return 'print.templates.'.$type;
    }

    public static function customBody(string $type): string
    {
        if (self::isCustomType($type)) {
            foreach (self::customForms() as $form) {
                if ($form['id'] === $type) {
                    return trim($form['body']);
                }
            }

            return '';
        }

        return trim((string) SiteSettings::effective(self::settingsKey($type), ''));
    }

    public static function hasCustom(string $type): bool
    {
        if (self::isCustomType($type)) {
            return self::customBody($type) !== '';
        }

        return self::customBody($type) !== '';
    }

    /**
     * @return array<string, string>
     */
    public static function varsForSurgery(SurgeryAppointment $surgery, string $todayJalali, string $surgeryJalali, string $surgeryTime): array
    {
        $smsVars = AppointmentSms::vars($surgery, 'surgery');

        return array_merge($smsVars, [
            'nationalCode' => (string) $surgery->national_code,
            'mobile' => (string) $surgery->mobile,
            'today' => $todayJalali,
            'date' => $surgeryJalali,
            'time' => $surgeryTime,
            'slotNumber' => $surgeryTime,
            'hospitalAddress' => (string) ($surgery->hospital?->address ?: '—'),
            'hospitalPhone' => (string) ($surgery->hospital?->phone ?: '—'),
            'doctorName' => (string) config('clinic.doctor_name', ''),
            'clinicPhone' => (string) config('clinic.phone', ''),
            'visitType' => (string) ($surgery->surgery_type ?: 'عمل'),
        ]);
    }

    /**
     * @param  array<string, string>  $vars
     */
    public static function render(?string $template, array $vars): string
    {
        $html = trim((string) $template);
        if ($html === '') {
            return '';
        }

        foreach ($vars as $key => $value) {
            $html = str_replace('{'.$key.'}', e($value), $html);
        }

        return $html;
    }

    public static function defaultBody(string $type): string
    {
        if (self::isCustomType($type)) {
            return <<<'HTML'
<p class="print-date"><strong>تاریخ:</strong> {today}</p>
<p class="print-body">
  آقای/خانم <strong>{name}</strong> · کد ملی <strong>{nationalCode}</strong>
  · تاریخ <strong>{date}</strong>
</p>
HTML;
        }

        return match ($type) {
            'hospital' => <<<'HTML'
<div class="print-title">{hospital}</div>
<div class="print-grid">
  <p><strong>نام بیمار:</strong> {name}</p>
  <p><strong>کد ملی:</strong> {nationalCode}</p>
  <p><strong>موبایل:</strong> {mobile}</p>
  <p><strong>تاریخ عمل:</strong> {date}</p>
  <p><strong>نوع عمل:</strong> {surgeryType}</p>
  <p><strong>چشم:</strong> {eyeType}</p>
</div>
<p><strong>آدرس:</strong> {hospitalAddress} | <strong>تلفن:</strong> {hospitalPhone}</p>
<p class="print-note"><strong>نکته:</strong> لطفاً قبل از تاریخ عمل، قرارداد بیمه تکمیلی خود را با بیمارستان استعلام بگیرید.</p>
HTML,
            'anesthesiologist' => <<<'HTML'
<p class="print-date"><strong>تاریخ:</strong> {today}</p>
<p class="print-body">
  احتراماً آقای/خانم <strong>{name}</strong> به کد ملی <strong>{nationalCode}</strong>
  جهت عمل <strong>{surgeryType}</strong> در تاریخ <strong>{date}</strong> و تست GA به حضورتان معرفی می‌گردد.
</p>
HTML,
            'laboratory' => <<<'HTML'
<p class="print-date"><strong>تاریخ:</strong> {today}</p>
<p><strong>مدیریت محترم آزمایشگاه</strong></p>
<p class="print-body">
  احتراماً آقای/خانم <strong>{name}</strong> به کد ملی <strong>{nationalCode}</strong>
  جهت انجام آزمایشات زیر به حضورتان معرفی می‌گردد:
</p>
<ul>
  <li>CBC - Diff</li>
  <li>BUN - Cr</li>
  <li>FBS</li>
</ul>
<p>کد رهگیری: <strong>......................</strong></p>
HTML,
            'iol_master' => <<<'HTML'
<p class="print-date"><strong>تاریخ:</strong> {today}</p>
<p><strong>همکار محترم اپتومتری</strong></p>
<p class="print-body">
  آقای/خانم <strong>{name}</strong> با کد ملی <strong>{nationalCode}</strong>
  جهت تعیین لنز (IOL Master) برای تاریخ عمل <strong>{date}</strong> معرفی می‌گردد.
</p>
HTML,
            'prescription' => <<<'HTML'
<h2 class="print-title">نسخه پزشکی</h2>
<p class="print-date"><strong>تاریخ:</strong> {today}</p>
<div class="print-grid">
  <p><strong>نام بیمار:</strong> {name}</p>
  <p><strong>کد ملی:</strong> {nationalCode}</p>
  <p><strong>موبایل:</strong> {mobile}</p>
  <p><strong>تاریخ عمل:</strong> {date}</p>
  <p><strong>نوع عمل:</strong> {surgeryType} · {eyeType}</p>
</div>
<div class="print-rx-box">
  <strong>Rx</strong>
  <p class="print-muted">داروها از آخرین نسخه ثبت‌شده در پرونده چاپ می‌شوند (در قالب سفارشی فقط متن ثابت).</p>
</div>
HTML,
            default => '',
        };
    }

    public static function resolveBody(string $type): string
    {
        $stored = self::customBody($type);

        return $stored !== '' ? $stored : self::defaultBody($type);
    }

    public static function sanitizeHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;

        return trim($html);
    }

    public static function newCustomId(): string
    {
        return 'custom_'.substr(bin2hex(random_bytes(4)), 0, 8);
    }

    public static function metaKey(string $type): string
    {
        return 'print.meta.'.$type;
    }

    /**
     * @return array{mode:string,subtype_ids:list<int|string>,hospital_ids:list<int>,overlay:array<string,mixed>}
     */
    public static function templateMeta(string $type): array
    {
        $raw = SiteSettings::effective(self::metaKey($type), '');
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return self::defaultTemplateMeta();
        }

        return self::normalizeTemplateMeta($decoded);
    }

    /**
     * @return array{mode:string,subtype_ids:list<int|string>,hospital_ids:list<int>,overlay:array<string,mixed>}
     */
    public static function defaultTemplateMeta(): array
    {
        return [
            'mode' => 'html',
            'subtype_ids' => [],
            'hospital_ids' => [],
            'overlay' => [
                'background' => '',
                'background_type' => 'image',
                'tags' => [],
            ],
        ];
    }

    /**
     * @param  array{mode?:string,subtype_ids?:list<int|string>,hospital_ids?:list<int>,overlay?:array<string,mixed>}  $meta
     */
    public static function saveTemplateMeta(string $type, array $meta): void
    {
        $normalized = self::normalizeTemplateMeta($meta);
        SiteSettings::put(self::metaKey($type), json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array{mode:string,subtype_ids:list<int|string>,hospital_ids:list<int>,overlay:array<string,mixed>}
     */
    public static function normalizeTemplateMeta(array $meta): array
    {
        $base = self::defaultTemplateMeta();
        $mode = ($meta['mode'] ?? $base['mode']) === 'overlay' ? 'overlay' : 'html';

        $overlayIn = is_array($meta['overlay'] ?? null) ? $meta['overlay'] : [];

        return [
            'mode' => $mode,
            'subtype_ids' => self::normalizeSubtypeIds($meta['subtype_ids'] ?? []),
            'hospital_ids' => self::normalizeHospitalIds($meta['hospital_ids'] ?? []),
            'overlay' => [
                'background' => trim((string) ($overlayIn['background'] ?? '')),
                'background_type' => in_array($overlayIn['background_type'] ?? 'image', ['image', 'pdf'], true)
                    ? $overlayIn['background_type']
                    : 'image',
                'tags' => self::normalizeOverlayTags($overlayIn['tags'] ?? []),
            ],
        ];
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<int|string>
     */
    private static function normalizeSubtypeIds(array $raw): array
    {
        $ids = [];
        foreach ($raw as $id) {
            if ($id === 'general' || $id === null || $id === '') {
                $ids[] = 'general';
            } elseif (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<mixed>  $raw
     * @return list<int>
     */
    private static function normalizeHospitalIds(array $raw): array
    {
        $ids = [];
        foreach ($raw as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<mixed>  $rawTags
     * @return list<array{key:string,x:float,y:float,align:string,size:float}>
     */
    private static function normalizeOverlayTags(array $rawTags): array
    {
        $tags = [];
        foreach ($rawTags as $tag) {
            if (! is_array($tag) || empty($tag['key'])) {
                continue;
            }
            $tags[] = [
                'key' => (string) $tag['key'],
                'x' => max(0, min(100, (float) ($tag['x'] ?? 0))),
                'y' => max(0, min(100, (float) ($tag['y'] ?? 0))),
                'align' => in_array($tag['align'] ?? 'rtl', ['rtl', 'ltr'], true) ? $tag['align'] : 'rtl',
                'size' => self::clampTagSize((float) ($tag['size'] ?? 1)),
            ];
        }

        return $tags;
    }

    public static function clampTagSize(float $size): float
    {
        if ($size <= 0) {
            return 1.0;
        }

        return round(max(0.4, min(3.5, $size)), 2);
    }

    public static function backgroundUrl(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return str_starts_with($path, '/') ? asset(ltrim($path, '/')) : $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * @param  array<string, string>  $vars
     */
    public static function renderOverlay(array $meta, array $vars): string
    {
        $overlay = $meta['overlay'] ?? [];
        $background = self::backgroundUrl((string) ($overlay['background'] ?? ''));
        $type = ($overlay['background_type'] ?? 'image') === 'pdf' ? 'pdf' : 'image';
        $tags = is_array($overlay['tags'] ?? null) ? $overlay['tags'] : [];

        $tagHtml = '';
        foreach ($tags as $tag) {
            $key = (string) ($tag['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $value = e($vars[$key] ?? '{'.$key.'}');
            $align = ($tag['align'] ?? 'rtl') === 'ltr' ? 'ltr' : 'rtl';
            $x = max(0, min(100, (float) ($tag['x'] ?? 0)));
            $y = max(0, min(100, (float) ($tag['y'] ?? 0)));
            $size = self::clampTagSize((float) ($tag['size'] ?? 1));
            $tagHtml .= '<span class="print-overlay-tag" style="left:'.$x.'%;top:'.$y.'%;direction:'.$align.';--tag-size:'.$size.';" dir="'.$align.'">'.$value.'</span>';
        }

        $bgHtml = '';
        if ($background) {
            if ($type === 'pdf') {
                $bgHtml = '<embed src="'.e($background).'#toolbar=0&navpanes=0" type="application/pdf" class="print-overlay-pdf">';
            } else {
                $bgHtml = '<img src="'.e($background).'" alt="" class="print-overlay-bg">';
            }
        }

        return '<div class="print-overlay-stage">'.$bgHtml.$tagHtml.'</div>';
    }

    /**
     * @return list<array{key:string,label:string,desc:string,custom:bool,meta:array<string,mixed>}>
     */
    public static function typesForSurgery(?SurgeryAppointment $surgery = null): array
    {
        $all = self::types();
        if (! $surgery) {
            return array_map(fn (array $type) => array_merge($type, [
                'meta' => self::templateMeta($type['key']),
            ]), $all);
        }

        $filtered = array_values(array_filter($all, function (array $type) use ($surgery) {
            return self::templateMatchesSurgery(self::templateMeta($type['key']), $surgery);
        }));

        return array_map(fn (array $type) => array_merge($type, [
            'meta' => self::templateMeta($type['key']),
        ]), $filtered);
    }

    /**
     * Empty subtype/hospital lists mean all. When set, both filters must match.
     *
     * @param  array{subtype_ids?:list<int|string>,hospital_ids?:list<int>}  $meta
     */
    public static function templateMatchesSurgery(array $meta, SurgeryAppointment $surgery): bool
    {
        $subtypeIds = $meta['subtype_ids'] ?? [];
        if ($subtypeIds !== []) {
            $subtypeId = $surgery->surgery_subtype_id;
            if ($subtypeId === null) {
                if (! in_array('general', $subtypeIds, true)) {
                    return false;
                }
            } elseif (! in_array((int) $subtypeId, $subtypeIds, true)) {
                return false;
            }
        }

        $hospitalIds = $meta['hospital_ids'] ?? [];
        if ($hospitalIds !== []) {
            $hospitalId = $surgery->hospital_id;
            if ($hospitalId === null || ! in_array((int) $hospitalId, $hospitalIds, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public static function allTemplateMetaForEditor(): array
    {
        $out = [];
        foreach (self::typeKeys() as $key) {
            $out[$key] = self::templateMeta($key);
        }

        return $out;
    }
}
