<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::whereRaw('UPPER(code) = ?', [strtoupper(trim($data['code']))])->first();

        if (! $coupon || ! $coupon->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['This coupon code is not valid or has expired.'],
            ]);
        }

        $subtotal = (float) $data['subtotal'];

        if ($subtotal < (float) $coupon->min_subtotal) {
            throw ValidationException::withMessages([
                'code' => [sprintf(
                    'This coupon applies to orders above Rs. %s.',
                    number_format((float) $coupon->min_subtotal)
                )],
            ]);
        }

        return response()->json([
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'min_subtotal' => (float) $coupon->min_subtotal,
            'discount' => $coupon->discountFor($subtotal),
        ]);
    }
}
