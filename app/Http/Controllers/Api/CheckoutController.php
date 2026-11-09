<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private const SHIPPING_CHARGE = 249.00;

    private const FREE_SHIPPING_THRESHOLD = 5000.00;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^(?:\+?92|0)?3\d{9}$/'],
            'city' => ['required', 'string', 'max:80'],
            'area' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:200'],
            'payment_method' => ['required', 'string', 'in:cod'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.size' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
        ], [
            'phone.regex' => 'Enter a valid Pakistani mobile number, e.g. 03001234567.',
        ]);

        $lines = $this->groupLines($validated['items']);
        $products = Product::whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');

        foreach ($lines as $line) {
            $sizes = $products[$line['product_id']]->sizes ?? [];

            if ($sizes !== [] && ! in_array($line['size'], $sizes, true)) {
                throw ValidationException::withMessages([
                    'items' => "Size ".($line['size'] ?? '—')." is not available for {$products[$line['product_id']]->name}.",
                ]);
            }
        }

        $order = DB::transaction(function () use ($validated, $lines, $products) {
            $items = [];
            $subtotal = 0;

            foreach ($lines as $line) {
                $product = $products[$line['product_id']];
                $unitPrice = round((float) $product->price, 2);
                $lineTotal = round($unitPrice * $line['quantity'], 2);

                $subtotal += $lineTotal;
                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'size' => $line['size'],
                    'unit_price' => $unitPrice,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $couponCode = trim((string) ($validated['coupon_code'] ?? ''));
            $discount = 0.0;

            if ($couponCode !== '') {
                $coupon = Coupon::whereRaw('UPPER(code) = ?', [strtoupper($couponCode)])->first();

                if (! $coupon || ! $coupon->isUsable() || $subtotal < (float) $coupon->min_subtotal) {
                    throw ValidationException::withMessages([
                        'coupon_code' => ['This coupon code is not valid for this order.'],
                    ]);
                }

                $discount = $coupon->discountFor(round($subtotal, 2));
                $couponCode = $coupon->code;
            }

            // The waived-shipping promise is measured on what the customer
            // actually pays for the clothes, not on the ticketed subtotal.
            $payable = round($subtotal - $discount, 2);
            $shipping = $payable >= self::FREE_SHIPPING_THRESHOLD ? 0.0 : self::SHIPPING_CHARGE;

            $order = Order::create([
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'city' => $validated['city'],
                'area' => $validated['area'],
                'street' => $validated['street'],
                'payment_method' => $validated['payment_method'],
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'shipping_charge' => $shipping,
                'coupon_code' => $discount > 0 ? $couponCode : null,
                'total' => round($payable + $shipping, 2),
            ]);

            $order->items()->createMany($items);
            $order->update(['order_number' => 'AC-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Your order has been placed.',
            'order' => $order,
        ], 201);
    }

    private function groupLines(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $key = $item['product_id'].'::'.($item['size'] ?? '');

            $lines[$key] = [
                'product_id' => (int) $item['product_id'],
                'size' => $item['size'] ?? null,
                'quantity' => ($lines[$key]['quantity'] ?? 0) + (int) $item['quantity'],
            ];
        }

        return array_values($lines);
    }
}
