<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug'              => 'string|required|max:191',
            'name'              => 'string|required|max:191',
            'description'       => 'string|nullable',
            'status'            => 'integer',
            'meta_title'        => 'string|nullable|max:191',
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
}
