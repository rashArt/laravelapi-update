<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Respuesta exitosa estándar.
     */
    protected function successResponse(mixed $data = null, int $code = 200): JsonResponse
    {
        $body = [
            'status' => true,
            'code'   => $code,
        ];

        if (!is_null($data)) {
            $body['data'] = $data;
        }

        return response()->json($body, $code);
    }

    /**
     * Respuesta de error genérico con mensaje descriptivo.
     */
    protected function errorResponse(string $message, int $code): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'code'    => $code,
            'message' => $message,
        ], $code);
    }

    /**
     * Respuesta paginada: fusiona status/code con data, links y meta del paginador.
     * Usar en lugar de successResponse() cuando el resultado es una ResourceCollection
     * respaldada por un CursorPaginator o LengthAwarePaginator.
     */
    protected function paginatedResponse(
        \Illuminate\Http\Resources\Json\ResourceCollection $collection,
        int $code = 200
    ): JsonResponse {
        // Obtiene la respuesta completa del paginador (incluye data, links, meta)
        $payload = json_decode(
            $collection->response(request())->getContent(),
            associative: true
        );

        return response()->json(
            array_merge(['status' => true, 'code' => $code], $payload),
            $code
        );
    }

    /**
     * Respuesta de error de validación con detalle de campos.
     */
    protected function validationErrorResponse(array $errors, int $code = 422): JsonResponse
    {
        return response()->json([
            'status' => false,
            'code'   => $code,
            'errors' => $errors,
        ], $code);
    }
}
