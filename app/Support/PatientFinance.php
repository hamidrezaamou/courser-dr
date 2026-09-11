<?php

namespace App\Support;

use App\Models\FinancialTransaction;
use App\Models\Patient;

class PatientFinance
{
    public static function balance(Patient $patient): int
    {
        $charges = FinancialTransaction::query()
            ->where('patient_id', $patient->id)
            ->where('type', 'charge')
            ->sum('amount');

        $payments = FinancialTransaction::query()
            ->where('patient_id', $patient->id)
            ->where('type', 'payment')
            ->sum('amount');

        return (int) $charges - (int) $payments;
    }

    /**
     * @return list<array{patient: Patient, balance: int}>
     */
    public static function debtors(int $limit = 30): array
    {
        $patientIds = FinancialTransaction::query()
            ->select('patient_id')
            ->distinct()
            ->pluck('patient_id');

        $out = [];
        foreach (Patient::query()->whereIn('id', $patientIds)->get() as $patient) {
            $balance = self::balance($patient);
            if ($balance > 0) {
                $out[] = ['patient' => $patient, 'balance' => $balance];
            }
        }

        usort($out, fn ($a, $b) => $b['balance'] <=> $a['balance']);

        return array_slice($out, 0, $limit);
    }
}
