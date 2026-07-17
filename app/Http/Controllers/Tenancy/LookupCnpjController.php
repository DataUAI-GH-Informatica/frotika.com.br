<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Support\Cnpj\Cnpj;
use App\Support\Cnpj\CnpjLookup;
use App\Support\Cnpj\CnpjLookupStatus;
use Illuminate\Http\JsonResponse;

final class LookupCnpjController
{
    public function __invoke(string $cnpj, CnpjLookup $lookup): JsonResponse
    {
        $digits = Cnpj::digits($cnpj);

        if (! Cnpj::isValid($digits)) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'O CNPJ informado é inválido.',
            ], 422);
        }

        $result = $lookup->find($digits);

        if ($result->status === CnpjLookupStatus::Found && $result->data !== null) {
            return response()->json([
                'status' => CnpjLookupStatus::Found->value,
                'source' => $result->data->source,
                'company' => $result->data->toFormPayload(),
            ]);
        }

        return response()->json([
            'status' => $result->status->value,
        ]);
    }
}
