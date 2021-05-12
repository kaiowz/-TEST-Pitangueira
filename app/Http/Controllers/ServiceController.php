<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Service;
use App\Models\Attendance;

class ServiceController extends Controller
{
    private $loggedUser;

    public function __construct(){
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
        if (!Gate::allows('isGestor')) return redirect()->route('login');
    }

    public function index($id = false){
        if (!$id){
            $activeServices = Service::where('state', 1)->get();
            $inactiveServices = Service::where('state', 0)->get();
            return response()->json(['result' => ['active'=>$activeServices, 'inactive'=>$inactiveServices]], 200);
        }else{
            $Service = Service::find($id);

            if (!$Service) return response()->json(['error'=>'Id inválido'], 400);

            return response()->json(['result' => $Service], 200);
        }
    }

    public function create(Request $req){
        $name = trim($req->input('name'));

        if (!$name) return response()->json(['error' => 'Preencha os campos corretamente'], 400);

        $newService = new Service();
        $newService->name = $name;
        $newService->state = 1;
        $newService->save();

        return response()->json(['result' => "Serviço criado com sucesso"], 200);
    }

    public function update(Request $req, $id){
        $name = trim($req->input('name'));
        $state = $req->input('state');

        $service = Service::find($id);
        if (!$service) return response()->json(['error' => 'ID inválido'], 400);

        if ($name) $service->name = $name;
        if ($state == 0 || $state == 1) $service->state = $state;

        $service->save();

        return response()->json(['result' => "Serviço atualizado com sucesso"], 200);
    }

    public function delete($id = false){
        $service = Service::find($id);
        if (!$service) return response()->json(['error'=>"Id inválido"]);

        $attendances = Attendance::where('service_id', $id)->count();

        if ($attendances > 0) return response()->json(['error' => "Serviço não pode ser deletado"], 401);

        $service->delete();
        return response()->json(['result' => "Serviço deletado com sucesso"], 200);
    }
}
