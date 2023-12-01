<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WarningController extends Controller
{
    public function getMyWarnings(Request $request)
    {
        $array = ['error' => ''];

        $property = $request->input('property');
        if ($property) {
            $user = auth()->user();
            $unit = Unit::where('id', $property)->where('id_owner', $user['id'])->count();
            if ($unit > 0) {
                $warnings = Warning::where('id_unit', $property)->orderBy('datecreate', 'DESC')->orderBy('id', 'DESC')->get();
                foreach ($warnings as $warnKey => $warnValue) {
                    $photoList = [];
                    $photos = explode(',', $warnValue['photos']);
                    foreach ($photos as $photo) {
                        if (!empty($photo)) {
                            $photoList[] = asset('storage/'.$photo);
                        }
                    }
                    $warnings[$warnKey]['datecreated'] = date('d/m/Y', strtotime($warnValue['datecreated']));
                    $warnings[$warnKey]['photos'] = $photoList;
                }
                $array['list'] = $warnings;
            } else {
                $array['error'] = 'A unidade informada não pertence a você!';
            }
        } else {
            $array['error'] = 'Necessário informar a unidade!';
        }

        return $array;
    }

    public function addWarningFile(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'photo' => 'required|file|mimes:jpg,jpeg,png'
        ]);
        if (!$validator->fails()) {
            $file = $request->file('photo')->store('public');
            $array['photo'] = asset(Storage::url($file));
        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function setWarning(Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'property' => 'required'
        ]);
        if (!$validator->fails()) {
            $title = $request->input('title');
            $unit = $request->input('property');
            $list = $request->input('list');

            $newWarn = new Warning();
            $newWarn->id_unit = $unit;
            $newWarn->title = $title;
            $newWarn->status = 'IN_REVIEW';
            $newWarn->datecreated = date('Y-m-d');
            if ($list && is_array($list)) {
                $photos = [];
                foreach ($list as $listItem) {
                    $url = explode('/', $listItem);
                    $photos[] = end($url);
                }
                $newWarn->photos = implode(',', $photos);
            } else {
                $newWarn->photos = '';
            }
            $newWarn->save();
        } else {
            $array['error'] = $validator->errors()->first();
        }

        return $array;
    }
}
