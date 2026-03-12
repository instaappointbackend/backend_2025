<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Return a success response.
     *
     * @param  string  $message
     * @param  array|null  $data
     * @param  int  $status
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data, $message, $status = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response.
     *
     * @param  string  $message
     * @param  int  $status
     * @param  array|null  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error($errors, $message, $status = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
