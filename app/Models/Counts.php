<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/7/16
 * Time: 7:42 PM
 */

namespace App\Models;

use App\Http\Controllers\Reports;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use DB;
use App\Models\Count;

class Counts extends Model {

    const EXPERT = 1;
    const BASIC = 0;
    const CANCELLED = 16;

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
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->today}' AND '{$this->countModel->tomorrow}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) todayCancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->yesterday}' AND '{$this->countModel->today}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) yesterday"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->yesterday}' AND '{$this->countModel->today}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newYesterday"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->yesterday}' AND '{$this->countModel->today}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) yesterdayCancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->tomorrowStart}' AND '{$this->countModel->tomorrowEnd}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) tomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->tomorrowStart}' AND '{$this->countModel->tomorrowEnd}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newTomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->tomorrowStart}' AND '{$this->countModel->tomorrowEnd}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) tomorrowCancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisWeek}' AND '{$this->countModel->nextWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisWeek}' AND '{$this->countModel->nextWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisWeek}' AND '{$this->countModel->nextWeek}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) this_week_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextWeek}' AND '{$this->countModel->twoNextWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextWeek}' AND '{$this->countModel->twoNextWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextWeek}' AND '{$this->countModel->twoNextWeek}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) next_week_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastWeek}' AND '{$this->countModel->thisWeek}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastWeek}' AND '{$this->countModel->thisWeek}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_week"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastWeek}' AND '{$this->countModel->thisWeek}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) last_week_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisMonth}' AND '{$this->countModel->nextMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisMonth}' AND '{$this->countModel->nextMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->thisMonth}' AND '{$this->countModel->nextMonth}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) this_month_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastMonth}' AND '{$this->countModel->thisMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastMonth}' AND '{$this->countModel->thisMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastMonth}' AND '{$this->countModel->thisMonth}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) last_month_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextMonth}' AND '{$this->countModel->lastDayOfNextMonth}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextMonth}' AND '{$this->countModel->lastDayOfNextMonth}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_month"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->nextMonth}' AND '{$this->countModel->lastDayOfNextMonth}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) next_month_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->year}' AND '{$this->countModel->nextYear}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->year}' AND '{$this->countModel->nextYear}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->year}' AND '{$this->countModel->nextYear}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) this_year_cancelled"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastYear}' AND '{$this->countModel->year}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastYear}' AND '{$this->countModel->year}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_year"),
            DB::raw("CAST(sum(case when date_of_inspection BETWEEN '{$this->countModel->lastYear}' AND '{$this->countModel->year}' AND inspection_type = {$type} AND status_id = " . Counts::CANCELLED . " then 1 else 0 end) AS UNSIGNED) last_year_cancelled"),
            DB::raw("CAST(sum(case when inspection_type = " . Reports::ON_HOLD . " then 1 else 0 end) AS UNSIGNED) on_hold")
        );
        $results = $query->get();
        return $results;
    }

    /**
     * Find all the counts for only top tables on report listing page
     * @param Request $request
     * @return mixed
     */
    public function findTopCounts($request) {
        if (isset($request)) {
            // Todo:
        }

        return DB::table('work_order')
            ->select(
                DB::raw("CAST(SUM(CASE WHEN alert_to_inspector = 1 then 1 else 0 end) AS UNSIGNED) inspector_attention_required"),
                DB::raw("CAST(SUM(CASE WHEN alert_office = 1 || alert_from_inspector = 1 then 1 else 0 end) AS UNSIGNED) office_attention_required"),
                DB::raw("CAST(SUM(CASE WHEN alert_admin = 1 then 1 else 0 end) AS UNSIGNED) admin_attention_required"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::ON_HOLD . " then 1 else 0 end) AS UNSIGNED) on_hold"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::ON_HOLD . " then 1 else 0 end) AS UNSIGNED) on_hold"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::NEW_INSPECTION . " then 1 else 0 end) AS UNSIGNED) new"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::NEW_PICKUP . " then 1 else 0 end) AS UNSIGNED) new_pickups"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::RESCHEDULE . " then 1 else 0 end) AS UNSIGNED) reschedule"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::IN_PROCESS . " then 1 else 0 end) AS UNSIGNED) in_process"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::SCHEDULED . " then 1 else 0 end) AS UNSIGNED) scheduled"),
                DB::raw("CAST(SUM(CASE WHEN date_of_inspection < now() && status_id = " . Reports::SCHEDULED . " then 1 else 0 end) AS UNSIGNED) post_inspection_date"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::INSPECTED . " then 1 else 0 end) AS UNSIGNED) inspected"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::REPORTING . " then 1 else 0 end) AS UNSIGNED) reporting"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::INVOICE_ALACRITY . " then 1 else 0 end) AS UNSIGNED) inv_alacrity"),
                DB::raw("CAST(SUM(CASE WHEN status_id = " . Reports::INVOICING . " then 1 else 0 end) AS UNSIGNED) invoicing"),
                DB::raw("CAST(SUM(CASE WHEN status_id IN (" . Reports::CANCELLED . " ) then 1 else 0 end) AS UNSIGNED) cancelled")
            )
            ->get();
    }
}