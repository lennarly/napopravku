<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{

    /**
     * Create a folder.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $fields = $request->validate([
            'name' => [
                'required',
                Rule::unique('folders')->where(function ($query) use ($userId, $request) {
                    return $query->where('user_id', $userId)
                        ->where('name', $request->get('name'));
                }),
            ]
        ]);

        $folder = Folder::create([
            'user_id' => $userId,
            'name' => $fields['name']
        ]);

        return response()->json($folder);
    }
}
