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

    /**
     * @param $id
     * @return mixed
     */
    public function getSimpleWorkorder($id) {
        try {
            $invoice = DB::table('work_order')->select('first_name', 'last_name', 'date_of_inspection',
                'meta.value as inspection_outcome')
                ->leftJoin('inpection_meta as meta', 'work_order.id', '=', 'meta.workorder_id')
                ->where('work_order.id', $id)->get();
            return response()->json(compact('invoice'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }
}