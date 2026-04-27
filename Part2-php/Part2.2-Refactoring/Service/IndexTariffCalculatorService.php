<?php

class IndexTariffCalculatorService implements TariffCalculatorInterface
{
    public function __construct(
        private readonly SpotPriceClientInterface $spotPriceClient,
    ) {}

    public function supports(string $tariffCode): bool
    {
        return str_contains($tariffCode, 'INDEX');
    }

    public function calculate(ContractModel $contract, float $totalKwh, string $month): float
    {
        $spotPrice = $this->spotPriceClient->getAveragePrice($month);

        $amount = ($totalKwh * $spotPrice) + $contract->fixedMonthly;

        if ($totalKwh > TariffRule::IndexHighConsumptionThresholdKwh->value()) {
            $amount *= TariffRule::IndexHighConsumptionDiscount->value();
        }

        return $amount;
    }
}
