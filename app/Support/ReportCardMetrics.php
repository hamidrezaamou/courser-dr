<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\BillingRecord;
use App\Models\FinancialTransaction;
use App\Models\ReportCard;
use App\Models\ServiceTariff;
use App\Models\SurgeryAppointment;
use Carbon\Carbon;

/**
 * Turns a saved ReportCard definition into a computed number.
 *
 * Every source below answers two questions for a date range: how many rows
 * (count) and how much money (total). Metric, partner share and the detail
 * line are all derived from that pair.
 */
class ReportCardMetrics
{
    public const UNIT_MONEY = 'money';

    public const UNIT_COUNT = 'count';

    /**
     * @return array<string, array{label: string, unit: string, service_label: ?string, type_label: ?string, hint: string}>
     */
    public static function sources(): array
    {
        return [
            'payments' => [
                'label' => 'دریافت‌ها (صندوق)',
                'unit' => self::UNIT_MONEY,
                'service_label' => 'روش دریافت',
                'type_label' => null,
                'hint' => 'تراکنش‌های دریافتی ثبت‌شده در حسابداری',
            ],
            'charges' => [
                'label' => 'بدهکاری‌ها (صورت‌حساب بیمار)',
                'unit' => self::UNIT_MONEY,
                'service_label' => 'منشأ',
                'type_label' => null,
                'hint' => 'مبالغی که به حساب بیمار بدهکار شده است',
            ],
            'balance' => [
                'label' => 'مانده (بدهکاری منهای دریافت)',
                'unit' => self::UNIT_MONEY,
                'service_label' => null,
                'type_label' => null,
                'hint' => 'اختلاف بدهکاری و دریافت در بازه',
            ],
            'visits' => [
                'label' => 'ویزیت‌ها',
                'unit' => self::UNIT_COUNT,
                'service_label' => 'نوع ویزیت',
                'type_label' => 'وضعیت نوبت',
                'hint' => 'نوبت‌های ویزیت بر اساس تاریخ نوبت',
            ],
            'surgeries' => [
                'label' => 'عمل‌ها',
                'unit' => self::UNIT_COUNT,
                'service_label' => 'نوع عمل',
                'type_label' => 'وضعیت نوبت',
                'hint' => 'نوبت‌های عمل بر اساس تاریخ عمل',
            ],
            'billing_fee' => [
                'label' => 'صورتحساب — مبلغ کل',
                'unit' => self::UNIT_MONEY,
                'service_label' => 'تعرفه',
                'type_label' => 'وضعیت تسویه',
                'hint' => 'جمع مبلغ خدمات ثبت‌شده در ماژول صورتحساب',
            ],
            'billing_patient' => [
                'label' => 'صورتحساب — سهم بیمار',
                'unit' => self::UNIT_MONEY,
                'service_label' => 'تعرفه',
                'type_label' => 'وضعیت تسویه',
                'hint' => 'سهم پرداختی بیمار پس از کسر بیمه',
            ],
            'billing_insurance' => [
                'label' => 'صورتحساب — سهم بیمه',
                'unit' => self::UNIT_MONEY,
                'service_label' => 'تعرفه',
                'type_label' => 'وضعیت تسویه',
                'hint' => 'سهم بیمه از خدمات ثبت‌شده',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function metrics(): array
    {
        return [
            'count' => 'تعداد',
            'total' => 'مجموع مبلغ',
            'avg' => 'میانگین مبلغ',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function rangeModes(): array
    {
        return [
            'today' => 'امروز',
            'this_week' => 'این هفته',
            'this_month' => 'ماه جاری',
            'last_30' => '۳۰ روز اخیر',
            'this_year' => 'سال جاری',
            'custom' => 'بازه دلخواه',
        ];
    }

    /**
     * Dropdown values for the two cascading filters, per source.
     *
     * @return array<string, array{service: array<string, string>, type: array<string, string>}>
     */
    public static function filterOptions(): array
    {
        $statuses = [];
        foreach (BookingStatus::all() as $status) {
            $statuses[$status] = BookingStatus::label($status);
        }

        $visitTypes = Appointment::query()
            ->whereNotNull('visit_type')
            ->where('visit_type', '!=', '')
            ->distinct()
            ->orderBy('visit_type')
            ->limit(80)
            ->pluck('visit_type')
            ->all();

        $surgeryTypes = SurgeryAppointment::query()
            ->whereNotNull('surgery_type')
            ->where('surgery_type', '!=', '')
            ->distinct()
            ->orderBy('surgery_type')
            ->limit(80)
            ->pluck('surgery_type')
            ->all();

        $tariffs = ServiceTariff::query()->orderBy('name')->pluck('name')->all();

        $settlement = ['open' => 'باز', 'partial' => 'جزئی', 'paid' => 'تسویه'];

        $billing = [
            'service' => array_combine($tariffs, $tariffs) ?: [],
            'type' => $settlement,
        ];

        return [
            'payments' => [
                'service' => [
                    'cash' => 'نقد',
                    'pos' => 'کارتخوان',
                    'card' => 'کارت',
                    'wallet' => 'کیف‌پول',
                    'deposit' => 'بیعانه',
                    'transfer' => 'انتقال',
                    'other' => 'سایر',
                ],
                'type' => [],
            ],
            'charges' => [
                'service' => ['visit' => 'از نوبت ویزیت', 'surgery' => 'از نوبت عمل', 'manual' => 'ثبت دستی'],
                'type' => [],
            ],
            'balance' => ['service' => [], 'type' => []],
            'visits' => [
                'service' => array_combine($visitTypes, $visitTypes) ?: [],
                'type' => $statuses,
            ],
            'surgeries' => [
                'service' => array_combine($surgeryTypes, $surgeryTypes) ?: [],
                'type' => $statuses,
            ],
            'billing_fee' => $billing,
            'billing_patient' => $billing,
            'billing_insurance' => $billing,
        ];
    }

    /**
     * Resolve the card's range preset (or its custom dates) to real days.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function range(ReportCard $card): array
    {
        $today = Carbon::today();
        [$jy, $jm] = Jalali::toJalali((int) $today->format('Y'), (int) $today->format('m'), (int) $today->format('d'));

        return match ($card->range_mode) {
            'today' => [$today->copy(), $today->copy()],
            'this_week' => [$today->copy()->startOfWeek(Carbon::SATURDAY), $today->copy()],
            'last_30' => [$today->copy()->subDays(29), $today->copy()],
            'this_year' => [
                self::fromJalali($jy, 1, 1),
                self::fromJalali($jy, 12, Jalali::daysInMonth($jy, 12)),
            ],
            'custom' => [
                $card->from_date ? $card->from_date->copy()->startOfDay() : self::fromJalali($jy, $jm, 1),
                $card->to_date ? $card->to_date->copy()->startOfDay() : self::fromJalali($jy, $jm, Jalali::daysInMonth($jy, $jm)),
            ],
            default => [
                self::fromJalali($jy, $jm, 1),
                self::fromJalali($jy, $jm, Jalali::daysInMonth($jy, $jm)),
            ],
        };
    }

    /**
     * @return array{
     *     value: int, display: string, unit: string, count: int, total: int, avg: int,
     *     share: int, details: list<string>, from: string, to: string, range_label: string
     * }
     */
    public static function compute(ReportCard $card): array
    {
        [$from, $to] = self::range($card);
        $base = self::base($card, $from, $to);

        $count = $base['count'];
        $total = $base['total'];
        $avg = $count > 0 ? (int) round($total / $count) : 0;
        $unit = self::sources()[$card->source]['unit'] ?? self::UNIT_MONEY;

        $details = [];

        if ($card->isPartner()) {
            $share = (int) round($total * max(0, min(100, $card->partner_percent)) / 100);
            $value = $share;
            $unit = self::UNIT_MONEY;

            if ($card->show_total) {
                $details[] = 'کل: '.self::money($total);
            }
            if ($card->show_count) {
                $details[] = 'تعداد: '.number_format($count);
            }
            $details[] = 'درصد: '.$card->partner_percent.'٪';
        } else {
            $share = 0;
            [$value, $unit] = match ($card->metric) {
                'count' => [$count, self::UNIT_COUNT],
                'avg' => [$avg, self::UNIT_MONEY],
                default => [$total, $unit === self::UNIT_COUNT ? self::UNIT_MONEY : $unit],
            };

            if ($card->show_count && $card->metric !== 'count') {
                $details[] = 'تعداد: '.number_format($count);
            }
            if ($card->show_avg && $card->metric !== 'avg') {
                $details[] = 'میانگین: '.self::money($avg);
            }
            if ($card->show_total && $card->metric === 'count' && $total > 0) {
                $details[] = 'مبلغ: '.self::money($total);
            }
        }

        return [
            'value' => $value,
            'display' => $unit === self::UNIT_MONEY ? self::money($value) : number_format($value).' مورد',
            'unit' => $unit,
            'count' => $count,
            'total' => $total,
            'avg' => $avg,
            'share' => $share,
            'details' => $details,
            'from' => Jalali::format($from, 'Y/m/d'),
            'to' => Jalali::format($to, 'Y/m/d'),
            'range_label' => self::rangeModes()[$card->range_mode] ?? 'بازه دلخواه',
        ];
    }

    /**
     * @return array{count: int, total: int}
     */
    protected static function base(ReportCard $card, Carbon $from, Carbon $to): array
    {
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $service = $card->filter_service;
        $type = $card->filter_type;

        return match ($card->source) {
            'charges' => self::transactions('charge', $fromDate, $toDate, $service),
            'balance' => self::balance($fromDate, $toDate),
            'visits' => self::appointments(Appointment::class, 'visit_type', $fromDate, $toDate, $service, $type),
            'surgeries' => self::appointments(SurgeryAppointment::class, 'surgery_type', $fromDate, $toDate, $service, $type),
            'billing_fee' => self::billing('fee_amount', $fromDate, $toDate, $service, $type),
            'billing_patient' => self::billing('patient_share', $fromDate, $toDate, $service, $type),
            'billing_insurance' => self::billing('insurance_share', $fromDate, $toDate, $service, $type),
            default => self::transactions('payment', $fromDate, $toDate, $service),
        };
    }

    /**
     * @return array{count: int, total: int}
     */
    protected static function transactions(string $type, string $from, string $to, ?string $filter): array
    {
        $query = FinancialTransaction::query()
            ->where('type', $type)
            ->where('transaction_date', '>=', $from)
            ->where('transaction_date', '<=', $to.' 23:59:59');

        if ($filter !== null && $filter !== '') {
            if ($type === 'payment') {
                if ($filter === 'card') {
                    $query->whereIn('method', ['card', 'pos']);
                } else {
                    $query->where('method', $filter);
                }
            } else {
                match ($filter) {
                    'visit' => $query->where('reference_type', Appointment::class),
                    'surgery' => $query->where('reference_type', SurgeryAppointment::class),
                    'manual' => $query->whereNull('reference_type'),
                    default => null,
                };
            }
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (int) (clone $query)->sum('amount'),
        ];
    }

    /**
     * @return array{count: int, total: int}
     */
    protected static function balance(string $from, string $to): array
    {
        $charges = self::transactions('charge', $from, $to, null);
        $payments = self::transactions('payment', $from, $to, null);

        return [
            'count' => (int) FinancialTransaction::query()
                ->where('transaction_date', '>=', $from)
                ->where('transaction_date', '<=', $to.' 23:59:59')
                ->distinct()
                ->count('patient_id'),
            'total' => $charges['total'] - $payments['total'],
        ];
    }

    /**
     * @param  class-string<Appointment|SurgeryAppointment>  $model
     * @return array{count: int, total: int}
     */
    protected static function appointments(string $model, string $typeColumn, string $from, string $to, ?string $service, ?string $status): array
    {
        $query = $model::query()
            ->where('scheduled_date', '>=', $from)
            ->where('scheduled_date', '<=', $to.' 23:59:59');

        if ($service !== null && $service !== '') {
            $query->where($typeColumn, $service);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $total = (int) FinancialTransaction::query()
            ->where('type', 'charge')
            ->where('reference_type', $model)
            ->whereIn('reference_id', (clone $query)->select('id'))
            ->sum('amount');

        return [
            'count' => (int) (clone $query)->count(),
            'total' => $total,
        ];
    }

    /**
     * @return array{count: int, total: int}
     */
    protected static function billing(string $column, string $from, string $to, ?string $tariffName, ?string $settlement): array
    {
        $query = BillingRecord::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        if ($tariffName !== null && $tariffName !== '') {
            $query->whereHas('tariff', fn ($q) => $q->where('name', $tariffName));
        }
        if ($settlement !== null && $settlement !== '') {
            $query->where('settlement_status', $settlement);
        }

        return [
            'count' => (int) (clone $query)->count(),
            'total' => (int) (clone $query)->sum($column),
        ];
    }

    protected static function fromJalali(int $jy, int $jm, int $jd): Carbon
    {
        [$gy, $gm, $gd] = Jalali::toGregorian($jy, $jm, $jd);

        return Carbon::createFromDate($gy, $gm, $gd)->startOfDay();
    }

    protected static function money(int $amount): string
    {
        return number_format($amount).' تومان';
    }
}
