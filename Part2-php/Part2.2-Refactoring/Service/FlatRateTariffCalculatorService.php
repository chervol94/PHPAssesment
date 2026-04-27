<?php

class FlatRateTariffCalculatorService implements TariffCalculatorInterface
{
    public function supports(string $tariffCode): bool
    {
        return $tariffCode === 'FLAT_RATE';
    }

    public function calculate(ContractModel $contract, float $totalKwh, string $month): float
    {
        return $contract->fixedMonthly;
    }
}
