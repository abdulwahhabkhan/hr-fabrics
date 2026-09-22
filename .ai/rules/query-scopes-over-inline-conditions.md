---
glob: app/**/*.php,database/factories/**/*.php
title: No inline `config('account.*')`/raw `where()` type checks — use model scopes; no inline `type` in factories — use factory states
---

Do not filter Eloquent queries by account/journal `type` with a raw `where()` (especially not `config('account.*')`, which is dead — `config/account.php` was removed, so any `config('account.xxx')` call returns `null` and silently breaks the query). Use or add a named `#[Scope]` method instead.

Avoid:

```php
JournalLedger::query()
    ->where('type', config('account.expenses'))
```

Replace with:

```php
JournalLedger::query()
    ->typeExpense()
```

`App\Models\Accounts\JournalLedger` already has most of these (`typeExpense`, `typeSupplier`, `typePayables`, `typeAdvances`, `typeDrawings`, `typeCharity`, `typeCustomer`, `typeOtherReceivables`, `typeBank`, `cashAccount`, `cashBank`, etc. — grep `#\[Scope\]` in that file before adding a new one). If the type you need has no scope yet, add one next to the others:

```php
#[Scope]
protected function typeLiability(Builder $query): Builder
{
    return $query->where('type', AccountType::Liability->value);
}
```

Same rule for factories: don't set `type` inline in `->create([...])`/`->make([...])`. Use or add a factory state method.

Avoid:

```php
Account::factory()->create(['type' => config('account.advances')])
```

Replace with:

```php
Account::factory()->expense()->create()
```

`Database\Factories\Accounts\AccountFactory` already has `supplier()`, `partner()`, `customer()`, `agent()`, `expense()`, `cash()`, `bank()` states — check that list before adding a new `type` state.
