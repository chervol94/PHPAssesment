<?php

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Orchestrates a single contract sync attempt against the ERSE API.
 *
 * API URL and Bearer token are injected via Symfony's services.yaml bindings
 * so they map to environment variables (ERSE_API_URL, ERSE_API_TOKEN).
 */
class ErseSyncService
{
    public function __construct(
        private readonly ErseContractRepository $contractRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface    $httpClient,
        private readonly string                 $erseApiUrl,
        private readonly string                 $erseApiToken,
    ) {}

    public function sync(int $contractId): ErseSyncLog
    {
        $log = new ErseSyncLog($contractId);
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        try {
            $contractData = $this->contractRepository->findForSync($contractId);
            $payload      = $this->buildPayload($contractData);

            $response   = $this->httpClient->request('POST', $this->erseApiUrl . '/contracts', [
                'headers' => ['Authorization' => 'Bearer ' . $this->erseApiToken],
                'json'    => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body       = $response->toArray(throw: false);

            match ($statusCode) {
                201     => $log->markSuccess($body['erse_id'], $body),
                400, 409 => $log->markFailed($body),
                default  => throw new \RuntimeException("Unexpected ERSE API status: {$statusCode}"),
            };

        } catch (\Throwable $e) {
            $log->markFailed(['error' => $e->getMessage()]);
        }

        $this->entityManager->flush();

        return $log;
    }

    /** @return array<string, mixed> */
    private function buildPayload(ErseContractData $data): array
    {
        return [
            'nif'  => $data->nif,
            'cups' => $data->cups,
            'supply_address' => [
                'street'      => $data->street,
                'city'        => $data->city,
                'postal_code' => $data->postalCode,
            ],
            'tariff_code'           => $data->tariffCode,
            'start_date'            => $data->startDate,
            'estimated_annual_kwh'  => $data->estimatedAnnualKwh,
        ];
    }
}
