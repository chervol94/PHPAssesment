# Exercise 3.4 - Written Questions

## a) Preventing duplicate simultaneous syncs

`ErseContractRepository::isSynced()` checks for an existing `success` record before the controller calls the service. This prevents a second sync if the first one already succeeded.

---

## b) Handling ERSE API downtime

If the sync request arrives while ERSE is down, the record is saved with `status = failed` and the error is stored in `erse_response` for debugging.

To avoid losing the request, the endpoint would push a message to a queue or a worker could periodically run to check missing or failed jobs.

---

## c) Storing the ERSE API URL and Bearer token

Both values are stored as **environment variables** and never hardcoded:

```
# .env.local
ERSE_API_URL=https://api.erse.pt/v2
ERSE_API_TOKEN=secret-token
```

Environment variables keep sensible data out of the code and make it possible to use them per environment (dev/staging/prod) without changing any code.
