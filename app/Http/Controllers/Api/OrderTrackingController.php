<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    private const NOT_FOUND_MESSAGE = 'We could not find an order matching those details. Please check the order number and phone number, then try again.';

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:24'],
        ]);

        $searched = $this->canonicalizePhone($validated['phone']);

        $order = $searched === null ? null : Order::query()
            ->whereRaw('UPPER(order_number) = ?', [strtoupper(trim($validated['order_number']))])
            ->with('items')
            ->first();

        // An unknown number and a wrong phone return the same response, so this
        // endpoint cannot be used to confirm which order numbers exist.
        if ($order === null || $this->canonicalizePhone($order->phone) !== $searched) {
            return response()->json(['message' => self::NOT_FOUND_MESSAGE], 404);
        }

        return response()->json([
            'order' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'city' => $order->city,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'placed_at' => $order->created_at?->toIso8601String(),
                'subtotal' => (float) $order->subtotal,
                'discount' => (float) $order->discount,
                'shipping_charge' => (float) $order->shipping_charge,
                'total' => (float) $order->total,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'product_name' => $item->product_name,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
            ],
        ]);
    }

    /**
     * Orders are stored in whichever format the customer typed (0300…, +92300…, 92300…),
     * so compare on the last ten digits of the local subscriber number.
     */
    private function canonicalizePhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        return strlen($digits) >= 10 ? substr($digits, -10) : null;
    }
}
