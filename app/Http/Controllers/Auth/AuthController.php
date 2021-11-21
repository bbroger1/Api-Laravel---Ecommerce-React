<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Resource\Auth\UserResource;
use App\Resource\Auth\UserResourceCollection;
use App\Http\Requests\Auth\StoreUpdateUserRequest;
use App\Models\User;
use App\Services\ResponseService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $user;

    public function __constructor(User $user): void
    {
        $this->user = $user;
    }

    public function store(StoreUpdateUserRequest $request)
    {
        if (!$data = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ])) {
            return ResponseService::customMessage('auth.store', $id = null, 'Não foi possível criar o usuário');
        };
        $data['token'] = $data->createToken($data->email . '_Token')->plainTextToken;
        return new UserResource($data, array('type' => 'store', 'route' => 'auth.store'));
    }
}
