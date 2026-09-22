<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\AccountsToOptions;

class AutoCompleteController extends Controller
{
    use AccountsToOptions;

    public function suppliers()
    {
        return response()->json($this->supplierOptions());
    }
}
