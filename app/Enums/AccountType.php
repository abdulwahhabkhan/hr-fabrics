<?php

namespace App\Enums;

enum AccountType: string
{
    case Agent = 'agent';
    case Customer = 'customer';
    case Employee = 'employee';
    case Supplier = 'supplier';
    case Partner = 'partner';
    case Material = 'material';
    case Account = 'account';
    case Payable = 'payable';
    case Admin = 'admin';
    case Bank = 'bank';
    case Expenses = 'expenses';
    case Cash = 'cash';
    case OtherIncome = 'other_income';
    case CashAccount = 'cash account';
    case Advances = 'advances';
    case Drawings = 'drawings';
    case OtherReceivable = 'other_receivable';
    case Liability = 'liability';
    case Assets = 'assets';
    case Charity = 'charity';
    case Store = 'store';

    public static function receivableAccounts(): array
    {
        return [
            self::Drawings->value,
            self::Advances->value,
            self::OtherReceivable->value,
            self::Expenses->value,
            self::Customer->value,
        ];
    }

    public static function reportFilter(): array
    {
        return [
            self::Customer->value,
            self::Employee->value,
            self::Supplier->value,
            self::Agent->value,
            self::Admin->value,
            self::Advances->value,
            self::Bank->value,
            self::Charity->value,
            self::Drawings->value,
            self::Expenses->value,
            self::Liability->value,
            self::Material->value,
            self::OtherIncome->value,
            self::OtherReceivable->value,
            self::Payable->value,
        ];
    }

    public static function accountType(): array
    {
        return [
            self::Agent->value,
            self::Admin->value,
            self::Advances->value,
            self::Bank->value,
            self::Charity->value,
            self::Drawings->value,
            self::Expenses->value,
            self::Liability->value,
            self::Material->value,
            self::OtherIncome->value,
            self::OtherReceivable->value,
            self::Payable->value,
            self::Store->value,
        ];
    }
}
