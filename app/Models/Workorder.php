<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/11/16
 * Time: 9:42 PM
 */

namespace App\Models;

use App\Http\Controllers\Reports;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Input;
use DB;
use League\Flysystem\Exception;
use Psy\Exception\ErrorException;

class Workorder extends Model {
    protected $table = 'work_order';
    protected $fillable = ['adjuster_id', 'policy_num', 'date_received', 'date_of_inspection', 'inspection_type',
    'auto_upgrade', 'type_of_damage', 'date_of_loss', 'has_tarp', 'estimate_requested', 'special_instruction_adjuster',
    'claim_num', 'special_instruction_insured', 'first_name', 'last_name', 'street_address', 'city', 'state',
    'zip', 'phone_1', 'phone_2', 'phone_3'];

    const NEW_INSPECTION = 1;

    // Inspection types
    const BASIC_INSPECTION = 0;
    const EXPERT_INSPECTION = 1;
    const LADDER_ASSIST = 2;

    public function adjuster() {
        return $this->belongsTo('\App\User');
    }

    public function status() {
        return $this->hasOne('\App\Models\WorkorderStatuses', 'status_id');
    }

    public function invoice() {
        return $this->belongsTo('\App\Models\Invoice');
    }

    public function inspection() {
        return $this->hasOne('\App\Models\Inspection', 'inspection_id');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveWorkorder() {
        $data = (object) Input::all();
        $adjusterId = null;

        try {
            // See if client property has an id field.
            $adjuster = (object) $data->adjuster;
            if (isset($adjuster->id)) {
                $adjusterId = $adjuster->id;
                DB::table('user_profiles')->where('user_id', $adjuster->id)->update($adjuster->profile);
            } else {
                // Create adjuster
                try {
                    $user_adjuster = new \App\User();
                    $user_adjuster->name = $adjuster->profile['first_name'] . ' ' . $adjuster->profile['last_name'];
                    $user_adjuster->email = $adjuster->email;
                    $user_adjuster->password = password_hash('123456', PASSWORD_BCRYPT);
                    $user = $user_adjuster->save();

                    if ($user) {
                        $adjusterId = $user_adjuster->id;
                        $user_adjuster_profile = new Profile();
                        foreach ($adjuster->profile as $key => $value) {
                            $user_adjuster_profile[$key] = $value;
                        }

                        $user_adjuster_profile->user_id = $user_adjuster->id;

                        // Add information to profile
                        try {
                            $user_adjuster->profile()->save($user_adjuster_profile);
                        } catch (ErrorException $e) {
                            return response()->json(compact('e'));
                        }

                        // Lastly, add the appropriate role_id
                        try {
                            $role_user = new RolesUser();
                            $role_user->role_id = 4;
                            $role_user->user_id = $user_adjuster->id;
                            $role_user->save();
                        } catch (QueryException $e) {
                            return response()->json(compact('e'));
                        }

                    }
                } catch (QueryException $e) {
                    return response()->json(compact('e'));
                }
            }

            // Transpose our $workorder object from $data
            $workorder = new Workorder();
            foreach ($data as $key => $value) {
                if ($key != 'query_string') {
                    if ($key == 'adjuster') {
                        $workorder['adjuster_id'] = $adjusterId;
                    } else {
                        $workorder[$key] = $value;
                    }
                }
            }

            if (isset($data->id)) {
                $workorder->exists = true;
                $workorder->save();
            } else {
                $workorder->status_id = Workorder::NEW_INSPECTION;
                $workorder->save();
            }

            $workorder->adjuster;
            $workorder->adjuster->profile;

            return $workorder;

        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getWorkorder($id) {
        try {
            $order = Workorder::find($id);
            $order->adjuster;
            $order->adjuster->profile;
            $order->adjuster->rolesUser;

            // Add our inspection type
            switch ($order->inspection_type) {
                case self::BASIC_INSPECTION:
                    $order->inspection_val = 'Basic Inspection';
                    break;
                case self::EXPERT_INSPECTION:
                    $order->inspection_val = 'Expert Inspection';
                    break;
                case self::LADDER_ASSIST:
                    $order->inspection_val = 'Ladder Assist';
            }

            unset($order->adjuster_id);

            return response()->json(compact('order'));
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @return Exception|\Exception|\Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAllWorkOrders() {
        try {
            return $reports = Workorder::all();
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @param $statuses
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getWorkOrdersByStatuses($statuses) {
        try {
            return DB::select('select * from work_order where status_id in (:ids)', ['ids' => $statuses]);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getInspectorWorkorders($id)
    {
        try {

            // dates
            $counts = new Counts();
            $dates = $counts->get_dates();

            $today = Workorder::where('inspector_id', '=', $id)->get();
//                ->whereBetween('date_of_inspection', [$dates->today->format('Y-m-d 00:00:00'), $dates->today->format('Y-m-d 23:59:59')])
//                ->get();
//            $yesterday = Workorder::where('inspector_id', '=', $id)
//                ->whereBetween('date_of_inspection', [$dates->today->format('Y-m-d 00:00:00'), $dates->today->format('Y-m-d 23:59:59')])
//                ->get();

            $results = $today;

            $orders = [];

            foreach ($results as $key => $order) {
                if ($key == 'date_of_inspection') {
                    // $order->date_of_inspection = new \DateTime($order[$key]);
                }
                $order->adjuster;
                $order->adjuster->profile;


                if (in_array($order->status_id, array(Reports::NEW_PICKUP, Reports::INSPECTOR_ATTENTION_REQUIRED))) {
                    $status = $order->status_id == Reports::NEW_PICKUP ? 'new_pickups' : 'inspector_attention_required';
                    if (!isset($orders[$status])) {
                        $orders[$status] = array($order);

                    } else {
                        array_push($orders[$status], $order);
                    }
                }
            }

            return response()->json(compact('orders'));
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }
 }