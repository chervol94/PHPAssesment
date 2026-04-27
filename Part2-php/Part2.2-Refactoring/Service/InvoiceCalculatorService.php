<?php

class InvoiceCalculatorService
{
    /**
     * @param TariffCalculatorInterface[] $tariffCalculators
     */
    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InvoiceRepositoryInterface  $invoiceRepository,
        private readonly array                       $tariffCalculators,
    ) {}

    public function calculate(int $contractId, string $month): float
    {
        $contract   = $this->contractRepository->findById($contractId);
        $totalKwh   = $this->contractRepository->getTotalKwhForMonth($contractId, $month);
        $calculator = $this->resolveCalculator($contract->tariffCode);
        $amount     = $calculator->calculate($contract, $totalKwh, $month);
        $total      = $this->applyTax($amount, $contract->country);

        $this->invoiceRepository->createDraft($contractId, $month, $totalKwh, $total);

        return $total;
    }

    private function resolveCalculator(string $tariffCode): TariffCalculatorInterface
    {
        foreach ($this->tariffCalculators as $calculator) {
            if ($calculator->supports($tariffCode)) {
                return $calculator;
            }
        }

        throw new \RuntimeException("No calculator registered for tariff '{$tariffCode}'.");
    }

    private function applyTax(float $amount, string $country): float
    {
        return $amount * (1 + TaxRate::from($country)->rate());
    }
}
