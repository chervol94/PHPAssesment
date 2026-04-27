<?php

enum TaxRate: string
{
    case ES = 'ES';
    case PT = 'PT';

    public function rate(): float
    {
        return match($this) {
            TaxRate::ES => 0.21,
            TaxRate::PT => 0.23,
        };
    }
}
