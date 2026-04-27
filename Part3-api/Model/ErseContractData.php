<?php

/**
 * DTO with all fields required to build the ERSE API payload.
 *
 * Note: supply_address fields (street, city, postalCode) and estimatedAnnualKwh
 * are not present in the Part 1 schema. In a real implementation they would
 * come from additional columns or a related table (e.g. supply_points).
 */
class ErseContractData
{
    public function __construct(
        public readonly int    $contractId,
        public readonly string $nif,
        public readonly string $cups,
        public readonly string $tariffCode,
        public readonly string $startDate,
        public readonly string $country,
        public readonly string $street,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly int    $estimatedAnnualKwh,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            contractId:          (int) $row['contract_id'],
            nif:                 $row['fiscal_id'],
            cups:                $row['cups'],
            tariffCode:          $row['tariff_code'],
            startDate:           $row['start_date'],
            country:             $row['country'],
            street:              $row['street']               ?? '',
            city:                $row['city']                 ?? '',
            postalCode:          $row['postal_code']          ?? '',
            estimatedAnnualKwh:  (int) ($row['estimated_annual_kwh'] ?? 0),
        );
    }
}
