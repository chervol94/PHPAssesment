<?php

class SqlContractRepository implements ContractRepositoryInterface
{
    public function __construct(
        private readonly DatabaseInterface $db,
    ) {}

    public function findById(int $contractId): ContractModel
    {
        $row = $this->db->fetchOne(
            ContractQueries::FIND_BY_ID,
            ['id' => $contractId],
        );

        if ($row === null) {
            throw new \RuntimeException("Contract {$contractId} not found.");
        }

        return ContractModel::fromRow($contractId, $row);
    }

    public function getTotalKwhForMonth(int $contractId, string $month): float
    {
        $row = $this->db->fetchOne(
            ContractQueries::GET_TOTAL_KWH_FOR_MONTH,
            ['contract_id' => $contractId, 'month' => $month],
        );

        return (float) ($row['total'] ?? 0);
    }
}
