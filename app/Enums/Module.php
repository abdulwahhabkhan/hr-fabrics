<?php

namespace App\Enums;

enum Module: string
{
    case Stock = 'PO';
    case Purchase = 'ASN';
    case PurchaseReturn = 'POR';
    case SaleOrder = 'SO';
    case SaleOrderReturn = 'SOR';

    case Journal = 'journal';
    case JournalVoucher = 'JV';
    case Conversion = 'CONV';
}
