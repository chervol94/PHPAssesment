<?php

interface SpotPriceClientInterface
{
    /** @throws \RuntimeException if the API is unreachable or returns an unexpected response. */
    public function getAveragePrice(string $month): float;
}
