<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/22/16
 * Time: 8:11 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Input;
use League\Flysystem\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class Inspection extends Model {

    var $workorder;

    protected $table = 'inspection_meta';

    const OUTCOME_CHARGES = [1, 2, 3, 4, 5, 6, 7];
    const HARNESS_CHARGE = 9;
    const TARP_CHARGES = [10, 11, 12, 13, 14, 15, 16, 17, 18];

    public function workorder() {
        return $this->belongsTo('App\Models\Workorder', 'id', 'workorder_id');
    }

    public function inspectionOutcome() {
        return $this->belongsTo('App\Models\InspectionOutcome');
    }

    public function getInspection($id) {
        $inspection = Inspection::where('workorder_id', $id)->get();
        return response()->json(compact('inspection'));
    }

    public function inspectionOutcomes() {
        $outcomes = DB::table('inspection_outcomes')->select('*')->get();
        $harnessCharges = DB::table('billing_options')->select('amount')->where('billing_type', self::HARNESS_CHARGE)->get();
        $outcomeCharges = DB::table('billing_options')->select('amount', 'type')
            ->join('billing_types', 'billing_options.billing_type', '=', 'billing_types.id')
            ->whereIn('billing_type', self::OUTCOME_CHARGES)->get();
        $tarpCharges = DB::table('billing_options')->select('amount')->whereIn('billing_type', self::TARP_CHARGES)->get();

        return response()->json(compact('outcomes', 'harnessCharges', 'outcomeCharges', 'tarpCharges'));
    }

    /**
     * Save inspection form, need to seriously re-look and possibly switch databases to mongo or another nosql database.
     * If following a relational database schema, you will have a bunch of smaller tables to relate to an inspection
     * table or an inspection table with 100+ columns. I'm not sure what is best, hopefully whoever looks at this in
     * the future can make a better decision than me.
     */
    public function saveForm()
    {
        $POST = (object)Input::all();
        $workorder_id = $POST->workorder_id;
        $data = [];

        // Get current user
        $user = JWTAuth::parseToken()->authenticate();

        // unset two fields that are not needed to be inserted to meta fields.
        unset($POST->workorder_id);
        unset($POST->query_string);


        // Need to compare old items to new items to see what ones changed.
        $meta = Inspection::where('workorder_id', $workorder_id)->get();
        $metaNice = [];

        // Get data into a nicely formatted array
        foreach ($meta as $key => $val) {
            array_push($metaNice, array(
                'id' => $val->id,
                'workorder_id' => $val->workorder_id,
                'key' => $val->key,
                'value' => $val->value
            ));
        }

        // Set values into $data
        foreach ($POST as $key => $val) {
            $val = is_array($val) ? json_encode($val) : $val;
            array_push($data, array(
                'id' => null,
                'workorder_id' => $workorder_id,
                'key' => $key,
                'value' => $val
            ));
        }

        try {

            // Compare $data and $metaNice
            foreach ($data as $k => $v) {
                foreach ($metaNice as $_k => $_v) {
                    if ($data[$k]['key'] === $metaNice[$_k]['key'] && $data[$k]['value'] != $metaNice[$_k]['value']) {
                        $message = $data[$k]['key'] . ' was updated to ' . $data[$k]['value'];

                        $log = new Log();
                        $log->workorder_id = $workorder_id;
                        $log->message = $message;
                        $log->updated_by = $user->id;
                        $log->save();
                    }
                }
            }

            DB::table('inspection_meta')->where('workorder_id', $workorder_id)->delete();
            Inspection::insert($data);

            return response()->json(compact('data'), 200);

        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function updateMeta($workorder_id, $key, $value)
    {
        DB::table('inspection_meta')->insert(
            ['workorder_id' => $workorder_id, 'key' => $key, 'value' => $value]
        );
    }

}