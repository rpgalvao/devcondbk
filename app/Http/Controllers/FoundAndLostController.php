<?php

namespace App\Http\Controllers;

use App\Models\FoundAndLost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoundAndLostController extends Controller
{
    public function getAll()
    {
        $array = ['error' => ''];

        $lost = FoundAndLost::where('status', 'LOST')->orderBy('datecreated', 'DESC')->orderBy('id', 'DESC')->get();
        foreach ($lost as $lostKey => $lostValue) {
            $lost[$lostKey]['datecreated'] = date('d/m/Y', strtotime($lostValue['datecreated']));
            $lost[$lostKey]['photo'] = asset('storage/'.$lostValue['photo']);
        }
        $array['lost'] = $lost;

        $recovered = FoundAndLost::where('status', 'RECOVERED')->orderBy('datecreated', 'DESC')->orderBy('id', 'DESC')->get();
        foreach ($recovered as $recKey => $recValue) {
            $recovered[$recKey]['datecreated'] = date('d/m/Y', strtotime($recValue['datecreated']));
            $recovered[$recKey]['photo'] = asset('storage/'.$recValue['photo']);
        }
        $array['recovered'] = $recovered;

        return $array;
    }

    public function insert(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'where' => 'required',
            'photo' => 'required|file|mimes:jpeg,jpg,png'
        ]);
        if (!$validator->fails()) {
            $description = $request->input('description');
            $where = $request->input('where');
            $file = $request->file('photo')->store('public');
            $file = explode('public/', $file);
            $photo = $file[1];

            $newLost = new FoundAndLost();
            $newLost->status = 'LOST';
            $newLost->description = $description;
            $newLost->where = $where;
            $newLost->datecreated = date('Y-m-d');
            $newLost->photo = $photo;
            $newLost->save();
        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function update($id, Request $request)
    {
        $array = ['error' => ''];

        $status = $request->input('status');
        if ($status && in_array($status, ['lost', 'recovered'])) {
            $item = FoundAndLost::find($id);
            if ($item) {
                $item->status = $status;
                $item->save();
            } else {
                $array['error'] = 'Não foi possível encontrar o item informado';
                return $array;
            }
        } else {
            $array['error'] = 'O status informado é inválido.';
            return $array;
        }

        return $array;
    }
}
