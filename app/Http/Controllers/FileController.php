<?php

namespace App\Http\Controllers;

use App\Enums\DirectoryType;
use App\Enums\FileType;
use App\Http\Requests\FileRequest;
use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function show(File $file, Request $request): Response
    {
        $this->authorize('view', $file);
        abort_if(! Storage::disk()->exists($file->path), 404);

        if (! $file->is_image) {
            return Storage::disk()->download($file->path);
        }

        return Storage::disk()->image($file->path)->orient()->toResponse($request);
    }

    public function store(FileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $file = $data['file'];
        $dir = $data['directory'];
        $dirPath = DirectoryType::tryFrom($dir)?->folderPath() ?? ($dir.'/');
        $dirPath = $dirPath.now()->year.'/'.now()->monthName.'/'.now()->day;
        $file_name = str($file->getClientOriginalName())
            ->replace([' ', '#', '[', ']'], '-')
            ->toString();
        $file_full_name = Str::random(6).'_'.$file_name;

        $file_path = str($file->getMimeType())->startsWith('image/')
            ? Image::fromUpload($file)->orient()->storeAs($dirPath, $file_full_name)
            : $file->storeAs($dirPath, $file_full_name);
        $fileInfo = new File();
        $fileInfo->type = FileType::getFileType($file->getMimeType());
        $fileInfo->directory = $data['directory'];
        if ($fileable = $request->fileable()) {
            $fileInfo->fileable()->associate($fileable);
        }
        $fileInfo->created_by = $request->user()->id;
        $fileInfo->name = $file_name;
        $fileInfo->path = $file_path;
        $fileInfo->size = $file->getSize();
        $fileInfo->save();

        return response()->json($fileInfo->toArray());
    }

    public function destroy(File $file): JsonResponse
    {
        $this->authorize('delete', $file);
        $file->delete();

        return response()->json(['message' => 'File deleted successfully']);
    }
}
