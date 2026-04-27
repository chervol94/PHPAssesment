<?php

interface ContractRepositoryInterface
{
    /** @throws \RuntimeException if the contract does not exist. */
    public function findById(int $contractId): ContractModel;

    public function getTotalKwhForMonth(int $contractId, string $month): float;
}
