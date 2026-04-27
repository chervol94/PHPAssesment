<?php

class FixTariffCalculatorService implements TariffCalculatorInterface
{
    public function supports(string $tariffCode): bool
    {
        return str_contains($tariffCode, 'FIX');
    }

    public function calculate(ContractModel $contract, float $totalKwh, string $month): float
    {
        $amount = ($totalKwh * $contract->pricePerKwh) + $contract->fixedMonthly;

        if ($contract->tariffCode === 'FIX_PROMO') {
            $amount *= TariffRule::FixPromoDiscount->value();
        }

        return $amount;
    }
}
