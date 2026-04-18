<?php

namespace App\Http\Controllers;

use App\Models\ApiHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ApiProxyController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'method'  => 'required|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url'     => 'required|url',
            'headers' => 'nullable|array',
            'body'    => 'nullable|string',
        ]);

        $method  = strtoupper($request->input('method'));
        $url     = $request->input('url');
        $headers = $request->input('headers', []);
        $body    = $request->input('body', '');

        $startMs = microtime(true) * 1000;

        try {
            $http = Http::withHeaders($headers)->timeout(30);

            $response = match ($method) {
                'GET', 'HEAD', 'OPTIONS' => $http->$method($url),
                default => $http->withBody($body, $headers['Content-Type'] ?? 'application/json')
                                ->$method($url),
            };

            $durationMs    = round(microtime(true) * 1000 - $startMs, 2);
            $statusCode    = $response->status();
            $responseBody  = $response->body();
            $responseHeaders = $response->headers();

        } catch (Throwable $e) {
            $durationMs      = round(microtime(true) * 1000 - $startMs, 2);
            $statusCode      = 0;
            $responseBody    = $e->getMessage();
            $responseHeaders = [];
        }

        ApiHistory::create([
            'method'           => $method,
            'url'              => $url,
            'request_headers'  => $headers ?: null,
            'request_body'     => $body ?: null,
            'status_code'      => $statusCode,
            'response_headers' => json_encode($responseHeaders),
            'response_body'    => $responseBody,
            'duration_ms'      => $durationMs,
        ]);

        return response()->json([
            'status_code'      => $statusCode,
            'response_headers' => $responseHeaders,
            'response_body'    => $responseBody,
            'duration_ms'      => $durationMs,
        ]);
    }

    public function history(): JsonResponse
    {
        $histories = ApiHistory::orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'method', 'url', 'status_code', 'duration_ms', 'created_at']);

        return response()->json($histories);
    }

    public function show(ApiHistory $apiHistory): JsonResponse
    {
        return response()->json($apiHistory);
    }

    public function destroy(ApiHistory $apiHistory): JsonResponse
    {
        $apiHistory->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function clearAll(): JsonResponse
    {
        ApiHistory::truncate();

        return response()->json(['message' => 'cleared']);
    }
}
