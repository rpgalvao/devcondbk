<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaDisabledDay;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function getAll()
    {
        $array = ['error' => '' ,'list' => ''];
        $daysHelper = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $areas = Area::where('allowed', '1')->get();
        foreach ($areas as $area) {
            $dayList = explode(',', $area['days']);
            $dayGroup = [];

            // Adicionar o primeiro dia
            $lastDay = intval(current($dayList));
            $dayGroup[] = $daysHelper[$lastDay];
            array_shift($dayList);

            // Adicionar os dias relevantes (fora da sequencia)
            foreach ($dayList as $day) {
                if (intval($day) != $lastDay+1) {
                    $dayGroup[] = $daysHelper[$lastDay];
                    $dayGroup[] = $daysHelper[$day];
                }
                $lastDay = intval($day);
            }

            // Adicionar o último dia
            $dayGroup[] = $daysHelper[end($dayList)];

            // Juntando todas as peças
            $dates = '';
            $close = 0;
            foreach ($dayGroup as $group) {
                if ($close === 0) {
                    $dates .= $group;
                } else {
                    $dates .= '-'.$group.',';
                }
                $close = 1 - $close;
            }
            $dates = explode(',', $dates);
            array_pop($dates);

            // Adicionando o horário
            $start = date('H:i', strtotime($area['start_time']));
            $end = date('H:i', strtotime($area['end_time']));
            foreach ($dates as $dKey => $dValue) {
                $dates[$dKey] .= ' '.$start.' às '.$end;
            }

            $array['list'][] = [
                'id' => $area['id'],
                'cover' => asset('storage/'.$area['cover']),
                'title' => $area['title'],
                'dates' => $dates
            ];
        }

        return $array;
    }

    public function setMyReservation($id, Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i:s',
            'property' => 'required'
        ]);
        if (!$validator->fails()) {
            $date = $request->input('date');
            $time = $request->input('time');
            $property = $request->input('property');

            $unit = Unit::find($property);
            $area = Area::find($id);

            if ($unit && $area) {
                $can = true;
                $weekday = date('w', strtotime($date));

                // Verificar se está dentro da disponibilidade padrão
                $allowedDays = explode(',', $area['days']);
                if (!in_array($weekday, $allowedDays)) {
                    $can = false;
                } else {
                    $start = strtotime($area['start_time']);
                    $end = strtotime('-1 hour', strtotime($area['end_time']));
                    $revtime = strtotime($time);
                    if ($revtime < $start || $revtime > $end) {
                        $can = false;
                    }
                }

                // Verificar se está dentro dos Disabled Days
                $isDisabledDay = AreaDisabledDay::where('id_area', $id)->where('day', $date)->count();
                if ($isDisabledDay > 0){
                    $can = false;
                }

                // Verificar se não existe outra reserva no mesmo dia/hora
                $existReservation = Reservation::where('id_unit', $id)->where('reservation_date', $date.' '.$time)->count();
                if ($existReservation > 0) {
                    $can = false;
                }

                if ($can) {
                    $newReservation = new Reservation();
                    $newReservation->id_unit = $property;
                    $newReservation->id_area = $id;
                    $newReservation->reservation_date = $date.' '.$time;
                    $newReservation->save();
                } else {
                    $array['error'] = 'Reserva não permitida nessa data/hora.';
                }
            } else {
                $array['error'] = 'Dados incorretos!';
                return $array;
            }
        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function getDisabledDates($id)
    {
        $array = ['error' => '', 'list' => []];
        $area = Area::find($id);

        if ($area) {
            // Verificar os dias disabled padrão
            $disabledDays = AreaDisabledDay::where('id_area', $id)->get();
            foreach ($disabledDays as $disabledDay) {
                $array['list'][] = $disabledDay['day'];
            }

            // Verificar os dias disabled através do allowed da área
            $allowedDays = explode(',', $area['days']);
            $offDays = [];
            for ($q=0; $q<7; $q++ ) {
                if (!in_array($q, $allowedDays)) {
                    $offDays[] = $q;
                }
            }

            // Verificar os dias proibidos para 3 meses à frente
            $start = time();
            $end = strtotime('+3 months');
            $current = $start;
            $keep = true;
            while ($keep) {
                if ($current < $end) {
                    $wd = date('w', $current);
                    if (in_array($wd, $offDays)) {
                        $array['list'][] = date('Y-m-d', $current);
                    }
                    $current = strtotime('+1 day', $current);
                } else {
                    $keep =false;
                }
            }

        } else {
            $array['error'] = 'Área informada é inexistente!';
            return $array;
        }

        return $array;
    }

    public function getTimes($id, Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d'
        ]);
        if (!$validator->fails()) {
            $date = $request->input('date');
            $area = Area::find($id);
            if ($area) {
                $can = true;

                // Verificar se é dia disabled
                $existDisabledDay = AreaDisabledDay::where('id_area', $id)->where('day', $date)->count();
                if ($existDisabledDay) {
                    $can = false;
                }

                // Verificar se é dia permitido
                $allowedDays = explode(',', $area['days']);
                $weekday = date('w', strtotime($date));
                if (!in_array($weekday, $allowedDays)) {
                    $can = false;
                }

                if ($can) {
                    $start = strtotime($area['start_time']);
                    $end = strtotime($area['end_time']);
                    $times = [];
                    for (
                        $lastTime = $start;
                        $lastTime < $end;
                        $lastTime = strtotime('+1 hour', $lastTime)
                    ) {
                        $times[] = $lastTime;
                    }
                    $timeList =[];
                    foreach ($times as $time) {
                        $timeList[] = [
                            'id' => date('H:i:s', $time),
                            'title' => date('H:i', $time). ' - '.date('H:i', strtotime('+1 hour', $time))
                        ];
                    }

                    // Removendo as reservas existentes
                    $reservations = Reservation::where('id_area', $id)->whereBetween('reservation_date', [$date.' 00:00:00', $date.' 23:59:59'])->get();
                    $toRemove = [];
                    foreach ($reservations as $reservation) {
                        $time = date('H:i:s', strtotime($reservation['reservation_date']));
                        $toRemove[] = $time;
                    }
                    foreach ($timeList as $timeItem) {
                        if (!in_array($timeItem['id'], $toRemove)) {
                            $array['list'][] = $timeItem;
                        }
                    }
                }
            } else {
                $array['error'] = 'Área informada não é válida!';
                return $array;
            }
        } else {
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function getMyReservation(Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $property = $request->input('property');
        if ($property) {
            $unit = Unit::find($property);
            if ($unit) {
                $reservations = Reservation::where('id_unit', $property)->orderBy('reservation_date', 'DESC')->get();
                foreach ($reservations as $reservation) {
                    $area = Area::find($reservation['id_area']);
                    $datarev = date('d/m/Y H:i', strtotime($reservation['reservation_date']));
                    $afterTime = date('H:i', strtotime('+1 hour', strtotime($reservation['reservation_date'])));
                    $datarev .= ' à '.$afterTime;
                    $array['list'][] = [
                        'id' => $reservation['id'],
                        'id_area' => $reservation['id_area'],
                        'title' => $area['title'],
                        'cover' => asset('storage/'.$area['cover']),
                        'datereserved' => $datarev
                    ];
                }
            } else {
                $array['error'] = 'Unidade informada não é válida!';
                return $array;
            }
        } else {
            $array['error'] = 'Necessário informar a unidade!';
            return $array;
        }

        return $array;
    }

    public function delMyReservation($id)
    {
        $array = ['error' => ''];

        $user = auth()->user();
        $reservation = Reservation::find($id);
        if ($reservation) {
            $unit = Unit::where('id', $reservation['id_unit'])->where('id_owner', $user['id'])->count();
            if ($unit > 0) {
                Reservation::find($id)->delete();
            } else {
                $array['error'] = 'Essa reserva não pertence à sua unidade!';
                return $array;
            }
        } else {
            $array['error'] = 'A resrva informada não existe!';
            return $array;
        }

        return $array;
    }
}
