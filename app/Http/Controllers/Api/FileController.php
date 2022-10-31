<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Rules\FileFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
}
