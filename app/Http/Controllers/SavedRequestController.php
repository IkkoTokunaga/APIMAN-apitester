<?php

namespace App\Http\Controllers;

use App\Models\SavedRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
