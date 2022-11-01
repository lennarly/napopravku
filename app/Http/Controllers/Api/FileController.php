<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Rules\FileFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FileController extends Controller
{

    /**
     * Upload file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $fields = $request->validate([
            'file' => [
                'required',
                'max:' . config('cloud.max_file_size', 2048),
                new FileFormat()
            ],
            'folder_id' => Rule::exists('folders', 'id')
                ->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            'expires_at' => 'date_format:Y-m-d|after_or_equal:1920-01-01'
        ]);

        $file = $fields['file'];
        $folderId = $fields['folder_id'] ?? null;

        $userFiles = Auth::user()->files()->get();
        $totalSize = $userFiles->sum('size');
        $fileSize = $file->getSize();
        $maxTotalSize = config('cloud.user_max_total_size', 10240);

        if ($totalSize + $fileSize > $maxTotalSize * 1024) {
            return response()->json([
                'message' => 'All of your uploaded files are larger than 100MB.'
            ], 500);
        }

        $fileUuid = Str::uuid();
        $fileExtension = $file->getClientOriginalExtension();
        $fileName = $fileUuid . '.' . $fileExtension;

        // Adding a leading zero
        $padUserId = str_pad($userId, 2, 0, STR_PAD_LEFT);
        $padFolder = str_pad($folderId, 2, 0, STR_PAD_LEFT);

        $filePath = sprintf("storage/%s/%s/", $padUserId, $padFolder);

        try {
            // Uploading a file to storage
            Storage::putFileAs($filePath, $file, $fileName);

            // Creating a file entry in the database
            $fileModel = new File;
            $fileModel->name = $fileUuid;
            $fileModel->original_name = $file->getClientOriginalName();
            $fileModel->mime = $file->getClientMimeType();
            $fileModel->extension = $fileExtension;
            $fileModel->size = $file->getSize();
            $fileModel->user_id = $userId;
            $fileModel->path = $filePath;
            $fileModel->folder_id = $folderId;
            $fileModel->expires_at = $fields['expires_at'] ?? null;
            $fileModel->save();

        } catch (\Throwable $e) {
            report($e);
            Storage::delete($filePath . $fileName);

            return response()->json([
                'message' => $filePath . $fileName
            ], 500);
        }

        return response()->json($fileModel);
    }

    /**
     * Get a list of files.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $files = File::with('folder')
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json($files);
    }

    /**
     * Delete a file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function remove(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $fields = $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ]
        ]);

        $file = File::find($fields['id']);
        $file->delete();

        Storage::delete($file->getFullPath());

        return response()->json($file);
    }

    /**
     * Edit a file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $fields = $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'name' => 'required|string|min:1|max:255'
        ]);

        $file = File::find($fields['id']);
        $file->original_name = $file->getNewName($fields['name']);
        $file->save();

        return response()->json($file);
    }

    /**
     * Download a file.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request): \Illuminate\Http\Response
    {

        $userId = $request->user()->id;
        $fields = $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ]
        ]);

        $file = File::find($fields['id']);
        $fullPath = Storage::get($file->getFullPath());

        $response = Response::make($fullPath);
        $response->header('Content-Type', $file->mime);
        $response->header('Content-disposition', 'attachment; filename="' . $file->original_name . '"');

        return $response;
    }

    /**
     * Download a public file.
     *
     * @param $hash
     * @return \Illuminate\Http\Response
     */
    public function publicDownload($hash): \Illuminate\Http\Response
    {
        $file = File::where('link', $hash)->first();
        if (!$file) {
            abort(404);
        }

        $fullPath = Storage::get($file->getFullPath());

        $response = Response::make($fullPath);
        $response->header('Content-Type', $file->mime);
        $response->header('Content-disposition', 'attachment; filename="' . $file->original_name . '"');

        return $response;
    }

    /**
     * Generating a public link for a file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $fields = $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ]
        ]);

        $file = File::find($fields['id']);
        $file->link = Str::uuid();
        $file->save();

        return response()->json([
            'link' => route('public_download', ['id' => $file->link])
        ]);
    }
}
