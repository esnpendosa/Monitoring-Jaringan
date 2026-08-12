<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     */
    protected function successResponse($data = null, string $message = 'Berhasil', int $code = 200): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof AnonymousResourceCollection || $data instanceof JsonResource) {
                $resolved = $data->response()->getData(true);
                $response['data'] = $resolved['data'] ?? $resolved;
                
                if (isset($resolved['meta'])) {
                    $response['meta'] = [
                        'current_page' => $resolved['meta']['current_page'] ?? null,
                        'total' => $resolved['meta']['total'] ?? null,
                        'last_page' => $resolved['meta']['last_page'] ?? null,
                        'per_page' => $resolved['meta']['per_page'] ?? null,
                    ];
                }
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
