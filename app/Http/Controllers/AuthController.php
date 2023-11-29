<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth as FacadesJWTAuth;

class AuthController extends Controller
{
    public function unauthorized()
    {
        return response()->json([
            'error' => 'Não autorizado!'
        ], 401);
    }

    public function register(Request $req)
    {
        $array = ['error'=>''];
        $validator = Validator::make($req->all(), [
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
            'cpf'=>'required|digits:11|unique:users,cpf',
            'password'=>'required|min:4',
            'password_confirm'=>'required|same:password'
        ]);

        if (!$validator->fails()) {
            $name = $req->input('name');
            $email = $req->input('email');
            $cpf = $req->input('cpf');
            $password = $req->input('password');
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $newUser = new User();
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->cpf = $cpf;
            $newUser->password = $hash;
            $newUser->save();

            $token = auth()->attempt([
                'cpf' => $cpf,
                'password' => $password
            ]);

            if (!$token) {
                $array['error'] = 'Ooops! Ocorreu algum erro! Tente de novo mais tarde';
                return $array;
            } else {
                $array['token'] = $token;
                $user = auth()->user();
                $properties = Unit::select()->where('id_owner', $user->id)->get();
                $array['user'] = $user;
                $array['user']['properties'] = $properties;
                return $array;
            }
        } else {
            $array['error'] = $validator->errors()->first();
        }
        return $array;
    }

    public function login(Request $req)
    {
        $array = ['error'=>''];

        $validator = Validator::make($req->all(), [
            'cpf' => 'required|digits:11',
            'password' => 'required|min:4'
        ]);

        if ($validator->fails()) {
            $array['error'] = $validator->errors()->first();
            return $array;
        } else {
            $cpf = $req->input('cpf');
            $password = $req->input('password');
            $user = [
                'cpf' => $cpf,
                'password' => $password
            ];

            $token = auth()->attempt($user);
            
            if (!$token) {
                $array['error'] = 'CPF e/ou senha iválidos';
                return $array;
            } else {
                $array['token'] = $token;
                $user = auth()->user();
                $properties = Unit::select()->where('id_owner', $user->id)->get();
                $array['user'] = $user;
                $array['user']['properties'] = $properties;
            }
        }

        return $array;
    }

    public function validateToken()
    {
        $array = ['error' => ''];

        $user = auth()->user();
        $properties = Unit::select()->where('id_owner', $user->id)->get();
        $array['user'] = $user;
        $array['user']['properties'] = $properties;

        return $array;
    }

    public function logout()
    {
        $array = ['error' => ''];

        auth()->logout();

        return $array;
    }
}
