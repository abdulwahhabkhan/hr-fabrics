<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileViewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filename = (string) $request->input('path');
        Gate::authorize('viewPath', [File::class, $filename]);
        $disk = Storage::disk()->exists($filename) ? Storage::disk() : Storage::disk('public');

        abort_if(! $disk->exists($filename), 404);

        if (! str($disk->mimeType($filename))->startsWith('image/')) {
            return $disk->download($filename);
        }

        return $disk->image($filename)->orient()->toResponse($request);
    }
}
