<?php

interface TariffCalculatorInterface
{
    public function supports(string $tariffCode): bool;

    public function calculate(ContractModel $contract, float $totalKwh, string $month): float;
}
