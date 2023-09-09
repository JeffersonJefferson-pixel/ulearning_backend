<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Stripe\Webhook;
use Stripe\Customer;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Course;

class PayController extends Controller
{
    //
    public function checkout(Request $request)
    {
        try {
            $user = $request->user();
            $token = $user->token;
            $courseId = $request->id;

            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $courseResult = Course::where('id', '=', $courseId)->first();

            if (empty($courseResult)) {
                return response()->json([
                    'code' => 400, 
                    'msg' => "Course does not exist"
                ], 400);
            }

            $orderMap = [];

            $orderMap['course_id'] = $courseId;
            $orderMap['user_token'] = $token;
            $orderMap['status'] = 1;

            $orderRes = Order::where($orderMap)->first();
            if (!empty($orderRes)) {
                return response()->json([
                    'code' => 400, 
                    'msg' => "You already bought this course"
                ], 400);
            }

            $YOUR_DOMAIN = env('APP_URL');
            $map = [];
            $map['user_token'] = $token;
            $map['course_id'] = $courseId;
            $map['total_amount'] = $courseResult->price;
            $map['status'] = 0;
            $map['created_at'] = Carbon::now();
            $orderNum = Order::insertGetId($map);

            $checkOutSession = Session::create(
                [
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'USD',
                            'product_data' => [
                                'name' => $courseResult->name,
                                'description' => $courseResult->description,
                            ],
                            'unit_amount' => intval(($courseResult->price) * 100),
                        ],
                        'quantity' => 1
                    ]],
                    'payment_intent_data' => [
                        'metadata' => ['order_num' => $orderNum, 'user_token'=>$token],
                    ],
                    'metadata' => ['order_num' => $orderNum, 'user_token'=>$token],
                    'mode'=>'payment',
                    'success_url' => $YOUR_DOMAIN . 'success', 
                    'cancel_url' => $YOUR_DOMAIN . 'cancel',
                ]
            );

            return response()->json([
                'code' => 200, 
                'msg' => 'success',  
                'data' => $checkOutSession->url
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
