<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected array $sortFields = ['name', 'selling_price', 'status'];

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function index(Request $request)
    {
        $sortFieldInput = $request->input('sort_field', self::DEFAULT_SORT_FIELD);
        $sortField      = in_array($sortFieldInput, $this->sortFields) ? $sortFieldInput : self::DEFAULT_SORT_FIELD;
        $sortOrder      = $request->input('sort_order', self::DEFAULT_SORT_ORDER);
        $searchInput    = $request->input('search');
        $query          = $this->product->orderBy($sortField, $sortOrder);
        $perPage        = $request->input('per_page') ?? self::PER_PAGE;
        if (!is_null($searchInput)) {
            $searchQuery = "%$searchInput%";
            $query       = $query->where('name', 'like', $searchQuery)
                ->orWhere('selling_price', 'like', $searchQuery)
                ->orWhere('status', 'like', $searchQuery);
        }

        $products = $query->with(['category:id,name'])->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'       => 'integer|required',
            'slug'              => 'string|required|min:3|max:191',
            'name'              => 'string|required|min:3|max:191',
            'description'       => 'string|nullable',
            'status'            => 'integer',
            'meta_title'        => 'string|nullable|min:3|max:191',
            'meta_keyword'      => 'string|nullable',
            'meta_description'  => 'string|nullable',
            'selling_price'     => 'string|required',
            'original_price'    => 'string|required',
            'quantity'          => 'integer|required',
            'brand'             => 'string|required',
            'featured'          => 'integer|nullable',
            'popular'           => 'integer|nullable',
            'image'             => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'validation_errors' => $validator->messages(),
            ]);
        }

        DB::beginTransaction();
        try {
            if (!$product = Product::create($request->all())) {
                return response()->json([
                    'status'    => 400,
                    'message'   => 'Could not register product [cód. 1]'
                ]);
            };

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                if (!$file->move('upload/product/', $filename)) {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 400,
                        'message'   => 'Could not register product [cód. 2]'
                    ]);
                };

                if (!$product->where('id', $product->id)->update(['image' => 'upload/product/' . $filename])) {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 400,
                        'message'   => 'Could not register product [cód. 3]'
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status'    => 200,
                'message'   => 'Product Added Successfully'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 400,
                'message'   => 'Could not register product [cód. 4]'
            ]);
        }
    }

    public function edit($id)
    {
        if (!$product = Product::find($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Product not found"
            ]);
        };

        return response()->json([
            'status'    => 200,
            'product'   => $product
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id'       => 'integer|required',
            'slug'              => 'string|required|min:3|max:191',
            'name'              => 'string|required|min:3|max:191',
            'description'       => 'string|nullable',
            'status'            => 'integer',
            'meta_title'        => 'string|nullable|min:3|max:191',
            'meta_keyword'      => 'string|nullable',
            'meta_description'  => 'string|nullable',
            'selling_price'     => 'string|required',
            'original_price'    => 'string|required',
            'quantity'          => 'integer|required',
            'brand'             => 'string|required',
            'featured'          => 'integer|nullable',
            'popular'           => 'integer|nullable',
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'validation_errors' => $validator->messages(),
            ]);
        }

        DB::beginTransaction();
        try {
            $product = Product::where('id', $id);
            if (!$product->update([

                'category_id'       => $request->category_id,
                'slug'              => $request->slug,
                'name'              => $request->name,
                'description'       => $request->description,
                'status'            => $request->status,
                'meta_title'        => $request->meta_title,
                'meta_keyword'      => $request->meta_keyword,
                'meta_description'  => $request->meta_description,
                'selling_price'     => $request->selling_price,
                'original_price'    => $request->original_price,
                'quantity'          => $request->quantity,
                'brand'             => $request->brand,
                'featured'          => $request->featured,
                'popular'           => $request->popular,

            ])) {
                return response()->json([
                    'status'    => 400,
                    'message'   => 'Could not update product [cód. 1]'
                ]);
            };

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                if (!$file->move('upload/product/', $filename)) {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 400,
                        'message'   => 'Could not update product [cód. 2]'
                    ]);
                };

                if (!$product->update(['image' => 'upload/product/' . $filename])) {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 400,
                        'message'   => 'Could not update product [cód. 3]'
                    ]);
                }

                if (File::exists($request->old_image)) {
                    File::delete($request->old_image);
                }
            }

            DB::commit();
            return response()->json([
                'status'    => 200,
                'message'   => 'Product Updated Successfully',
                'product'   => Product::where('id', $id)->first()
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'    => 400,
                'message'   => 'Could not update product [cód. 4]' . $e
            ]);
        }
    }

    public function destroy($id)
    {
        if (!$product = Product::find($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Product not found"
            ]);
        }

        if (!$product->delete($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Product Not Deleted"
            ]);
        }

        return response()->json([
            'status'    => 200,
            'message'   => "Product Deleted Succesfully"
        ]);
    }

    public function fetchproducts($slug)
    {
        $category = Category::where('slug', $slug)
            ->where('status', 1)
            ->first();

        if ($category) {
            $products = Product::where('category_id', $category->id)
                ->where('status', 1)
                ->get();
            if ($products) {
                return response()->json([
                    'status' => 200,
                    'products_data' => [
                        'products' => $products,
                        'category' => $category
                    ]

                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No product available'
                ]);
            }
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No such category found'
            ]);
        }
    }
}
