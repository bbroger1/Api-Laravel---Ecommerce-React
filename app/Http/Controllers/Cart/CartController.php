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
    public function __construct(Cart $cart, Purchase $purchase, Product $product)
    {
        $this->cart = $cart;
        $this->purchase = $purchase;
        $this->product = $product;
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

        //verifica se o produto existe e tem a quantidade necessária para atender ao pedido
        $stock_product = $this->stockProduct($product_id, $quantity);

        if ($stock_product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ]);
        }

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
                if (!$this->purchase->create([
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

            //se já possui carrinho, inclui o pedido na tb purchase e atualiza o valor total do carrinho
            if (!$this->purchase->create([
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

    public function show()
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Please login first'
            ]);
        }

        $user_id = auth('sanctum')->user()->id;

        if (!$cart = $this->cart
            ->where('user_id', $user_id)
            ->where('status', 0)
            ->first()) {
            return response()->json([
                'status' => 204,
                'message' => 'Shopping cart not available'
            ]);
        };

        if (!$purchases = $this->purchase->with('product')
            ->where('cart_id', $cart->id)
            ->groupBy('product_id')
            ->selectRaw('*, sum(quantity) as sum')
            ->get()) {
            return response()->json([
                'status' => 204,
                'message' => 'No products available for cart'
            ]);
        }

        return response()->json([
            'status' => 200,
            'cart' => $cart,
            'items' => $purchases
        ]);
    }

    public function updatePurchase(Request $request)
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Please login first'
            ]);
        }

        $user_id = auth('sanctum')->user()->id;
        $purchase_id = $request->purchaseId;
        $product_id = $request->productId;

        if ($request->action === 'increment') {
            $quantity = intval($request->sum) + 1;

            //verifica se o produto existe e tem a quantidade necessária para atender ao pedido
            $stock_product = $this->stockProduct($product_id, $quantity);

            if ($stock_product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found'
                ]);
            }
        } else {
            $quantity = intval($request->sum) - 1;
        }

        DB::beginTransaction();
        try {
            //verificando o carrinho ativo
            if (!$active_cart = $this->cart
                ->where('user_id', $user_id)
                ->where('status', 0)->first()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found',
                ]);
            }

            //alterar a quantidade de produtos na tabela purchases (pedidos)
            if (!$this->purchase->where('id', $purchase_id)->update([
                'quantity' => $quantity
            ])) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Sorry, there was an unexpected error [Cód. 1]',
                ]);
            }

            $product = $this->product->where('id', $product_id)->first();

            if ($request->action === 'increment') {
                if (!$this->cart->where('id', $active_cart->id)->update([
                    'total' => floatval($active_cart->total) + floatval($product->selling_price)
                ])) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 404,
                        'message' => 'Sorry, there was an unexpected error [Cód. 2]',
                    ]);
                }
            } else {
                if (!$this->cart->where('id', $active_cart->id)->update([
                    'total' => floatval($active_cart->total) - floatval($product->selling_price)
                ])) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 404,
                        'message' => 'Sorry, there was an unexpected error [Cód. 2]',
                    ]);
                }
            }


            DB::commit();

            $cart = $this->cart
                ->where('user_id', $user_id)
                ->where('status', 0)->first();

            $items = $this->purchase->with('product')
                ->where('cart_id', $cart->id)
                ->groupBy('product_id')
                ->selectRaw('*, sum(quantity) as sum')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Quantity changed successfully',
                'cart' => $cart,
                'items' => $items
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Sorry, there was an unexpected error [Cód. 3]',
                'error' => $e
            ]);
        }
    }

    public function destroy($id)
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Please login first'
            ]);
        }

        $purchase = $this->purchase->where('id', $id)->first();

        if (!$purchase) {
            return response()->json([
                'status' => 404,
                'message' => 'Purchase not deleted'
            ]);
        }

        DB::beginTransaction();
        $product = $this->product->where('id', $purchase->product_id)->first();
        $cart = $this->cart->where('id', $purchase->cart_id)->first();
        $total = floatVal($cart->total) - floatVal($product->selling_price);

        if (!$cart->update(['total' => $total])) {
            DB::rollBack();
            return response()->json([
                'status' => 404,
                'message' => 'Purchase not deleted'
            ]);
        }

        if (!$purchase->delete($id)) {
            DB::rollBack();
            return response()->json([
                'status' => 404,
                'message' => 'Purchase not deleted'
            ]);
        }

        DB::commit();
        return response()->json([
            'status' => 200,
            'message' => 'Purchase deleted',
            'total_cart' => $total
        ]);
    }

    private function productTotal($product_id, $quantity)
    {
        $product = $this->product->where("id", $product_id)->first();
        $total = floatval($product->selling_price * $quantity);
        return $total;
    }

    private function stockProduct($product_id, $quantity)
    {
        $stock_product = $this->product->where('id', $product_id)->first();

        if (intval($stock_product->quantity) < intval($quantity)) {
            return true;
        }

        return false;
    }
}
