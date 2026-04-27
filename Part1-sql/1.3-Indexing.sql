-- ============================================================================
-- Exercise 1.3 - Indexing
-- ============================================================================


-- ----------------------------------------------------------------------------
-- Index 1: meter_readings (contract_id, reading_date) INCLUDE (kwh_consumed)
--
-- This table gets hit the most across all queries — every time we need to
-- sum consumption we filter by contract_id and then by a date range.
-- Without an index here the DB would scan the full table on every query,
-- which gets slow.
-- I added kwh_consumed as an INCLUDE so the index already has the value
-- we need to SUM without going back to the main table.
-- ----------------------------------------------------------------------------
CREATE NONCLUSTERED INDEX IX_meter_readings_contract_date
    ON meter_readings (contract_id, reading_date)
    INCLUDE (kwh_consumed);


-- ----------------------------------------------------------------------------
-- Index 2: contracts (status) INCLUDE (client_id, tariff_id)
--
-- All the main queries filter contracts by status = 'active', so it makes
-- sense to index that column. Including client_id and tariff_id means the
-- JOIN conditions are also covered by the index, so SQL Server doesn't need
-- to do an extra lookup on the clustered index for those columns.
-- ----------------------------------------------------------------------------
CREATE NONCLUSTERED INDEX IX_contracts_status
    ON contracts (status)
    INCLUDE (client_id, tariff_id);


-- ----------------------------------------------------------------------------
-- Index 3: invoices (contract_id, billing_period)
--
-- Used in two places: the duplicate check in the stored procedure and the
-- NOT EXISTS in query 1.1c. Both look up invoices by contract_id and
-- billing_period, so a composite index on those two columns makes both
-- operations a direct seek instead of a full table scan.
-- Could also add UNIQUE here to enforce at DB level that a contract can't
-- have two invoices for the same period.
-- ----------------------------------------------------------------------------
CREATE NONCLUSTERED INDEX IX_invoices_contract_period
    ON invoices (contract_id, billing_period);
