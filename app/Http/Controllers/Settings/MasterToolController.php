<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Purchase\FabricReceiving;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page listing the master user tools.
 */
final class MasterToolController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('masterTools', FabricReceiving::class);

        return Inertia::render('settings/Master/MasterActionIndex');
    }
}
