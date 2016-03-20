<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/7/16
 * Time: 10:13 AM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use League\Flysystem\Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use DB;

class Workorders extends Model {
    //
    protected $table = 'work_orders';
    protected $fillable = ['first_name', 'last_name', 'type', 'user_id', 'policy_number', 'street_address', 'city',
    'state', 'zip', 'phone', 'phone2', 'is_expert', 'damage_type', 'date_of_loss', 'interior_inspection',
    'adjuster_present', 'tarped', 'estimate_scope_requirement', 'special_instructions', 'status', 'inspector_id',
    'inspector_status', 'date_of_inspection', 'price', 'is_generated_pdf', 'last_generated', 'generate_report_status',
    'comments', 'commenter_id', 'latitude', 'longtitude', 'pdfLoc'];
    public function adjuster() {
        return $this->morphOne('App\Models\Profile', 'user_id');
    }

    public function insuredProfile() {
        return $this->hasOne('App\Models\InsuredProfile');
    }

    public function clientProfile() {
        return $this->hasOne('App\Models\ClientProfile');
    }

    /**
     * Get all work orders that exist
     *
     * @param $start
     * @param $end
     * @return mixed
     */
    public function findWorkorders($start, $end) {
        return Workorders::where('id', '>', 30)
            ->orderBy('id', 'desc')
            ->groupBy('id')
            ->take($start)
            ->get();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findWorkorderById($id) {
        return Workorders::where('id', '=', $id)->get();
    }

    /**
     * @param $attr
     * @param $value
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findWorkorderByAttribute($attr, $value) {
        try {
            return Workorders::where($attr, '=', $value)->get();
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @param $time
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function findWorkordersByTime($time, $type) {
        $dates = Counts::get_dates();

        $type = $type == 'basic' ? 0 : 1;

        switch ($time) {
            case 'yesterday':
                return $this->findDayDate($dates->yesterday->format('Y-m-d'), $type);
            case 'today':
                return $this->findDayDate($dates->today->format('Y-m-d'), $type);
            case 'tomorrow':
                return $this->findDayDate($dates->tomorrow->format('Y-m-d'), $type);
            case 'this-week':
                return $this->findWeekDate($dates->this_week->format('Y-m-d'), $dates->next_week->format('Y-m-d'),
                    $type);
            case 'last-week':
                return $this->findWeekDate($dates->two_last_week->format('Y-m-d'), $dates->last_week->format('Y-m-d'),
                    $type);
            case 'next-week':
                return $this->findWeekDate($dates->next_week->format('Y-m-d'), $dates->two_next_week->format('Y-m-d'),
                    $type);
            case 'this-month':
                return $this->findWeekDate($dates->this_month->format('Y-m-d'), $dates->next_month->format('Y-m-d'),
                    $type);
            case 'last-month':
                return $this->findMonthDate($dates->last_month->format('Y-m'), $type);
            case 'three-month':
                return $this->findWeekDate($dates->three_month->format('Y-m-d'), $dates->last_month->format('Y-m-d'),
                    $type);
            case 'this-year':
                return $this->findYearDate($dates->this_year->format('Y'), $type);
            case 'last-year':
                return $this->findYearDate($dates->last_year->format('Y-m-d'), $type);
        }
    }

    /**
     * @param $day
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function findDayDate($day, $type) {
        try {

            $query = $this->whereRaw('STR_TO_DATE(date_of_inspection, \'%Y-%m-%d\') LIKE ? AND type = ?
                ORDER BY id DESC', array($day, $type));
            return $query->getBindings();
            return response()->json(['w' => $query->get()]);
        } catch (QueryException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @param $start
     * @param $end
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function findWeekDate($start, $end, $type) {
        $query = "STR_TO_DATE(created_at, '%Y-%m-%d')";
        try {
            return response()->json(['w' => $this->whereRaw("STR_TO_DATE(created_at, '%Y-%m-%d') >= ?
                        AND STR_TO_DATE(created_at, '%Y-%m-%d') <= ?
                        AND type = ?
                        ORDER BY work_orders.id DESC",
                array($start, $end, $type))->get()]);
        } catch (QueryException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @param $date
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function findMonthDate($date, $type) {
        $query = "SUBSTRING_INDEX(date_of_inspection, '-', 2) = ? AND type = ? GROUP BY w.id ORDER BY w.id DESC";
        try {
            $orders = DB::select( DB::raw("SELECT policy_number, w.first_name, w.last_name, CONCAT(p.first_name, ' ', p.last_name) as client_name,
CONCAT(p2.first_name, ' ', p2.last_name) as inspector, date_of_inspection,
CASE type
  WHEN 0 THEN 'Basic Inspection'
  WHEN 1 THEN 'Expert Inspection'
END AS inspection_name
                FROM work_orders w
                LEFT JOIN user_profiles p ON w.user_id = p.user_id
                LEFT JOIN user_profiles p2 ON w.inspector_id = p.user_id
                WHERE " . $query), array($date, $type));

            return response()->json(compact('orders'));
        } catch (QueryException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @param $year
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function findYearDate($year, $type) {
        try {

            $query = $this->whereRaw('SUBSTRING_INDEX(date_of_inspection, \'-\', 1) = ? AND type = ?
                ORDER BY id DESC', array($year, $type));
            return response()->json(['w' => $query->get()]);
        } catch (QueryException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @param $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateWorkorder($request) {
        try {
            $input = \Illuminate\Support\Facades\Input::all();
            $id = $input['id'];

            Workorders::where('id', $id)->firstOrFail();
            unset($input['query_string']); // remove `query_string` from our $input
            $res = Workorders::where('id', '=', $id)->update($input);
            return response()->json(['w' => $res]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @param $values
     */
    public function createWorkorder() {
        try {
            $values = \Illuminate\Support\Facades\Input::all();
            unset($values['query_string']);
            $w = new Workorders();

            foreach ($values as $key => $value) {
                $w[$key] = $value;
            }

            // Our result: $res
            $res = Workorders::create($values);

            return response()->json(['w' => $res]);
        } catch (QueryException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

