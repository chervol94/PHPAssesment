<?php

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:invoices:generate-monthly',
    description: 'Generates invoices for all active contracts for a given billing period.',
)]
class GenerateMonthlyInvoicesCommand extends Command
{
    public function __construct(
        private readonly InvoiceCalculatorService $invoiceCalculator,
        private readonly Connection               $connection,
        private readonly MailerInterface          $mailer,
        private readonly LoggerInterface          $logger,
        private readonly string                   $summaryEmailRecipient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'period',
            null,
            InputOption::VALUE_OPTIONAL,
            'Billing period in YYYY-MM format. Defaults to the previous month.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = $input->getOption('period')
            ?? (new \DateTimeImmutable('first day of last month'))->format('Y-m');

        $io = new SymfonyStyle($input, $output);
        $io->title("Generating invoices — period: {$period}");

        $contracts = $this->connection->fetchAllAssociative(
            InvoiceBatchQueries::FETCH_ACTIVE_CONTRACTS,
            ['status' => 'active'],
        );

        $succeeded = 0;
        $failed    = [];

        foreach ($contracts as $contract) {
            $contractId = (int) $contract['id'];

            try {
                if ($this->invoiceExists($contractId, $period)) {
                    $io->note("Contract {$contractId}: invoice already exists, skipping.");
                    continue;
                }

                $this->invoiceCalculator->calculate($contractId, $period);
                $succeeded++;
                $this->logger->info('Invoice generated.', ['contract_id' => $contractId]);

            } catch (\Throwable $e) {
                $failed[] = ['contract_id' => $contractId, 'error' => $e->getMessage()];
                $this->logger->error('Invoice generation failed.', [
                    'contract_id' => $contractId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        $this->sendSummaryEmail($period, $succeeded, $failed);

        $io->success(sprintf('Done. Succeeded: %d — Failed: %d', $succeeded, count($failed)));

        return Command::SUCCESS;
    }

    private function invoiceExists(int $contractId, string $period): bool
    {
        $count = $this->connection->fetchOne(
            InvoiceBatchQueries::INVOICE_EXISTS,
            ['contract_id' => $contractId, 'period' => $period],
        );

        return (int) $count > 0;
    }

    /** @param array<int, array{contract_id: int, error: string}> $failed */
    private function sendSummaryEmail(string $period, int $succeeded, array $failed): void
    {
        $body = sprintf(
            "Period: %s\nSucceeded: %d\nFailed: %d\n\nFailed contracts:\n%s",
            $period,
            $succeeded,
            count($failed),
            $failed ? json_encode($failed, JSON_PRETTY_PRINT) : 'none',
        );

        $email = (new Email())
            ->to($this->summaryEmailRecipient)
            ->subject("Invoice generation summary — {$period}")
            ->text($body);

        $this->mailer->send($email);
    }
}
