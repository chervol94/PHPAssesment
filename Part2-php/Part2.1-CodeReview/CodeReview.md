# Exercise 2.1 - Code Review

## Issues found

### Security

**1. SQL Injection**
All three queries build SQL using string concatenation (`$contractId`, `$month`) without prepared statements. An attacker controlling these values can read or modify arbitrary data in the database.

**2. URL injection in external API call**
`$month` is interpolated directly into the `file_get_contents` URL with no validation. If the value comes from user input, it can be used to make the server issue requests to unintended endpoints (SSRF).

---

### Bad practices

**3. Errors reported via `echo` instead of exceptions**
`echo "Contract not found"` mixed with `return false` makes errors impossible to catch in calling code. Exceptions should be thrown instead.

**4. Inconsistent return type**
The method returns `false` on error and a `float` on success. This prevents declaring a return type and forces callers to do type checks instead of catching exceptions.

**5. No error handling for `file_get_contents`**
If the external API is down, `file_get_contents` returns `false`. Then `json_decode(false, true)` returns `null`, and accessing `$spotData['avg_price']` causes a fatal error with no useful message.

**6. No null safety on `$spotData['avg_price']`**
Even if the HTTP call succeeds, there is no guarantee the response contains `avg_price`. The code crashes silently if the API response shape changes.

---

### Maintainability

**7. Single Responsibility violated**
`calculate()` does too many things: fetches DB data, calls an external HTTP API, applies tariff logic, calculates taxes, persists the invoice, and outputs a message. Each is a separate responsibility.

**8. `if/elseif` chain for tariff types is not extensible**
Adding a new tariff type requires modifying this method directly, violating the Open/Closed Principle. A Strategy pattern (one class per tariff implementing a shared interface) would allow adding tariffs without touching existing code.

**9. Hardcoded magic numbers**
Values like `0.9` (FIX_PROMO discount), `0.95` (INDEX discount), `500` (kWh threshold), `0.21`/`0.23` (tax rates) are business rules embedded as unnamed literals. They should be named constants or configurable values.

**10. No type hints**
`$db` has no type declaration, so there is no contract for what it must implement. The method also lacks a return type. Both make static analysis and refactoring harder.

**11. External HTTP dependency not injectable**
`file_get_contents` is called directly inside business logic, making the class impossible to unit test without hitting the real API. The HTTP call should be abstracted behind an injectable service.
