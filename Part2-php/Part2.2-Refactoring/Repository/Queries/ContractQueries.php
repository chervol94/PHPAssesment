<?php

final class ContractQueries
{
    public const FIND_BY_ID =
        'SELECT c.*, t.code AS tariff_code, t.price_per_kwh, t.fixed_monthly
         FROM contracts c
         JOIN tariffs t ON c.tariff_id = t.id
         WHERE c.id = :id';

    public const GET_TOTAL_KWH_FOR_MONTH =
        "SELECT SUM(kwh_consumed) AS total
         FROM meter_readings
         WHERE contract_id = :contract_id
           AND FORMAT(reading_date, 'yyyy-MM') = :month";
}
