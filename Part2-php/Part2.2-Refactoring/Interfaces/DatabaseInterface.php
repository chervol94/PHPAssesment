<?php

interface DatabaseInterface
{
    /** @param array<string, mixed> $params */
    public function fetchOne(string $sql, array $params = []): ?array;

    /** @param array<string, mixed> $params */
    public function execute(string $sql, array $params = []): void;
}
