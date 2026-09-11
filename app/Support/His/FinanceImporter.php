<?php

namespace App\Support\His;

use App\Models\FinancialTransaction;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

/**
 * HIS money lands in `financial_transactions`, never in `billing_records`.
 *
 * `billing_records.billable_type/id` is a non-nullable morph, so every row
 * there must hang off an appointment or surgery that exists on the website.
 * HIS invoices belong to HIS admissions, which often have no counterpart here.
 * `financial_transactions` uses nullableMorphs and accepts a bare patient.
 *
 * Real clinic cash in Bina is Accounting.CashPayment + Accounting.Payment
 * (PaymentType 1=POS, 2=Cash, 3=Wallet) — not AdmissionAccountingBundle.
 */
class FinanceImporter extends HisImporter
{
    protected function importRow(array $row): string
    {
        $hisId = $this->text($row['his_id'] ?? null);
        if ($hisId === null) {
            throw new RuntimeException('his_id is required.');
        }

        $type = mb_strtolower((string) $this->text($row['type'] ?? null));
        if (! in_array($type, ['charge', 'payment'], true)) {
            throw new RuntimeException("type must be charge or payment, got: {$type}");
        }

        $amount = $row['amount'] ?? null;
        if (! is_numeric($amount) || (int) $amount < 0) {
            throw new RuntimeException('amount must be a non-negative number.');
        }

        $date = $this->text($row['transaction_date'] ?? null);
        if ($date === null) {
            throw new RuntimeException('transaction_date is required.');
        }

        try {
            $transactionDate = Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            throw new RuntimeException("transaction_date is not a valid date: {$date}");
        }

        $patient = HisPatientMatcher::resolve($row);
        $method = $this->normalizeMethod($row['method'] ?? null, $row['payment_type'] ?? null);
        $notes = $this->composeNotes($row);

        $transaction = FinancialTransaction::query()->where('his_transaction_id', $hisId)->first();
        $existed = $transaction !== null;

        $attributes = [
            'patient_id' => $patient->id,
            'type' => $type,
            'amount' => (int) $amount,
            'method' => $type === 'payment' ? ($method ?? 'other') : null,
            'label' => $this->text($row['label'] ?? null, 255) ?? $this->defaultLabel($method),
            'transaction_date' => $transactionDate,
            'notes' => $notes,
            'his_transaction_id' => $hisId,
            'source' => 'his',
            'his_synced_at' => now(),
        ];

        if ($existed) {
            $transaction->fill($attributes)->save();
        } else {
            FinancialTransaction::create($attributes);
        }

        return $existed ? 'updated' : 'created';
    }

    /**
     * Bina PaymentType codes and common aliases → site method keys.
     */
    protected function normalizeMethod(mixed $method, mixed $paymentType): ?string
    {
        if (is_numeric($paymentType)) {
            return match ((int) $paymentType) {
                1 => 'pos',
                2 => 'cash',
                3 => 'wallet',
                default => 'other',
            };
        }

        $raw = $this->text($method);
        if ($raw === null) {
            return null;
        }

        $key = mb_strtolower($raw);

        return match ($key) {
            '1', 'pos', 'card', 'pospayment', 'کارتخوان' => 'pos',
            '2', 'cash', 'نقد' => 'cash',
            '3', 'wallet', 'کیف پول', 'کیف‌پول' => 'wallet',
            'deposit', 'بیعانه' => 'deposit',
            'transfer', 'انتقال' => 'transfer',
            'other', 'سایر' => 'other',
            default => mb_substr($key, 0, 40),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function composeNotes(array $row): ?string
    {
        $parts = [];

        $admission = $this->text($row['his_admission_id'] ?? null, 64);
        if ($admission !== null) {
            $parts[] = 'پذیرش '.$admission;
        }

        $extra = $this->text($row['notes'] ?? null);
        if ($extra !== null && ! in_array($extra, $parts, true)) {
            $parts[] = $extra;
        }

        return $parts === [] ? null : implode(' | ', $parts);
    }

    protected function defaultLabel(?string $method): string
    {
        return match ($method) {
            'pos' => 'کارتخوان HIS',
            'cash' => 'نقد HIS',
            'wallet' => 'کیف‌پول HIS',
            'deposit' => 'بیعانه HIS',
            default => 'تراکنش HIS',
        };
    }
}
