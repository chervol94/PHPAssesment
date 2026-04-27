<?php

final class ErseContractQueries
{
    public const FIND_FOR_SYNC =
        'SELECT
            c.id          AS contract_id,
            c.cups,
            c.start_date,
            cl.fiscal_id,
            cl.country,
            t.code        AS tariff_code
         FROM contracts c
         JOIN clients  cl ON cl.id = c.client_id
         JOIN tariffs   t ON t.id  = c.tariff_id
         WHERE c.id     = :id
           AND c.status = :status';

    public const IS_SYNCED =
        'SELECT COUNT(*) FROM erse_sync_logs
         WHERE contract_id = :id AND status = :status';
}
