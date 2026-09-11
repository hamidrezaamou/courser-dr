<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use App\Models\ReportCard;
use App\Support\Jalali;
use App\Support\ModuleRegistry;
use App\Support\PatientFinance;
use App\Support\ReportCardMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->string('date')->toString();
        try {
            $day = $date !== '' ? Jalali::parseJalaliDate($date) : now()->startOfDay();
        } catch (\Throwable) {
            $day = now()->startOfDay();
        }

        $gregorian = $day->toDateString();
        $jalali = Jalali::format($day, 'Y/m/d');

        $todayPayments = FinancialTransaction::query()
            ->with(['patient', 'recorder'])
            ->where('type', 'payment')
            ->whereDate('transaction_date', $gregorian)
            ->latest()
            ->get();

        $todayCharges = FinancialTransaction::query()
            ->with(['patient', 'recorder'])
            ->where('type', 'charge')
            ->whereDate('transaction_date', $gregorian)
            ->latest()
            ->get();

        $patients = Patient::query()->orderBy('name')->limit(200)->get(['id', 'name', 'mobile']);

        $cards = ReportCard::query()->ordered()->get()->map(fn (ReportCard $card) => [
            'card' => $card,
            'result' => ReportCardMetrics::compute($card),
        ])->all();

        [$currentJalaliYear] = Jalali::toJalali((int) now()->format('Y'), (int) now()->format('m'), (int) now()->format('d'));

        $tab = $request->string('tab')->toString();
        $activeTab = in_array($tab, ['ledger', 'his'], true) ? $tab : 'cards';

        $hisFilter = $this->hisFinanceFilter($request);

        return view('modules.accounting.index', [
            'moduleSection' => ModuleRegistry::definitions()['accounting']['section'],
            'dateJalali' => $jalali,
            'todayPayments' => $todayPayments,
            'todayCharges' => $todayCharges,
            'todayIncome' => (int) $todayPayments->sum('amount'),
            'todayChargesTotal' => (int) $todayCharges->sum('amount'),
            'debtors' => PatientFinance::debtors(),
            'patients' => $patients,
            'cards' => $cards,
            'cardSources' => ReportCardMetrics::sources(),
            'cardMetrics' => ReportCardMetrics::metrics(),
            'cardRangeModes' => ReportCardMetrics::rangeModes(),
            'cardFilterOptions' => ReportCardMetrics::filterOptions(),
            'canManageCards' => (bool) $request->user()?->isStaff() && ! $request->user()->isAssistant(),
            'jalaliYears' => range($currentJalaliYear - 2, $currentJalaliYear + 1),
            'currentJalaliYear' => $currentJalaliYear,
            'activeTab' => $activeTab,
            'hisFromJalali' => $hisFilter['from_jalali'],
            'hisToJalali' => $hisFilter['to_jalali'],
            'hisMethod' => $hisFilter['method'],
            'hisQuery' => $hisFilter['q'],
            'hisPayments' => $hisFilter['rows'],
            'hisSummary' => $hisFilter['summary'],
            'hisMethodLabels' => self::hisMethodLabels(),
        ]);
    }

    /**
     * Online counterpart of the offline mali.html finance view: HIS payments
     * already synced into financial_transactions (CashPayment/Payment).
     *
     * @return array{from_jalali: string, to_jalali: string, method: string, q: string, rows: \Illuminate\Support\Collection, summary: array<string, int>}
     */
    protected function hisFinanceFilter(Request $request): array
    {
        $fromRaw = $request->string('his_from')->toString();
        $toRaw = $request->string('his_to')->toString();
        $method = $request->string('his_method')->toString();
        $q = trim($request->string('his_q')->toString());

        try {
            $fromDate = $fromRaw !== ''
                ? Jalali::parseJalaliDate($fromRaw)->toDateString()
                : now()->startOfMonth()->toDateString();
        } catch (\Throwable) {
            $fromDate = now()->startOfMonth()->toDateString();
        }

        try {
            $toDate = $toRaw !== ''
                ? Jalali::parseJalaliDate($toRaw)->toDateString()
                : now()->toDateString();
        } catch (\Throwable) {
            $toDate = now()->toDateString();
        }

        $query = FinancialTransaction::query()
            ->with('patient')
            ->where('source', 'his')
            ->where('type', 'payment')
            ->whereBetween('transaction_date', [$fromDate, $toDate]);

        if ($method !== '' && array_key_exists($method, self::hisMethodLabels())) {
            $query->where('method', $method);
        }

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('label', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhere('his_transaction_id', 'like', "%{$q}%")
                    ->orWhereHas('patient', function ($patient) use ($q) {
                        $patient->where('name', 'like', "%{$q}%")
                            ->orWhere('mobile', 'like', "%{$q}%")
                            ->orWhere('national_code', 'like', "%{$q}%");
                    });
            });
        }

        $rows = (clone $query)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $totals = (clone $query)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $summary = [
            'count' => (int) $totals->sum('cnt'),
            'total' => (int) $totals->sum('total'),
            'pos' => (int) ($totals->get('pos')?->total ?? 0),
            'cash' => (int) ($totals->get('cash')?->total ?? 0),
            'wallet' => (int) ($totals->get('wallet')?->total ?? 0),
            'deposit' => (int) ($totals->get('deposit')?->total ?? 0),
            'other' => (int) $totals
                ->reject(fn ($row, $key) => in_array((string) $key, ['pos', 'cash', 'wallet', 'deposit'], true))
                ->sum('total'),
        ];

        return [
            'from_jalali' => Jalali::format(\Carbon\Carbon::parse($fromDate), 'Y/m/d'),
            'to_jalali' => Jalali::format(\Carbon\Carbon::parse($toDate), 'Y/m/d'),
            'method' => $method,
            'q' => $q,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function hisMethodLabels(): array
    {
        return [
            'pos' => 'کارتخوان',
            'cash' => 'نقد',
            'wallet' => 'کیف‌پول',
            'deposit' => 'بیعانه',
            'transfer' => 'انتقال',
            'other' => 'سایر',
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'type' => ['required', 'in:charge,payment'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999'],
            'method' => ['nullable', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:160'],
            'transaction_date' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $date = Jalali::parseJalaliDate($validated['transaction_date'])->toDateString();
        } catch (\Throwable) {
            return back()->withErrors(['transaction_date' => 'تاریخ نامعتبر است.'])->withInput();
        }

        FinancialTransaction::create([
            'patient_id' => $validated['patient_id'],
            'type' => $validated['type'],
            'amount' => (int) $validated['amount'],
            'method' => $validated['type'] === 'payment' ? ($validated['method'] ?: 'cash') : null,
            'label' => $validated['label'],
            'recorded_by' => $request->user()->id,
            'transaction_date' => $date,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'تراکنش مالی ثبت شد.');
    }

    public function export(Request $request): StreamedResponse
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();

        try {
            $fromDate = $from !== '' ? Jalali::parseJalaliDate($from)->toDateString() : now()->startOfMonth()->toDateString();
            $toDate = $to !== '' ? Jalali::parseJalaliDate($to)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->toDateString();
        }

        $rows = FinancialTransaction::query()
            ->with(['patient', 'recorder'])
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $filename = 'accounting-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['date', 'type', 'patient', 'label', 'amount', 'method', 'recorder', 'notes']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    optional($row->transaction_date)->format('Y-m-d'),
                    $row->type,
                    $row->patient?->name,
                    $row->label,
                    $row->amount,
                    $row->method,
                    $row->recorder?->name,
                    $row->notes,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
