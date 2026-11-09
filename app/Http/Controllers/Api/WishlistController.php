<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = $request->user()
            ->wishlist()
            ->with('product.category:id,name,slug')
            ->latest('id')
            ->get()
            ->map(fn ($entry) => $entry->product)
            ->filter()
            ->values();

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $request->user()->wishlist()->firstOrCreate([
            'product_id' => $data['product_id'],
        ]);

        return response()->json(['message' => 'Saved to wishlist.'], 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $request->user()->wishlist()->where('product_id', $productId)->delete();

        return response()->json(['message' => 'Removed from wishlist.']);
    }
}
