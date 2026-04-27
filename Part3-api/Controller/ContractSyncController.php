<?php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ContractSyncController extends AbstractController
{
    public function __construct(
        private readonly ErseSyncService        $erseSyncService,
        private readonly ErseContractRepository $contractRepository,
    ) {}

    #[Route('/api/contracts/sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $body       = json_decode($request->getContent(), true);
        $contractId = $body['contract_id'] ?? null;

        if (!is_int($contractId) || $contractId <= 0) {
            return $this->json(['error' => 'contract_id must be a positive integer.'], 400);
        }

        try {
            $contractData = $this->contractRepository->findForSync($contractId);
        } catch (\RuntimeException) {
            return $this->json(['error' => "Contract {$contractId} not found or not active."], 404);
        }

        if ($contractData->country !== 'PT') {
            return $this->json(['error' => 'Only Portuguese contracts (country = PT) can be synced with ERSE.'], 422);
        }

        if ($this->contractRepository->isSynced($contractId)) {
            return $this->json(['error' => "Contract {$contractId} is already registered in ERSE."], 409);
        }

        $log = $this->erseSyncService->sync($contractId);

        return match ($log->getStatus()) {
            SyncStatus::Success => $this->json([
                'erse_id' => $log->getErseId(),
                'status'  => $log->getStatus()->value,
            ], 201),
            SyncStatus::Failed => $this->json([
                'error'    => 'Sync failed.',
                'details'  => $log->getErseResponse(),
            ], 502),
            default => $this->json(['status' => $log->getStatus()->value], 202),
        };
    }
}
