<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct(Cart $cart, Purchase $purchase)
    {
        $this->cart = $cart;
        $this->purchase = $purchase;
    }

    public function store(Request $request)
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Please login first'
            ]);
        }

        $user_id = auth('sanctum')->user()->id;
        $product_id = $request->productId;
        $quantity = $request->quantity;
        $total = $this->productTotal($product_id, $quantity);

        DB::beginTransaction();
        try {
            //verificando se o cliente possui carrinho ativo
            if (!$active_cart = $this->cart
                ->where('user_id', $user_id)
                ->where('status', 0)->first()) {

                if (!$create_cart = $this->cart->create([
                    'user_id' => $user_id,
                    'total' => $total,
                    'status' => 0
                ])) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Product not added to cart',
                    ]);
                }

                //incluir produtos na tabela purchases (pedidos)
                if (!$purchase = $this->purchase->create([
                    'cart_id' => $create_cart->id,
                    'product_id' => $product_id,
                    'quantity' => $quantity
                ])) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 404,
                        'message' => 'Product not added to cart',
                    ]);
                }
                DB::commit();
                return response()->json([
                    'status' => 201,
                    'message' => 'Product added to cart',
                ]);
            }

            //se já possui carrinho ativo, inclui o pedido na tb purchase e atualiza o valor total do carrinho
            if (!$purchase = $this->purchase->create([
                'cart_id' => $active_cart->id,
                'product_id' => $product_id,
                'quantity' => $quantity
            ])) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not added to cart',
                ]);
            }

            if (!$this->cart->where('id', $active_cart->id)->update([
                'total' => floatval($active_cart->total + $total)
            ])) {
                DB::rollBack();
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not added to cart',
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 201,
                'message' => 'Product added to cart',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not added to cart',
                'error' => $e
            ]);
        }
    }

    private function cartTotal($product_id, $quantity)
    {
        $product = Product::where("id", $product_id)->first();
        $total = floatval($product->selling_price * $quantity);
        return $total;
    }

    private function productTotal($product_id, $quantity)
    {
        $product = Product::where("id", $product_id)->first();
        $total = floatval($product->selling_price * $quantity);
        return $total;
    }
}
