<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct(){
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
        if (!Gate::allows('isGestor')) return redirect()->route('login');
    }

    public function me(){
        $user = User::find($this->loggedUser['id']);
        if (!$user) return response()->json(['error'=>'Id inválido'], 400);
        return response()->json(['user' => $user], 200);
    }

    public function index($id = false){
        if (!$id){
            $inactiveUsers = User::where('user_id', $this->loggedUser['id'])
            ->where('state', 0)
            ->get();
            $activeUsers = User::where('user_id', $this->loggedUser['id'])
            ->where('state', 1)
            ->get();
            return response()->json(['result' => ['data'=>['active' => $activeUsers, 'inactive' => $inactiveUsers]]], 200);
        }else{
            $user = User::find($id);

            if (!$user) return response()->json(['error'=>'Id inválido'], 400);

            return response()->json(['result' => ['data'=>$user]], 200);
        }
    }

    public function update(Request $req, $id){
        $name = trim($req->input('name'));
        $email = trim($req->input('email'));
        $password = trim($req->input('password'));
        $state = $req->input('state');

        $user = User::find($id);
        if (!$user) return response()->json(['error' => 'ID inválido'], 400);

        $emailExists = User::where('email', $email)->count();

        if ($emailExists != 0) return response()->json(['error'=>'E-mail já cadastrado'], 400);
        if (!$this->isValidPassword($password)) return response()->json(['error'=>'A senha é muito fraca. Certifique-se de inserir pelo menos um caractere minúsculo, maiúsculo, especial e no mínimo 6 caracteres'], 400);

        if ($name) $user->name = $name;
        if ($email) $user->email = $email;
        if ($password){
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user->password = $hash;
        }
        if ($state == 0 || $state == 1) $user->state = $state;
        $user->save();

        return response()->json(['result' => "Usuário atualizado com sucesso"], 200);
    }

    public function delete($id = false){
        $user = User::find($id);
        if (!$user) return response()->json(['error'=>"Id inválido"]);

        $attendances = Attendance::where('user_id', $id)->count();

        if ($attendances > 0) return response()->json(['error' => "Usuário não pode ser deletado"], 401);

        $user->delete();
        return response()->json(['result' => "Usuário deletado com sucesso"], 200);
    }
}
