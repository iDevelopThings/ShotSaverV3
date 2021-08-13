<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Resources\Files\FileResource;
use App\Jobs\Files\CreateThumbnail;
use App\Models\Files\File;
use App\Services\FileValidation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{

    /**
     * Handle a file being upload from sharex on behalf of a user
     *
     * @return string
     */
    public function upload(): string
    {
        $user      = auth()->user();
        $file      = request()->file('file');
        $privacy   = $user->private_uploads ? 'private' : 'public';
        $isPrivate = $user->private_uploads;

        if (request()->has('privacy')) {
            if (!in_array(request('privacy'), ['public', 'private'])) {
                return "Privacy can only be 'public' or 'private'.";
            }

            $privacy   = request('privacy');
            $isPrivate = $privacy === 'private';
        }

        if (!$file) {
            return "No file uploaded.";
        }

        $directory = Str::random();
        $fileName  = Str::random() . '.' . $file->getClientOriginalExtension();


        $path = $file->storeAs($directory, $fileName, 'spaces');

        Storage::setVisibility($path, $privacy);

        $type = resolve(FileValidation::class)->fileType($file->getMimeType());
        $meta = [];

        if ($codeInfo = resolve(FileValidation::class)->isCodeFile($file->getClientOriginalExtension())) {
            $type = 'code';
            $meta = $codeInfo;
        }

        $fileModel = $user->files()->save(new File([
            'name'          => $file->getClientOriginalName(),
            'type'          => $type,
            'path'          => $path,
            'mime_type'     => $file->getMimeType(),
            'extension'     => $file->getClientOriginalExtension(),
            'size_in_bytes' => $file->getSize(),
            'private'       => $isPrivate,
            'status'        => 'complete',
            'meta'          => $meta,
        ]));


        $folderName = md5($file->getClientOriginalName());

        $file->storeAs('/temporary/' . $folderName, $fileName, 'local');

        if ($type === 'image' || $type === 'video') {
            CreateThumbnail::dispatch($folderName, $fileModel);
        }

        return route('files.view', $fileModel);
    }

    public function uploads()
    {
        $files = auth()->user()->files()
            ->withCount('views as views')
            ->orderBy('id', request('order', 'desc'))
            ->paginate(10);

        return FileResource::collection($files);
    }
}
