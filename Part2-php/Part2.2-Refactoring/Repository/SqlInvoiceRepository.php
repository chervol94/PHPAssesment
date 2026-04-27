<?php

class SqlInvoiceRepository implements InvoiceRepositoryInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    public function createDraft(int $contractId, string $month, float $totalKwh, float $total): void
    {
        $this->db->execute(
            InvoiceQueries::CREATE_DRAFT,
            [
                'contract_id' => $contractId,
                'month'       => $month,
                'total_kwh'   => $totalKwh,
                'total'       => $total,
            ],
        );
    }
}
