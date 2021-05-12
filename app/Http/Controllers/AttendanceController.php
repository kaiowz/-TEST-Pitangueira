<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    private $loggedUser;

    public function __construct(){
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function index($id = false){
        if (!Gate::allows('isGestor')) return redirect()->route('login');

        if (!$id){
            $inactiveAttendances = Attendance::where('user_id', $this->loggedUser['id'])
            ->where('state', 0)
            ->get();
            $activeAttendances = Attendance::where('user_id', $this->loggedUser['id'])
            ->where('state', 1)
            ->get();
            return response()->json(['result' => ['data'=>['active' => $activeAttendances, 'inactive' => $inactiveAttendances]]], 200);
        }else{
            $attendance = Attendance::find($id);

            if (!$attendance) return response()->json(['error'=>'Id inválido'], 400);

            return response()->json(['result' => ['data'=>$attendance]], 200);
        }
    }

    public function create(Request $req){
        $clientName = trim($req->input('clientName'));
        $description = trim($req->input('description'));
        $service_id = $req->input('service_id');

        if (!$clientName && !$description && $service_id)
            return response()->json(['error' => 'Preencha os campos corretamente'], 400);

        $newAttendance = new Attendance();
        $newAttendance->clientName = $clientName;
        $newAttendance->description = $description;
        $newAttendance->service_id = $service_id;
        $newAttendance->user_id = $this->loggedUser['id'];
        $newAttendance->state = 1;
        $newAttendance->save();

        return response()->json(['result' => "Atendimento criado com sucesso"], 200);
    }

    public function update(Request $req, $id){
        if (!Gate::allows('isGestor')) return redirect()->route('login');

        $clientName = trim($req->input('clientName'));
        $description = trim($req->input('description'));
        $service_id = trim($req->input('service_id'));
        $state = $req->input('state');

        $attendance = Attendance::find($id);
        if (!$attendance) return response()->json(['error' => 'ID inválido'], 400);

        if ($clientName) $attendance->clientName = $clientName;
        if ($description) $attendance->description = $description;
        if ($service_id) $attendance->service_id = $service_id;
        if ($state == 0 || $state == 1) $attendance->state = $state;
        $attendance->save();

        return response()->json(['result' => "Atendimento atualizado com sucesso"], 200);
    }

    public function delete($id = false){
        if (!Gate::allows('isGestor')) return redirect()->route('login');

        $attendance = Attendance::find($id);
        if (!$attendance) return response()->json(['error'=>"Id inválido"]);
        $attendance->delete();
        return response()->json(['result' => "Atendimento deletado com sucesso"], 200);
    }
}
