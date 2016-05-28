<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/27/16
 * Time: 1:54 PM
 */

namespace App\Models;

use Barryvdh\DomPDF;
use Illuminate\Support\Facades\App;
use DB;


class Report
{
    public function generate($html) {
        $pdf = App::make('dompdf.wrapper');;
        $pdf->loadHTML($html);
        return $pdf->stream();
    }

    public function getMetaData($id) {
        $data = DB::table('inspection_meta')->where('workorder_id', $id)->get();
        $meta = [];

        foreach ($data as $key => $value) {
            $meta[$value->key] = $value->value;
        }

        $meta = (object) $meta;
        return $meta;
    }

    public function getInspection($id) {
        $data = DB::table('work_order')
            ->select(DB::raw('CONCAT(work_order.first_name, " ", work_order.last_name) as insured'),
                'address',
                DB::raw('CONCAT(work_order.city, "/", work_order.state, "/", work_order.zip_code) as addressLine2'),
                'policy_num', 'date_of_inspection', 'user.name as adjuster', 'user_profiles.insurance_company as insurance_company')
            ->leftJoin('user', 'user.id', '=', 'work_order.adjuster_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'user.id')
            ->where('work_order.id', $id)->get();
        
        // We need to convert our time string to a date
        $time = $data[0]->date_of_inspection / 1000;
        $data[0]->date_of_inspection = date('Y-m-d h:i:s', $time);
        return $data;
    }

}