<?php

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'erse_sync_logs')]
class ErseSyncLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $contractId;

    #[ORM\Column(nullable: true)]
    private ?string $erseId = null;

    #[ORM\Column(enumType: SyncStatus::class)]
    private SyncStatus $status;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $erseResponse = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(int $contractId)
    {
        $this->contractId = $contractId;
        $this->status     = SyncStatus::Pending;
        $this->createdAt  = new \DateTimeImmutable();
    }

    public function markSuccess(string $erseId, array $response): void
    {
        $this->erseId      = $erseId;
        $this->status      = SyncStatus::Success;
        $this->erseResponse = $response;
        $this->updatedAt   = new \DateTimeImmutable();
    }

    public function markFailed(array $response): void
    {
        $this->status       = SyncStatus::Failed;
        $this->erseResponse = $response;
        $this->updatedAt    = new \DateTimeImmutable();
    }

    public function getId(): int              { return $this->id; }
    public function getContractId(): int      { return $this->contractId; }
    public function getErseId(): ?string      { return $this->erseId; }
    public function getStatus(): SyncStatus   { return $this->status; }
    public function getErseResponse(): ?array { return $this->erseResponse; }
    public function getCreatedAt(): \DateTimeImmutable  { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
