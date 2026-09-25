<?php

use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;

if (! function_exists('user_avatar')) {
    function user_avatar(): string
    {
        $user = Auth::user();
        $name = str($user instanceof User ? $user->name : 'Guest User')
            ->slug().'.svg';
        $path = 'avatars/'.$name;
        $filesystem = Storage::disk('public');

        if (! $filesystem->exists($path)) {
            $image_url = 'https://eu.ui-avatars.com/api/'
                .'?background=4b0900&format=svg&color=fff&name='
                .$name;
            $content = file_get_contents($image_url);
            $filesystem->put($path, $content);
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('storage')) {
    function storage(): Filesystem
    {
        return Storage::disk();
    }
}

if (! function_exists('generate_thumbnail')) {
    function generate_thumbnail(?string $path = null, int $expiresIn = 1): string
    {
        if (! $path) {
            return '';
        }
        $storage = storage();
        if (! $storage->exists($path)) {
            return $path.'?s=not_found';
        }
        $mimeType = $storage->mimeType($path);
        if ($mimeType !== 'application/pdf') {
            $file_thumb_path = 'temp_thumbnails/'.$path;
            if ($storage->exists($file_thumb_path)) {
                return $storage->temporaryUrl($file_thumb_path, now()->addMinutes($expiresIn));
            }
            Image::fromStorage($path)
                ->resize(250, 250)
                ->quality(80)
                ->storeAs($file_thumb_path);

            return $storage->temporaryUrl($file_thumb_path, now()->addMinutes($expiresIn));
        }

        return Vite::asset('resources/images/icons/pdf-icon.png');
    }
}

if (! function_exists('download_link')) {
    function download_link(?string $path, int $expiresIn = 5): string
    {
        if (! $path) {
            return '';
        }
        $storage = storage();
        if (! $storage->exists($path)) {
            return $path.'?s=not_found';
        }

        return $storage->temporaryUrl($path, now()->addMinutes($expiresIn));
    }
}
if (! function_exists('hasPermission')) {
    function hasPermission(string $ability, ?User $user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        return once(fn () => $user->hasPermission($ability));
    }
}
