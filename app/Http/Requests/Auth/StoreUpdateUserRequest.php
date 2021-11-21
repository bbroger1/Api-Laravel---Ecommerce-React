<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'      => 'required|string|max:191',
            'email'     => 'required|string|unique:users,email',
            'password'  => 'required|string|min:6'
        ];
    }

    public function withValidator($validator)
    {

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json([
                'status'    => false,
                'errors'    => $validator->errors(),
                'url'       => route('auth.store')
            ], 403));
        }
    }
}
