<?php

enum TariffRule
{
    case FixPromoDiscount;
    case IndexHighConsumptionThresholdKwh;
    case IndexHighConsumptionDiscount;

    public function value(): float
    {
        return match($this) {
            TariffRule::FixPromoDiscount               => 0.90,
            TariffRule::IndexHighConsumptionThresholdKwh => 500.0,
            TariffRule::IndexHighConsumptionDiscount   => 0.95,
        };
    }
}
