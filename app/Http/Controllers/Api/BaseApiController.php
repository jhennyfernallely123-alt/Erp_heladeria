<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    /**
     * $meta se usa para lo que va en el sobre pero NO es la lista: por ejemplo
     * las tarjetas de resumen del inventario. El frontend lo lee de
     * response.meta, separado de data.
     */
    protected function successResponse($data = null, ?string $message = 'Operación exitosa', int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta ?: null,
        ], $status);
    }

    protected function errorResponse(string $message = 'Error en la operación', int $status = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
