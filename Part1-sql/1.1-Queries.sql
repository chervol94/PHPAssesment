-- ============================================================================
-- Exercise 1.1 - Queries
-- ============================================================================


-- ----------------------------------------------------------------------------
-- a) All active contracts with client name, tariff code, and total kWh
--    consumed in the current year. Ordered by total kWh descending.
-- ----------------------------------------------------------------------------

SELECT
    c.id                            AS contract_id,
    cl.full_name,
    t.code                          AS tariff_code,
    COALESCE(SUM(mr.kwh_consumed), 0) AS total_kwh_current_year
FROM contracts c
JOIN clients  cl ON cl.id = c.client_id
JOIN tariffs   t ON t.id  = c.tariff_id
LEFT JOIN meter_readings mr
    ON  mr.contract_id  = c.id
    AND mr.reading_date >= DATEFROMPARTS(YEAR(GETDATE()), 1, 1)
    AND mr.reading_date <  DATEFROMPARTS(YEAR(GETDATE()) + 1, 1, 1)
WHERE c.status = 'active'
GROUP BY c.id, cl.full_name, t.code
ORDER BY total_kwh_current_year DESC;


-- ----------------------------------------------------------------------------
-- b) Per country ('ES' / 'PT'): total active contracts and average monthly
--    kWh consumption over the last 6 months.
-- ----------------------------------------------------------------------------

SELECT
    cl.country,
    COUNT(DISTINCT c.id)              AS active_contracts,
    COALESCE(SUM(mr.kwh_consumed), 0) / 6.0 AS avg_monthly_kwh
FROM contracts c
JOIN clients cl ON cl.id = c.client_id
LEFT JOIN meter_readings mr
    ON  mr.contract_id  = c.id
    AND mr.reading_date >= DATEADD(MONTH, -6, GETDATE())
WHERE c.status     = 'active'
  AND cl.country  IN ('ES', 'PT')
GROUP BY cl.country;


-- ----------------------------------------------------------------------------
-- c) Clients with at least one contract who have NEVER received an invoice.
--    Returns: client name, fiscal_id, contract count.
-- ----------------------------------------------------------------------------

SELECT
    cl.full_name,
    cl.fiscal_id,
    COUNT(c.id) AS contract_count
FROM clients cl
JOIN contracts c ON c.client_id = cl.id
WHERE NOT EXISTS (
    SELECT 1
    FROM invoices i
    JOIN contracts c2 ON c2.id = i.contract_id
    WHERE c2.client_id = cl.id
)
GROUP BY cl.id, cl.full_name, cl.fiscal_id;
