# Exercise 2.2 - Refactoring

## Folder structure

```
Part2.2-Refactoring/
├── Enum/
│   ├── TaxRate.php                          ← tax rates per country
│   └── TariffRule.php                       ← tariff discounts and thresholds
├── Interfaces/
│   ├── DatabaseInterface.php
│   ├── ContractRepositoryInterface.php
│   ├── InvoiceRepositoryInterface.php
│   ├── SpotPriceClientInterface.php
│   └── TariffCalculatorInterface.php
├── Model/
│   └── ContractModel.php
├── Repository/
│   ├── Queries/
│   │   ├── ContractQueries.php              ← all contract/readings SQL strings
│   │   └── InvoiceQueries.php              ← all invoice SQL strings
│   ├── SqlContractRepository.php
│   └── SqlInvoiceRepository.php
├── Service/
│   ├── InvoiceCalculatorService.php
│   ├── FixTariffCalculatorService.php
│   ├── IndexTariffCalculatorService.php
│   └── FlatRateTariffCalculatorService.php
└── Infrastructure/
    └── SpotPriceClient.php
```

---

## a) Security vulnerabilities

**Problem:** All three queries in the original class used string interpolation (`$contractId`, `$month`) to build SQL directly, exposing the code to SQL injection. The `$month` variable was also interpolated into an external API URL with no validation.

**Fix:**

All queries now use named parameters. No user-supplied value is ever concatenated into a SQL string:

```php
// ContractQueries.php
public const FIND_BY_ID =
    'SELECT c.*, t.code AS tariff_code, t.price_per_kwh, t.fixed_monthly
     FROM contracts c JOIN tariffs t ON c.tariff_id = t.id
     WHERE c.id = :id';

// SqlContractRepository.php
$this->db->fetchOne(ContractQueries::FIND_BY_ID, ['id' => $contractId]);
```

The external API call validates `$month` format before it touches the URL:

```php
// Infrastructure/SpotPriceClient.php
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    throw new \InvalidArgumentException("Invalid month format '{$month}'. Expected YYYY-MM.");
}
$response = file_get_contents(self::API_URL . '?month=' . urlencode($month));
```

---

## b) Error handling

**Problem:** The original class used `echo` to report errors and returned `false`, mixing output with business logic and making errors impossible to catch in calling code. The return type was inconsistent (`false` or `float`).

**Fix:**

All error paths throw exceptions. The method always returns `float` or throws — never `false`:

```php
// SqlContractRepository.php
if ($row === null) {
    throw new \RuntimeException("Contract {$contractId} not found.");
}

// InvoiceCalculatorService.php
throw new \RuntimeException("No calculator registered for tariff '{$tariffCode}'.");

// ContractModel.php
TaxRate::from($country); // throws \ValueError automatically for unknown countries
```

---

## c) Dependency injection

**Problem:** The original `$db` parameter had no type, so there was no contract for what it must implement. It was also the only dependency — the external HTTP call was hardcoded inside the method.

**Fix:**

`InvoiceCalculatorService` declares all its dependencies explicitly via typed constructor injection:

```php
public function __construct(
    private readonly ContractRepositoryInterface $contractRepository,
    private readonly InvoiceRepositoryInterface  $invoiceRepository,
    private readonly array                       $tariffCalculators,
) {}
```

`DatabaseInterface` defines the contract for the database layer. `SpotPriceClientInterface` defines the contract for the external API. Both are injected — not instantiated internally — which makes every dependency mockable in tests.

---

## d) Reducing the if/elseif chain — Strategy pattern

**Problem:** The original `calculate()` method contained a long if/elseif block that checked the tariff code string. Adding a new tariff type required modifying this method directly.

**Fix — Strategy pattern:**

Each tariff type is its own class implementing `TariffCalculatorInterface`:

```php
interface TariffCalculatorInterface
{
    public function supports(string $tariffCode): bool;
    public function calculate(ContractModel $contract, float $totalKwh, string $month): float;
}
```

`InvoiceCalculatorService` iterates the injected list and delegates:

```php
private function resolveCalculator(string $tariffCode): TariffCalculatorInterface
{
    foreach ($this->tariffCalculators as $calculator) {
        if ($calculator->supports($tariffCode)) {
            return $calculator;
        }
    }
    throw new \RuntimeException("No calculator registered for tariff '{$tariffCode}'.");
}
```

**Adding a new tariff type** only requires:
1. Creating a new class implementing `TariffCalculatorInterface`.
2. Registering it in the DI container (e.g. Symfony tagged services).

No existing code is modified.

---

## e) Unit testing

| What to test | Why |
|---|---|
| `FixTariffCalculatorService` | Pure function of `ContractModel` inputs. Verify base amount, FIX_PROMO discount applied, FIX_PROMO discount not applied for other FIX codes. |
| `FlatRateTariffCalculatorService` | Always returns `fixedMonthly` regardless of kWh. Trivial but worth a contract test. |
| `IndexTariffCalculatorService` | Mock `SpotPriceClientInterface` to return a fixed price. Verify discount applied above threshold and not applied below. |
| `InvoiceCalculatorService` | Mock all three interfaces. Verify correct calculator is resolved, tax is applied, and invoice is persisted with the right values. |
| `ContractModel` | Verify `fromRow()` maps fields correctly. Verify constructor throws on negative prices or unknown country. |
| `TaxRate` enum | Verify `rate()` returns the correct value per case. |
| `TariffRule` enum | Verify `value()` returns the correct constant per case. |

`SpotPriceClient` (Infrastructure) is excluded from unit tests — it is tested via integration tests against a real or stubbed HTTP server.

---

## Additional design decisions

### SQL queries isolated in `Repository/Queries/`

All raw SQL strings are constants in `ContractQueries` and `InvoiceQueries`. The repositories reference these constants and only handle parameter binding and result mapping. If the schema changes, only the query file needs updating — the repository logic is untouched.

### Business rules in enums (`Enum/`)

Magic numbers like `0.90`, `0.95`, `0.21`, `500` are business rules that change over time. They live in two enums:

- `TaxRate` — backed string enum, maps country code to tax rate via `rate()`. Adding a new country is one new `case`.
- `TariffRule` — unit enum with a `value()` method. Covers discounts and thresholds for all tariff types.

### `ContractModel::fromRow()` factory

The DB row-to-model mapping is the model's own responsibility. The repository calls `ContractModel::fromRow($id, $row)` instead of constructing the object directly. This keeps field names and casts in one place and makes the repository simpler.

The constructor also validates inputs (`pricePerKwh >= 0`, `fixedMonthly >= 0`, valid country) so no invalid model can ever be constructed.

### `SpotPriceClient` isolated in `Infrastructure/`

The external HTTP client is the only component that reaches outside the system. Isolating it in `Infrastructure/` makes the boundary explicit — everything else in `Service/` and `Repository/` is internal. In tests, `SpotPriceClientInterface` is mocked so no test ever makes a real HTTP call.
