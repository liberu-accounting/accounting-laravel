<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Serves a minimal OpenAPI 3.0 description of the versioned (/v1) API.
 *
 * ponytail: hand-maintained core spec — wire up an annotation-driven generator
 * (e.g. l5-swagger) if/when the surface grows enough to need full automation.
 */
class OpenApiController extends Controller
{
    public function spec(): JsonResponse
    {
        $resource = fn (string $name, string $readScope, string $writeScope): array => [
            'get' => ['summary' => "List {$name}", 'security' => [['sanctum' => [$readScope]]], 'responses' => ['200' => ['description' => 'OK']]],
            'post' => ['summary' => "Create {$name}", 'security' => [['sanctum' => [$writeScope]]], 'responses' => ['201' => ['description' => 'Created']]],
        ];

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Liberu Accounting API',
                'version' => '1.0.0',
                'description' => 'Versioned REST API. Authenticate with a Sanctum bearer token; endpoints are scoped by token abilities (e.g. invoices:read, invoices:write).',
            ],
            'servers' => [['url' => '/api/v1']],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => ['type' => 'http', 'scheme' => 'bearer'],
                ],
            ],
            'paths' => [
                '/invoices' => $resource('invoices', 'invoices:read', 'invoices:write'),
                '/bills' => $resource('bills', 'bills:read', 'bills:write'),
                '/estimates' => $resource('estimates', 'estimates:read', 'estimates:write'),
                '/journal-entries' => $resource('journal entries', 'journal-entries:read', 'journal-entries:write'),
                '/chart-of-accounts' => $resource('chart of accounts', 'chart-of-accounts:read', 'chart-of-accounts:write'),
                '/general-ledger/trial-balance' => [
                    'get' => ['summary' => 'Trial balance', 'security' => [['sanctum' => ['general-ledger:read']]], 'responses' => ['200' => ['description' => 'OK']]],
                ],
            ],
        ]);
    }
}
