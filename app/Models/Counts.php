<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/7/16
 * Time: 7:42 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;
use DB;
use App\Models\Count;

class Counts extends Model {

    const EXPERT = 1;
    const BASIC = 0;

    var $countModel;

    protected $dates;

    public function __construct() {}

    /**
     * @return mixed
     * deprecated
     */
    public function queryCounts() { }

    /**
     * Helper to help duplication of query
     * @param $type
     * @param $dates
     * @return mixed
     */
    public function findCounts($type) {
        $this->countModel = new Count();

        $query = DB::table('work_order')->select(
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->today}' AND '{$this->countModel->tomorrow}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) today"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->today}' AND '{$this->countModel->tomorrow}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newToday"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->yesterday}' AND '{$this->countModel->today}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) yesterday"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->yesterday}' AND '{$this->countModel->today}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newYesterday"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->tomorrow}' AND '{$this->countModel->nextTwoDays}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) tomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->tomorrow}' AND '{$this->countModel->nextTwoDays}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newTomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisWeek}' AND '{$this->countModel->nextWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisWeek}' AND '{$this->countModel->nextWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextWeek}' AND '{$this->countModel->twoNextWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextWeek}' AND '{$this->countModel->twoNextWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastWeek}' AND '{$this->countModel->thisWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastWeek}' AND '{$this->countModel->thisWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisMonth}' AND '{$this->countModel->nextMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisMonth}' AND '{$this->countModel->nextMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastMonth}' AND '{$this->countModel->thisMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastMonth}' AND '{$this->countModel->thisMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextMonth}' AND '{$this->countModel->lastDayOfNextMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextMonth}' AND '{$this->countModel->lastDayOfNextMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->year}' AND '{$this->countModel->nextYear}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->year}' AND '{$this->countModel->nextYear}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastYear}' AND '{$this->countModel->year}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastYear}' AND '{$this->countModel->year}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_year")
        );
        $results = $query->get();
        return $results;
    }
}