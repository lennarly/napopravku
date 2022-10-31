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
    public function upload(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'file' => [
                'required',
                'max:20000',
                new FileFormat()
            ],
            'folder' => 'exists:folders,id,user_id,' . $userId
        ]);

        $file = $request->file('file');
        $folder = $request->get('folder');

        $fileUuid = Str::uuid();
        $fileExtension = $file->getClientOriginalExtension();
        $fileName = $fileUuid . '.' . $fileExtension;

        $padUserId = str_pad($userId, 2, 0, STR_PAD_LEFT);
        $padFolder = str_pad($folder, 2, 0, STR_PAD_LEFT);
        $filePath = sprintf("storage/%s/%s/", $padUserId, $padFolder);

        try {
            // Uploading a file to storage
            Storage::putFileAs($filePath, $request->file('file'), $fileName);

            // Creating a file entry in the database
            $fileModel = new File;
            $fileModel->name = $fileUuid;
            $fileModel->original_name = $file->getClientOriginalName();
            $fileModel->mime = $file->getClientMimeType();
            $fileModel->extension = $fileExtension;
            $fileModel->size = $file->getSize();
            $fileModel->user_id = $userId;
            $fileModel->path = $filePath;
            $fileModel->folder_id = $folder ?? null;
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
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $files = File::with('folder')
            ->where('user_id', Auth::id())
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
        $fileId = $request->get('id');
        $userId = $request->user()->id;

        $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId, $fileId) {
                    return $query->where('id', $fileId)
                        ->where('user_id', $userId);
                }),
            ]
        ]);

        $file = File::find($fileId);
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
        $fileId = $request->get('id');
        $name = $request->get('name');
        $userId = $request->user()->id;

        $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId, $fileId) {
                    return $query->where('id', $fileId)
                        ->where('user_id', $userId);
                }),
            ],
            'name' => 'required|digits_between:1,256'
        ]);

        $file = File::find($fileId);
        $file->original_name = $name . '.' . $file->extension;
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
        $fileId = $request->get('id');
        $userId = $request->user()->id;

        $request->validate([
            'id' => [
                'required',
                Rule::exists('files')->where(function ($query) use ($userId, $fileId) {
                    return $query->where('id', $fileId)
                        ->where('user_id', $userId);
                }),
            ]
        ]);

        $file = File::find($fileId);

        $response = Response::make(Storage::get($file->getFullPath()));
        $response->header('Content-Type', $file->mime);
        $response->header('Content-disposition', 'attachment; filename="' . $file->original_name . '"');

        return $response;
    }
}
