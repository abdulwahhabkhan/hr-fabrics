---
glob: app/Actions/**/*.php
title: Post journal entries through `LedgerEntry`, never `Journal` model directly
---

Do not call `Journal::createVoucher(...)` / `$journal->post(...)` — those methods do not exist on `App\Models\Accounts\Journal`. Post ledger entries through `App\Actions\Accounts\LedgerEntry` instead, as in `App\Actions\Outbound\SaleOrders\ProcessSaleLedger`:

```php
resolve(LedgerEntry::class)
    ->setTransactionDate($model->transaction_date)
    ->setUser($user)
    ->setHead(JournalHead::Sales)
    ->init($model)
    ->debit($accountId, $amount)
    ->credit($accountId, $amount)
    ->logAction();
```

- `init($model)` creates the `Journal` and associates it via the model's `journal()` morphOne (from `MorphToJournal`).
- `debit()`/`credit()` each create one `JournalDetail` row (`dr`/`cr`).
- `logAction()` records the action; the method is typed `@throws Exception`, so callers up the chain need `@throws` too.
- The morph model needs a **public** `journalDetail(): string` method (see `App\Models\Contracts\Journalable` and `Order`, `Purchase`, `PurchaseReturn`, `SalesReturn`). `LedgerEntry` calls `$this->morph->journalDetail()` as a method — a `protected` method or an `Attribute` accessor will fatal or silently return null.
- To re-post a journal (idempotent update), delete the existing journal's transactions and the journal itself first — `LedgerEntry::init()` always creates a new `Journal`, it does not delete an existing one.
