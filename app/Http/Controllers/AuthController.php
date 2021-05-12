<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', [
            'except' => [
                'login', 'unauthorized'
                ]
        ]);
    }

    public function login(Request $req){
        $email = $req->input('email');
        $password = $req->input('password');

        $canLogin = User::where('email', $email)
        ->where('state', 1)
        ->get();

        if (!$canLogin) return redirect()->route('login');

        if ($email && $password){
            $token = Auth::attempt(['email'=>$email, 'password'=>$password]);

            if (!$token) return response()->json(['error'=>'E-mail e/ou senha errados!']);

            return response()->json(['token'=>$token]);
        } else return response()->json(['error'=> "Preencha os campos correntamente"]);
    }

    public function logout(){
        Auth::logout();
        return response()->json(['result'=>'Logout realizado']);
    }

    public function refresh(){
        $token = Auth::refresh();
        return response()->json(['token'=>$token]);
    }

    public function create(Request $req){
        $name = $req->input('name');
        $email = $req->input('email');
        $password = $req->input('password');
        $role = $req->input('role');
        $passwordConfirmation = $req->input('password_confirmation');

        if (!Gate::allows('isGestor')) return redirect()->route('login');

        if ($name && $email && $password && $passwordConfirmation && ($role == 0 || $role == 1)){
            $emailExists = User::where('email', $email)->count();

            if ($emailExists != 0) return response()->json(['error'=>'E-mail já cadastrado']);
            if (!$this->isValidPassword($password)) return response()->json(['error'=>'A senha é muito fraca. Certifique-se de inserir pelo menos um caractere minúsculo, maiúsculo, especial e no mínimo 6 caracteres']);
            if ($password != $passwordConfirmation) return response()->json(['error'=>'Senhas não coincidem']);

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $newUser = new User();
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->password = $hash;
            $newUser->role = intval($role);
            $newUser->state = 1;
            $newUser->save();

            $token = Auth::attempt(['email'=>$email, 'password'=>$password]);

            if (!$token) return response()->json(['error'=>'Ocorreu um erro interno!']);

            return response()->json(['token'=>$token], 200);

        }else return response()->json(['error'=>'Você não preencheu todos os campos corretamente']);
    }

    private function isValidPassword($pass){
        return preg_match('/[a-z]/', $pass) // at least one lowercase char
        && preg_match('/[A-Z]/', $pass) // at least one uppercase char
        && preg_match('/[0-9]/', $pass) // at least one number
        && preg_match('/^[\w$@]{6,}$/', $pass); // at least six chars
    }
}
