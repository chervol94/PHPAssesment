<?php

interface InvoiceRepositoryInterface
{
    public function createDraft(int $contractId, string $month, float $totalKwh, float $total): void;
}
