<?php

final class InvoiceQueries
{
    public const CREATE_DRAFT =
        "INSERT INTO invoices (contract_id, billing_period, total_kwh, total_amount, status)
         VALUES (:contract_id, :month, :total_kwh, :total, 'draft')";
}
