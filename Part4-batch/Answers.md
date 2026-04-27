# Part 4 - Practical Scenario

## Exercise 4.1 - Implementation Approach

### a) How to trigger the job

A **Symfony Console Command** (`GenerateMonthlyInvoicesCommand`) scheduled via system cron:

```
0 3 * * * php /app/bin/console app:invoices:generate-monthly >> /var/log/invoices.log 2>&1
```

```bash
php bin/console app:invoices:generate-monthly --period=2026-03
```

---

### b) High-level flow

```
1. Resolve billing period (--period option or previous month)
2. Fetch all active contract IDs from DB
3. For each contract:
   a. Check if invoice already exists for this period → skip if so
   b. Call InvoiceCalculatorService::calculate($contractId, $period)
   c. On success → log success, increment counter
   d. On failure → log error with contract ID, add to failed list
4. Send summary email (succeeded count, failed list with errors)
5. Exit with Command::SUCCESS regardless of individual failures
```

---

### c) Handling individual contract failures

Each contract is wrapped in its own `try/catch`. A failure on one contract logs the error and moves on to the next. The batch never stops mid-run:

Failed contracts are included in the summary email so the team can investigate and re-run.

---

### d) Preventing duplicate invoices

Before calling the calculator, the command checks whether an invoice already exists for that `(contract_id, billing_period)` pair:

If one already exists, the contract is skipped. This means running the command twice for the same period is safe.

---

## Exercise 4.2 - Scaling Questions

### a) 100,000 contracts — process takes too long

The main bottleneck at scale is sequential processing: one contract at a time in a single PHP process.

The fix is to split the work and process it in parallel using **Symfony Messenger**:

1. The command dispatches one `GenerateInvoiceMessage($contractId, $period)` per contract to a queue (RabbitMQ or a DB-backed transport).
2. Multiple worker processes consume the queue in parallel.
3. The command itself finishes immediately after dispatching. A separate listener collects results and sends the summary email once all messages are processed.

---

### b) Process fails at contract #5,000 — DB timeout

3. 10,000 rapid queries may exhaust the connection pool. Reviewing `max_connections`) can help.

4. Processing in smaller chunks (e.g. 500 contracts per batch with a short pause between) reduces overload.

---

### c) Concerns about running during business hours

- **DB contention** — Running this alongside user traffic means competing for the same DB connections, locks, and I/O, which degrades response times for customers.

- **Spot price API calls** 10,000 concurrent requests during business hours could hit rate limits or slow down the API.

- **Visible errors** — if the batch fails mid-run during the day, customers may see partial or missing invoices in real time. At night, the team can fix and re-run before anyone notices.

The only valid reason to move it to business hours would be if the night window is needed for other critical jobs (backups, maintenance). 
