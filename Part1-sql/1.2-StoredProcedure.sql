-- ============================================================================
-- Exercise 1.2 - Stored Procedure: sp_GenerateInvoice
-- ============================================================================

CREATE OR ALTER PROCEDURE sp_GenerateInvoice
    @contract_id    INT,
    @billing_period VARCHAR(7)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- --------------------------------------------------------------------
        -- 1. Verify the contract exists and is active. Fetch tariff rates.
        -- --------------------------------------------------------------------
        DECLARE @price_per_kwh  DECIMAL(10,6);
        DECLARE @fixed_monthly  DECIMAL(10,2);

        SELECT
            @price_per_kwh = t.price_per_kwh,
            @fixed_monthly = t.fixed_monthly
        FROM contracts c
        JOIN tariffs t ON t.id = c.tariff_id
        WHERE c.id     = @contract_id
          AND c.status = 'active';

        IF @@ROWCOUNT = 0
            THROW 50001, 'Contract not found or not active.', 1;

        -- --------------------------------------------------------------------
        -- 2. Check no invoice already exists for this period.
        -- --------------------------------------------------------------------
        IF EXISTS (
            SELECT 1
            FROM invoices
            WHERE contract_id   = @contract_id
              AND billing_period = @billing_period
        )
            THROW 50002, 'An invoice already exists for this contract and period.', 1;

        -- --------------------------------------------------------------------
        -- 3. Calculate total kWh for the billing period.
        --
        -- If there are no readings, the invoice is still created with 0 kWh
        -- so the fixed monthly charge is captured. The caller can inspect
        -- total_kwh = 0 and decide whether to escalate.
        -- --------------------------------------------------------------------
        DECLARE @total_kwh DECIMAL(12,3);

        SELECT @total_kwh = ISNULL(SUM(kwh_consumed), 0)
        FROM meter_readings
        WHERE contract_id  = @contract_id
          AND FORMAT(reading_date, 'yyyy-MM') = @billing_period;

        -- --------------------------------------------------------------------
        -- 4. Calculate total amount and insert the draft invoice.
        -- --------------------------------------------------------------------
        DECLARE @total_amount DECIMAL(10,2);
        SET @total_amount = (@total_kwh * @price_per_kwh) + @fixed_monthly;

        INSERT INTO invoices (contract_id, billing_period, total_kwh, total_amount, status)
        VALUES (@contract_id, @billing_period, @total_kwh, @total_amount, 'draft');

        -- --------------------------------------------------------------------
        -- 5. Return the created invoice row.
        -- --------------------------------------------------------------------
        SELECT *
        FROM invoices
        WHERE id = SCOPE_IDENTITY();

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        -- Re-throw so the caller sees the original error code and message.
        THROW;
    END CATCH;
END;
