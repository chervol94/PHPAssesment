<?php

class ContractModel
{
    public function __construct(
        public readonly int    $id,
        public readonly string $tariffCode,
        public readonly float  $pricePerKwh,
        public readonly float  $fixedMonthly,
        public readonly string $country,
    ) {
        if ($pricePerKwh < 0) {
            throw new \InvalidArgumentException("pricePerKwh cannot be negative.");
        }

        if ($fixedMonthly < 0) {
            throw new \InvalidArgumentException("fixedMonthly cannot be negative.");
        }

        TaxRate::from($country); // throws \ValueError if country is not a valid TaxRate case
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(int $id, array $row): self
    {
        return new self(
            id:           $id,
            tariffCode:   $row['tariff_code'],
            pricePerKwh:  (float) $row['price_per_kwh'],
            fixedMonthly: (float) $row['fixed_monthly'],
            country:      $row['country'],
        );
    }
}
