<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/15/16
 * Time: 3:44 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Support\Facades\Input;

class Invoice extends Model {

    protected $table = 'invoice';
    protected $fillable = ['date'];
    public function workorder() {
        return $this->belongsTo('\App\Models\Workorder');
    }

    const WEEKS = 52;
    const WORK_WEEK_INCREMENT = 7;

    /**
     * @param $start
     * @param $end
     * @return mixed
     */
    public function getInspectionsByRange($start, $end) {
        $time = new Time(date('Y-m-d 00:00:00', strtotime(str_replace('-', '/', $start))),
            date('Y-m-d 23:59:59', strtotime(str_replace('-', '/', $end))));
        $meta = [];

        try {
            $inspections = DB::table('work_order')->select('first_name', 'last_name', 'date_of_inspection',
                'inspection_outcome', 'id')
                ->whereBetween('work_order.date_of_inspection', [$time->getStart(), $time->getEnd()])
                ->get();

            foreach ($inspections as $inspection) {
                $metaResult = DB::table('inspection_meta')->select('key', 'value', 'workorder_id')->where('workorder_id', $inspection->id)
                    ->whereIn('key', ['harness_charge', 'tarp_charge', 'misc_charge'])
                    ->get();

                if (count($metaResult) > 0) {
                    foreach ($metaResult as $result) {
                        $meta[$result->workorder_id][] = $result;
                    }
                } else {
                    array_push($meta, $metaResult);
                }


            }

            return response()->json(compact('inspections', 'meta'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getInspectionsByInspector($start, $end, $id) {
        $time = new Time(date('Y-m-d 00:00:00', strtotime(str_replace('-', '/', $start))),
            date('Y-m-d 23:59:59', strtotime(str_replace('-', '/', $end))));
        $meta = [];

        try {
            $inspections = DB::table('work_order')->select('first_name', 'last_name', 'date_of_inspection',
                'inspection_outcome', 'id')
                ->whereBetween('work_order.date_of_inspection', [$time->getStart(), $time->getEnd()])
                ->where('inspector_id', $id)
                ->get();

            foreach ($inspections as $inspection) {
                $metaResult = DB::table('inspection_meta')->select('key', 'value', 'workorder_id')->where('workorder_id', $inspection->id)
                    ->whereIn('key', ['harness_charge', 'tarp_charge'])
                    ->get();

                if (count($metaResult) > 0) {
                    foreach ($metaResult as $result) {
                        $meta[$result->workorder_id][] = $result;
                    }
                } else {
                    array_push($meta, $metaResult);
                }
            }

            return response()->json(compact('inspections', 'meta'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * Work week is Sunday to Sunday.
     *
     */
    public function getWorkweeks() {
        $time = new Time(new \DateTime('first sunday of January'), new \DateTime('last sunday of December'));
        $weeks = [];

        for ($i = 0; $i < Invoice::WEEKS - 1; $i++) {
            $current = $time->getStart();
            $next = new \DateTime($time->getStart()->format('m/d/Y'));
            $next = $next->modify('+' . Invoice::WORK_WEEK_INCREMENT . ' days');
            $week = new Time($current->format('m/d/Y'), $next->format('m/d/Y'));


            // check to see if between the date
            if ($this->checkInvoiceDateRange($week->getStart(), $week->getEnd())) {
                array_push($weeks, array('name' => $week->getStart() . ' - ' . $week->getEnd(),
                    'start' => $week->getStart(),
                    'end' => $week->getEnd(),
                    'current' => true));
            } else {
                array_push($weeks, array('name' => $week->getStart() . ' - ' . $week->getEnd(),
                    'start' => $week->getStart(),
                    'end' => $week->getEnd()));
            }

            // increment $current
            $current->modify('+7 days');
        }

        return $weeks;
    }

    public function changeInspectorMileLockState($userId) {
        try {
            $user = User::find($userId);
            $lockedState = $user->profile->is_miles_locked;

            switch ($lockedState) {
                case NULL:
                case 0:
                    $lockedState = 1;
                    break;
                default:
                    $lockedState = 0;
                    break;
            }

            DB::table('user_profiles')->where('user_id', $userId)->update(['is_miles_locked' => $lockedState]);
            return response()->json(array('lockedState' => intval($lockedState)), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function checkInspectorLock($userId) {
        try {
            $user = User::find($userId);
            $lockedState = $user->profile->is_miles_locked;
            return response()->json(array('lockedState' => intval($lockedState)), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @return mixed
     */
    public function saveMileage() {
        $data = (object) Input::all();
        try {
            $mileage = new Mileage();
            foreach ($data as $k => $v) {
                if (!in_array($k, array('query_string', 'total', 'billable'))) {
                    $mileage[$k] = $v;
                }
            }

            if (isset($mileage->id)) {
                $mileage->exists = true;
            }

            $mileage->save();

            return response()->json(compact('mileage'), 200);

        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getWeeklyInspectorMileage($id, $week) {
        try {
            $mileage = DB::table('mileage')->where('inspector_id', $id)->where('week', $week)->get();
            return response()->json(compact('mileage'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 200);
        }
    }

    /**
     * @param $start
     * @param $end
     * @return bool
     */
    private function checkInvoiceDateRange($start, $end) {
        $today = strtotime('today');
        $start = strtotime($start);
        $end = strtotime($end);
        return (($today >= $start) && ($today <= $end));
    }
}