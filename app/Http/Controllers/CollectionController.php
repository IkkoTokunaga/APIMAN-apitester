<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(): JsonResponse
    {
        $collections = Collection::orderBy('name')
            ->with(['savedRequests' => function ($query) {
                $query->select([
                    'id',
                    'collection_id',
                    'title',
                    'method',
                    'url',
                    'sort_order',
                ]);
            }])
            ->get();

        return response()->json($collections);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $collection = Collection::create($data);

        return response()->json($collection, 201);
    }

    public function update(Request $request, Collection $collection): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $collection->update($data);

        return response()->json($collection);
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $collection->delete();

        return response()->json(['message' => 'deleted']);
    }
}
