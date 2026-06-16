<?php

namespace App\Support;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function success($data = [], string $message = "Success",int $code = 200){
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message = "Error",int $code = 400, $errors = []){
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
