<?php

namespace App\Http\Controllers;


use App\Http\Requests\OrderAddRequest;
use App\Http\Requests\OrderCheckDiscountRequest;
use App\Http\Requests\OrderDeleteRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Service\DiscountService;
use App\Service\MessageService;
use App\Service\OrderService;
use App\Service\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var DiscountService
     */
    private $discountService;

    public function __construct(OrderService $orderService, ProductService $productService, DiscountService $discountService)
    {
        $this->orderService    = $orderService;
        $this->productService  = $productService;
        $this->discountService = $discountService;
    }

    /**
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     ** path="/api/v1/order/add",
     *   tags={"Order"},
     *   summary="Add order",
     *
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass order data",
     *    @OA\JsonContent(
     *       required={"customerId","total","items"},
     *       @OA\Property(property="customerId", type="string",example="1"),
     *       @OA\Property(property="total", type="string",example="112.80"),
     *       @OA\Property(
     *             property="items",
     *               type="array",
     *               description="The survey ID",
     *               @OA\Items(
     *                      @OA\Property(
     *                         property="productId",
     *                         type="string",
     *                         example="102"
     *                      ),
     *                      @OA\Property(
     *                         property="quantity",
     *                         type="int",
     *                         example=10
     *                      ),
     *             ),
     *        @OA\Schema(type="array")
     *        ),
     *    ),
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *      )
     *   )
     * )
     *
     **/
    public function add(OrderAddRequest $request)
    {
        $customerId = $request->input('customerId');
        $items      = $request->input('items');
        $total      = (float)$request->input('total');


        /**
         * Logic
         * Check customer
         * Check productId
         * Check unitPrice
         * Check total
         */

        $customer = Customer::find($customerId) ?? null;
        if (!$customer) {
            return response()->json([
                'status'  => false,
                'message' => MessageService::$error['100'],
            ]);

        }
        $calculateTotal = 0.00;


        $orderItem = [];
        foreach ($items as $item) {
            $product = Product::find($item['productId']) ?? null;
            if (!$product) {
                return response()->json([
                    'status'  => false,
                    'message' => "productId:" . $item['productId'] . " " . MessageService::$error['107'],
                ]);
            }
            $isStock = $this->productService->isStock($product, (int)$item['quantity']);
            if (!$isStock) {
                return response()->json([
                    'status'  => false,
                    'message' => "Hatalı productId: " . $product->id . " ; " . MessageService::$error['104'],

                ]);
            }

            $itemPrice      = (float)$product->price * (int)$item['quantity'];
            $calculateTotal += $itemPrice;

            $orderItem[] = [
                'product_id' => $item['productId'],
                'quantity'   => $item['quantity'],
                'unit_price' => $product->price,
                'total'      => $itemPrice,
            ];

        }
        $isTotalManipulation = $this->orderService->isTotalManipulation((float)$calculateTotal, (float)$total);
        if ($isTotalManipulation) {
            return response()->json([
                'status'  => false,
                'message' => $isTotalManipulation,
            ]);
        }

        //add clean order

        $order              = new Order();
        $order->customer_id = $customerId;
        $order->total       = $total;
        $order->save();

        $order->orderItem()
              ->createMany($orderItem);


        return response()->json([
            'status' => true,
            'data'   => [
                'orderId'    => $order->id,
                'totalPrice' => $order->total,
            ]
        ]);
    }

    /**
     * Delete order
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Delete (
     ** path="/api/v1/order/delete",
     *   tags={"Order"},
     *   summary="Delete order",
     *
     * @OA\RequestBody(
     *    required=true,
     *    description="Delete order",
     *    @OA\JsonContent(
     *       required={"orderId"},
     *       @OA\Property(property="orderId", type="string",example="1"),
     *    ),
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *      )
     *   )
     * )
     *
     **/
    public function delete(OrderDeleteRequest $request)
    {
        $orderId = $request->input('orderId');
        DB::beginTransaction();

        try {
            $order = Order::find($orderId) ?? null;
            if (!$order) {
                return response()->json([
                    'status'  => false,
                    'message' => MessageService::$error['106'],

                ]);
            }
            $order->delete();
            $order->orderItem()
                  ->delete();

            DB::commit();
            // all good
        } catch (\Exception $e) {
            DB::rollback();

            // something went wrong
            return response()->json([
                'status'  => false,
                'message' => MessageService::$error['105'],

            ]);

        }

        return response()->json([
            'status' => true,
            'data'   => [
                'removeOrderId' => $orderId,
            ]
        ]);
    }

    /**
     * @OA\Get(
     * path="/api/v1/order/list",
     *   tags={"Order"},
     *   summary="Order List)",
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *      )
     *   )
     * )
     *
     */
    public function list()
    {
        $orders = new  OrderCollection(Order::with('orderItem')
                                            ->get());


        return response()->json([
            'status' => true,
            'data'   => $orders
        ]);
    }

    /**
     * checkDiscount order
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post  (
     ** path="/api/v1/order/checkDiscount",
     *   tags={"Order"},
     *   summary="checkDiscount order",
     *
     * @OA\RequestBody(
     *    required=true,
     *    description="checkDiscount order",
     *    @OA\JsonContent(
     *       required={"orderId"},
     *       @OA\Property(property="orderId", type="string",example="1"),
     *    ),
     * ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *          mediaType="application/json",
     *      )
     *   )
     * )
     *
     **/
    public function checkDiscount(OrderCheckDiscountRequest $request)
    {
        $orderId = $request->input('orderId');

        $order = Order::with('orderItem')
                      ->find($orderId) ?? null;
        if (!$order) {
            return response()->json([
                'status'  => false,
                'message' => MessageService::$error['108'],

            ]);
        }
        $order = $order->toArray();

        $total = 0.00;
        foreach ($order['order_item'] as $item) {
            $product            = Product::find($item['product_id']);
            $itemPrice          = $product->price * $item['quantity'];
            $total              += $itemPrice;
            $itemList['item'][] = [
                'unitPrice' => $product->price,
                'productId' => $product->id,
                'price'     => $itemPrice,
                'category'  => $product->category,
                'quantity'  => $item['quantity'],
            ];
        }
        $itemList['total'] = $total;

        $isDiscount = $this->discountService->isDiscount($itemList);

        return response()->json([
            'status' => true,
            'data'   => $isDiscount,
        ]);
    }


}
