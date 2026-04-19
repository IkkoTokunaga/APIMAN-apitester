<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\SavedRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $requests = SavedRequest::orderBy('sort_order')
            ->orderBy('id')
            ->get([
                'id',
                'collection_id',
                'title',
                'method',
                'url',
            ]);

        return response()->json($requests);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $saved = SavedRequest::create($data);

        return response()->json($saved, 201);
    }

    public function show(SavedRequest $savedRequest): JsonResponse
    {
        return response()->json($savedRequest);
    }

    public function update(Request $request, SavedRequest $savedRequest): JsonResponse
    {
        $data = $this->validated($request);

        $savedRequest->update($data);

        return response()->json($savedRequest);
    }

    public function destroy(SavedRequest $savedRequest): JsonResponse
    {
        $savedRequest->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * 複数のリクエスト情報を一括でインポートする。
     *
     * リクエストボディ:
     *   { "items": [ { title, method, url, request_headers, request_body, content_type, collection }, ... ] }
     * collection はコレクション名 (string) または null。存在しなければ作成する。
     */
    public function import(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.title'             => 'required|string|max:255',
            'items.*.method'            => 'required|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'items.*.url'               => 'required|string|max:2000',
            'items.*.request_headers'   => 'nullable|array',
            'items.*.request_body'      => 'nullable|string',
            'items.*.content_type'      => 'nullable|string|max:255',
            'items.*.collection'        => 'nullable|string|max:255',
        ]);

        $imported = 0;
        $collectionCache = [];

        DB::transaction(function () use ($payload, &$imported, &$collectionCache) {
            foreach ($payload['items'] as $item) {
                $collectionId = null;
                $colName = isset($item['collection']) ? trim((string) $item['collection']) : '';

                if ($colName !== '') {
                    if (! array_key_exists($colName, $collectionCache)) {
                        $collection = Collection::firstOrCreate(['name' => $colName]);
                        $collectionCache[$colName] = $collection->id;
                    }
                    $collectionId = $collectionCache[$colName];
                }

                SavedRequest::create([
                    'collection_id'   => $collectionId,
                    'title'           => $item['title'],
                    'method'          => $item['method'],
                    'url'             => $item['url'],
                    'request_headers' => $item['request_headers'] ?? null,
                    'request_body'    => $item['request_body'] ?? null,
                    'content_type'    => $item['content_type'] ?? null,
                ]);

                $imported++;
            }
        });

        return response()->json(['imported' => $imported]);
    }

    /**
     * 全保存リクエストをエクスポート用フォーマットで返す。
     */
    public function export(): JsonResponse
    {
        $items = SavedRequest::with('collection:id,name')
            ->orderBy('id')
            ->get()
            ->map(function (SavedRequest $r) {
                return [
                    'title'           => $r->title,
                    'method'          => $r->method,
                    'url'             => $r->url,
                    'request_headers' => $r->request_headers,
                    'request_body'    => $r->request_body,
                    'content_type'    => $r->content_type,
                    'collection'      => $r->collection?->name,
                ];
            });

        return response()->json(['items' => $items]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'collection_id' => 'nullable|integer|exists:collections,id',
            'title'         => 'required|string|max:255',
            'method'        => 'required|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'url'           => 'required|string|max:2000',
            'request_headers' => 'nullable|array',
            'request_body'    => 'nullable|string',
            'content_type'    => 'nullable|string|max:255',
        ]);
    }
}
