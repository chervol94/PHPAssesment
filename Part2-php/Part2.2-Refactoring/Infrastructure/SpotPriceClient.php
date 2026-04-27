<?php

class SpotPriceClient implements SpotPriceClientInterface
{
    private const API_URL = 'https://api.energy-market.eu/spot';

    public function getAveragePrice(string $month): float
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new \InvalidArgumentException("Invalid month format '{$month}'. Expected YYYY-MM.");
        }

        $response = file_get_contents(self::API_URL . '?month=' . urlencode($month));

        if ($response === false) {
            throw new \RuntimeException("Could not reach spot price API for month '{$month}'.");
        }

        $data = json_decode($response, true);

        if (!isset($data['avg_price'])) {
            throw new \RuntimeException("Unexpected response from spot price API: missing 'avg_price'.");
        }

        return (float) $data['avg_price'];
    }
}
