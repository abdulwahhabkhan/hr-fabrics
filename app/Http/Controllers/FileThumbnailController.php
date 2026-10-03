<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileThumbnailController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filename = (string) $request->input('path');
        Gate::authorize('viewPath', [File::class, $filename]);
        $disk = Storage::disk();

        abort_if(! $disk->exists($filename), 404);

        if (! str($disk->mimeType($filename))->startsWith('image/')) {
            return redirect(generate_thumbnail($filename, 1));
        }

        return $disk->image($filename)->orient()->resize(250, 250)->quality(80)->toResponse($request);
    }
}
