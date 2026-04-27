# PHP Developer - Technical Assessment

**Framework:** Symfony
**Language:** English

---

## Structure

```
Part1-sql/        → Exercises 1.1 (queries), 1.2 (stored procedure), 1.3 (indexing)
Part2-php/        → Exercise 2.1 (code review) and 2.2 (refactoring)
Part3-api/        → Exercises 3.1–3.4 (ERSE API integration)
Part4-batch/      → Exercises 4.1–4.2 (nightly invoice generation)
```

Each folder contains the code and a written explanation of the decisions made.

## Notes

- Part 2.2 follows a layered structure: `Enum/`, `Interfaces/`, `Model/`, `Repository/`, `Service/`, `Infrastructure/`.
- SQL queries are isolated in `Queries/` files within each repository layer so schema changes only affect one place.
- No runnable project is included.