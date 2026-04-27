<?php

final class InvoiceBatchQueries
{
    public const FETCH_ACTIVE_CONTRACTS =
        'SELECT id FROM contracts WHERE status = :status';

    public const INVOICE_EXISTS =
        'SELECT COUNT(*) FROM invoices
         WHERE contract_id = :contract_id
           AND billing_period = :period';
}
