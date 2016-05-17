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
use App\Models\Time;

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

        try {
            $inspections = DB::table('work_order')->select('first_name', 'last_name', 'date_of_inspection',
                'meta.value as inspection_outcome')
                ->leftJoin('inspection_meta as meta', 'work_order.id', '=', 'meta.workorder_id')
                ->whereBetween('work_order.date_of_inspection', [$time->getStart(), $time->getEnd()])
                ->groupBy('work_order.id')
                ->get();
            return response()->json(compact('inspections'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getInspectionsByInspector($start, $end, $id) {
        $time = new Time(date('Y-m-d 00:00:00', strtotime(str_replace('-', '/', $start))),
            date('Y-m-d 23:59:59', strtotime(str_replace('-', '/', $end))));

        try {
            $inspections = DB::table('work_order')->select('first_name', 'last_name', 'date_of_inspection',
                'meta.value as inspection_outcome')
                ->leftJoin('inspection_meta as meta', 'work_order.id', '=', 'meta.workorder_id')
                ->whereBetween('work_order.date_of_inspection', [$time->getStart(), $time->getEnd()])
                ->where('inspector_id', $id)
                ->groupBy('work_order.id')
                ->get();
            return response()->json(compact('inspections'), 200);
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