<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\ReportCard;
use App\Support\Jalali;
use App\Support\ReportCardMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingCardController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $this->cardPayload($request);
        $data['created_by'] = $request->user()->id;
        $data['sort_order'] = (int) ReportCard::query()->max('sort_order') + 1;

        ReportCard::create($data);

        return redirect()
            ->route('modules.accounting.index', ['tab' => 'cards'])
            ->with('success', 'کارت گزارش ساخته شد.');
    }

    public function update(Request $request, ReportCard $reportCard): RedirectResponse
    {
        $reportCard->update($this->cardPayload($request));

        return redirect()
            ->route('modules.accounting.index', ['tab' => 'cards'])
            ->with('success', 'کارت گزارش به‌روزرسانی شد.');
    }

    /**
     * Seed the board with the four numbers a clinic looks at every day.
     */
    public function presets(Request $request): RedirectResponse
    {
        if (ReportCard::query()->exists()) {
            return redirect()
                ->route('modules.accounting.index', ['tab' => 'cards'])
                ->with('error', 'کارت‌های پیش‌فرض فقط وقتی هیچ کارتی وجود ندارد ساخته می‌شوند.');
        }

        $presets = [
            ['title' => 'دریافت امروز', 'source' => 'payments', 'metric' => 'total', 'range_mode' => 'today', 'color' => '#0d8a66'],
            ['title' => 'دریافت ماه جاری', 'source' => 'payments', 'metric' => 'total', 'range_mode' => 'this_month', 'color' => '#4f86be'],
            ['title' => 'بدهکاری ماه جاری', 'source' => 'charges', 'metric' => 'total', 'range_mode' => 'this_month', 'color' => '#e07a3d'],
            ['title' => 'ویزیت‌های ماه جاری', 'source' => 'visits', 'metric' => 'count', 'range_mode' => 'this_month', 'color' => '#2f5f8c'],
            ['title' => 'عمل‌های ماه جاری', 'source' => 'surgeries', 'metric' => 'count', 'range_mode' => 'this_month', 'color' => '#7c5cd6'],
        ];

        foreach ($presets as $index => $preset) {
            ReportCard::create($preset + [
                'card_type' => 'metric',
                'text_color' => '#ffffff',
                'show_count' => true,
                'show_avg' => false,
                'show_total' => true,
                'sort_order' => $index + 1,
                'created_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('modules.accounting.index', ['tab' => 'cards'])
            ->with('success', 'کارت‌های پیش‌فرض ساخته شد.');
    }

    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        $reportCard->delete();

        return redirect()
            ->route('modules.accounting.index', ['tab' => 'cards'])
            ->with('success', 'کارت حذف شد.');
    }

    public function export(): StreamedResponse
    {
        $cards = ReportCard::query()->ordered()->get();
        $sources = ReportCardMetrics::sources();

        return response()->streamDownload(function () use ($cards, $sources) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['عنوان', 'نوع کارت', 'منبع', 'شاخص', 'از', 'تا', 'مقدار', 'تعداد', 'مجموع', 'میانگین']);

            foreach ($cards as $card) {
                $result = ReportCardMetrics::compute($card);
                fputcsv($out, [
                    $card->title,
                    $card->isPartner() ? 'شراکت — '.$card->partner_name.' ('.$card->partner_percent.'٪)' : 'شاخص',
                    $sources[$card->source]['label'] ?? $card->source,
                    ReportCardMetrics::metrics()[$card->metric] ?? $card->metric,
                    $result['from'],
                    $result['to'],
                    $result['value'],
                    $result['count'],
                    $result['total'],
                    $result['avg'],
                ]);
            }

            fclose($out);
        }, 'report-cards-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function cardPayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'card_type' => ['required', Rule::in(['metric', 'partner'])],
            'source' => ['required', Rule::in(array_keys(ReportCardMetrics::sources()))],
            'metric' => ['required', Rule::in(array_keys(ReportCardMetrics::metrics()))],
            'filter_service' => ['nullable', 'string', 'max:160'],
            'filter_type' => ['nullable', 'string', 'max:160'],
            'partner_name' => ['nullable', 'required_if:card_type,partner', 'string', 'max:120'],
            'partner_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'range_mode' => ['required', Rule::in(array_keys(ReportCardMetrics::rangeModes()))],
            'from_jalali' => ['nullable', 'required_if:range_mode,custom', 'string', 'max:12'],
            'to_jalali' => ['nullable', 'required_if:range_mode,custom', 'string', 'max:12'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'title.required' => 'عنوان کارت را وارد کنید.',
            'partner_name.required_if' => 'برای کارت شراکت، نام همکار لازم است.',
            'partner_percent.max' => 'درصد شراکت باید بین ۰ تا ۱۰۰ باشد.',
            'from_jalali.required_if' => 'برای بازهٔ دلخواه، تاریخ شروع را انتخاب کنید.',
            'to_jalali.required_if' => 'برای بازهٔ دلخواه، تاریخ پایان را انتخاب کنید.',
            'color.regex' => 'رنگ کارت نامعتبر است.',
            'text_color.regex' => 'رنگ متن نامعتبر است.',
        ]);

        $from = null;
        $to = null;

        if ($validated['range_mode'] === 'custom') {
            try {
                $from = Jalali::parseJalaliDate((string) $validated['from_jalali']);
                $to = Jalali::parseJalaliDate((string) $validated['to_jalali']);
            } catch (\Throwable) {
                throw ValidationException::withMessages(['from_jalali' => 'تاریخ شمسی نامعتبر است.']);
            }

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to, $from];
            }
        }

        return [
            'title' => $validated['title'],
            'card_type' => $validated['card_type'],
            'source' => $validated['source'],
            'metric' => $validated['card_type'] === 'partner' ? 'total' : $validated['metric'],
            'filter_service' => $validated['filter_service'] ?: null,
            'filter_type' => $validated['filter_type'] ?: null,
            'partner_name' => $validated['card_type'] === 'partner' ? $validated['partner_name'] : null,
            'partner_percent' => $validated['card_type'] === 'partner' ? (int) ($validated['partner_percent'] ?? 0) : 0,
            'range_mode' => $validated['range_mode'],
            'from_date' => $from?->toDateString(),
            'to_date' => $to?->toDateString(),
            'color' => strtolower($validated['color']),
            'text_color' => strtolower($validated['text_color']),
            'show_count' => $request->boolean('show_count'),
            'show_avg' => $request->boolean('show_avg'),
            'show_total' => $request->boolean('show_total'),
        ];
    }
}
