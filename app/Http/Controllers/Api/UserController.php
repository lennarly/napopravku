<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get information about an authorized user.
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        return response()->json(
            Auth::user()
        );
    }
}
