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

class Counts extends Model {

    const EXPERT = 1;
    const BASIC = 0;

    protected $dates;

    public function __construct() {

        $this->dates = array('today' => new DateTime('today'),
            'tomorrow' => new DateTime('tomorrow'),
            'yesterday' => new DateTime('yesterday'),
            'this_week' => new DateTime('this week'),
            'next_week' => new DateTime('next week'),
            'last_week' => new DateTime('last week'),
            'two_last_week' => new DateTime('-2 week'),
            'two_next_week' => new DateTime('+2 week'),
            'this_month' => new DateTime('first day of this month'),
            'last_month' => new DateTime('first day of last month'),
            'last_day_of_this_month' => new DateTime('last day of this month'),
            '3_month_now' => new DateTime('+3 month'),
            '4_month_now' => new DateTime('+4 month'),
            'next_month' => new DateTime('first day of next month'),
            'last_day_of_next_month' => new DateTime('last day of next month'),
            'this_year' => new DateTime('this year'),
            'next_year' => new DateTime('next year'),
            'last_year' => new DateTime('last year')
        );
    }

    /**
     * @return array
     */
    public static function get_dates() {
        $c = new Counts();
        $object = new \stdClass();

        foreach ($c->dates as $key => $date) {
            $object->$key = $date;
        }

        return $object;
    }

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
        $dates = $this->get_dates();

        $query = DB::table('work_order')->select(
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->today->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) today"),
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->today->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newToday"),
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->yesterday->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) yesterday"),
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->yesterday->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newYesterday"),
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->tomorrow->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) tomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection LIKE '{$dates->tomorrow->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) newTomorrow"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->this_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->next_week->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_week"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->this_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->next_week->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_week"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->next_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->two_next_week->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_week"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->next_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->two_next_week->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_week"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->last_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->this_week->format('Y-m-d')}%' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_week"),
            DB::raw("CAST(sum(case when date_of_inspection >= '{$dates->last_week->format('Y-m-d')}%' AND date_of_inspection < '{$dates->this_week->format('Y-m-d')}%' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_week"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->this_month->format('Y-m')}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->this_month->format('Y-m')}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->last_month->format('Y-m')}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->last_month->format('Y-m')}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->next_month->format('Y-m')}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) next_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 2) = '{$dates->next_month->format('Y-m')}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_next_month"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 1) = '{$dates->this_year->format('Y')}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) this_year"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 1) = '{$dates->this_year->format('Y')}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_this_year"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 1) = '{$dates->last_year->format('Y')}' AND inspection_type = {$type} then 1 else 0 end) AS UNSIGNED) last_year"),
            DB::raw("CAST(sum(case when SUBSTRING_INDEX(date_of_inspection, '-', 1) = '{$dates->last_year->format('Y')}' AND inspection_type = {$type} AND status_id = 1 then 1 else 0 end) AS UNSIGNED) new_last_year")
        );
        $results = $query->get();
        return $results;
    }
}