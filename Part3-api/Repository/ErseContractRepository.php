<?php

use Doctrine\DBAL\Connection;

class ErseContractRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * @throws \RuntimeException if the contract does not exist or is not active.
     */
    public function findForSync(int $contractId): ErseContractData
    {
        $row = $this->connection->fetchAssociative(
            ErseContractQueries::FIND_FOR_SYNC,
            ['id' => $contractId, 'status' => 'active'],
        );

        if ($row === false) {
            throw new \RuntimeException("Active contract {$contractId} not found.");
        }

        return ErseContractData::fromRow($row);
    }

    public function isSynced(int $contractId): bool
    {
        $count = $this->connection->fetchOne(
            ErseContractQueries::IS_SYNCED,
            ['id' => $contractId, 'status' => SyncStatus::Success->value],
        );

        return (int) $count > 0;
    }
}
