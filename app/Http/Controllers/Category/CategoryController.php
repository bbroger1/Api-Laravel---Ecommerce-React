<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected array $sortFields = ['name', 'slug', 'status'];

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function index(Request $request)
    {
        $sortFieldInput = $request->input('sort_field', self::DEFAULT_SORT_FIELD);
        $sortField      = in_array($sortFieldInput, $this->sortFields) ? $sortFieldInput : self::DEFAULT_SORT_FIELD;
        $sortOrder      = $request->input('sort_order', self::DEFAULT_SORT_ORDER);
        $searchInput    = $request->input('search');
        $query          = $this->category->orderBy($sortField, $sortOrder);
        $perPage        = $request->input('per_page') ?? self::PER_PAGE;
        if (!is_null($searchInput)) {
            $searchQuery = "%$searchInput%";
            $query       = $query->where('name', 'like', $searchQuery)
                ->orWhere('slug', 'like', $searchQuery)
                ->orWhere('status', 'like', $searchQuery);
        }

        $categories = $query->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug'              => 'string|required|min:3|max:191',
            'name'              => 'string|required|min:3|max:191',
            'description'       => 'string|nullable',
            'status'            => 'integer',
            'meta_title'        => 'string|nullable|min:3|max:191',
            'meta_keyword'      => 'string|nullable',
            'meta_description'  => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'validation_errors' => $validator->messages(),
            ]);
        }

        if (!$category = Category::create([
            'slug'              => $request->slug,
            'name'              => $request->name,
            'description'       => $request->description,
            'status'            => $request->status,
            'meta_title'        => $request->meta_title,
            'meta_keyword'      => $request->meta_keyword,
            'meta_description'   => $request->meta_description,
        ])) {
            return response()->json([
                'status'    => 400,
                'message'   => 'Could not register category'
            ]);
        };

        return response()->json([
            'status'    => 200,
            'message'   => 'Category Added Successfully'
        ]);
    }

    public function edit($id)
    {
        if (!$category = Category::find($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Category not found"
            ]);
        }
        return response()->json([
            'status'    => 200,
            'category'  => $category
        ]);
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug'              => 'string|required|min:3|max:191',
            'name'              => 'string|required|min:3|max:191',
            'description'       => 'string|nullable',
            'status'            => 'integer',
            'meta_title'        => 'string|nullable|min:3|max:191',
            'meta_keyword'      => 'string|nullable',
            'meta_description'  => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'validation_errors' => $validator->messages(),
            ]);
        }

        if (!$category = Category::find($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Category not found"
            ]);
        }

        if (!$category->update($request->all())) {
            return response()->json([
                'status'    => 400,
                'message'   => 'Could not register category'
            ]);
        };

        return response()->json([
            'status'    => 200,
            'message'   => 'Category Updated Successfully ',
            'category'  => $category
        ]);
    }

    public function destroy($id)
    {
        if (!$category = Category::find($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Category not found"
            ]);
        }

        if (!$category->delete($id)) {
            return response()->json([
                'status'    => 404,
                'message'   => "Category Not Deleted"
            ]);
        }

        return response()->json([
            'status'    => 200,
            'message'   => "Category Deleted Succesfully"
        ]);
    }

    public function allCategories()
    {
        $categories = Category::where('status', '1')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status'    => 200,
            'message'   => 'Category list',
            'category'  => $categories
        ]);
    }
}
