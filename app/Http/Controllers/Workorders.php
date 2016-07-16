<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/21/16
 * Time: 11:46 PM
 */

namespace App\Http\Controllers;

use App\Models\Logger;
use App\Models\Report;
use App\Models\WorkorderStatuses;
use App\User;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use League\Flysystem\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Workorder;


class Workorders extends BaseController
{
    var $workorder, $counts, $_workorder;

    public function __construct()
    {
        $this->workorder = new \App\Models\Workorders();
        $this->_workorder = new Workorder();
        $this->counts = new \App\Models\Counts();
    }

    /**
     *
     */
    public function getWorkOrders($start, $end) {
        return response()->json(['w' => $this->workorder->findWorkOrders($start, 0)]);
    }

    /**
     * @param $attr
     * @param $value
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getWorkorderByAttribute($attr, $value) {
        return response()->json($this->workorder->findWorkorderByAttribute($attr, $value));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCounts(Request $request) {
        $ladderAssist = $this->counts->findCounts(5);
        $ladderAssistWithReport = $this->counts->findCounts(0);
        $expert = $this->counts->findCounts(1);
        $topCounts = $this->counts->findTopCounts($request);

        return response()->json(compact('ladderAssist', 'ladderAssistWithReport', 'expert', 'topCounts'), 200);
    }

    /**
     * Returns top status counts
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getTopCounts(Request $request) {
        try {
            $counts = $this->counts->findTopCounts($request);
            return response()->json(compact('counts'), 200);
        } catch (Exception $e) {
            return response()->json(array('error' => $e->getMessage()), 500);
        }
    }

    public function getWorkordersByTime($time, $type) { return $this->workorder->findWorkordersByTime($time, $type); }

    public function log(Request $request) { return Logger::log($request); }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update(Request $request) { return $this->workorder->updateWorkorder($request); }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function save() { return $this->_workorder->saveWorkorder(); }

    public function get($id) { return $this->_workorder->getWorkorder($id); }

    public function getByInspector($id, $userId) { return $this->_workorder->getInspectorWorkorder($id, $userId); }

    public function all() { return $this->_workorder->getAllWorkOrders(); }

    /**
     * Get all statuses, some are hidden per request. 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getStatuses() {
        $hiddenStatuses = [Reports::ALERT, Reports::CALLED_PH, Reports::SENT, Reports::INSPECTION_COMPLETED,
            Reports::PRE_INVOICE, Reports::INVOICED];
        $statuses = WorkorderStatuses::whereNotIn('id', $hiddenStatuses)
            ->orderBy('display_order')
            ->get();
        return response()->json(compact('statuses'));
    }

    /**
     * TODO: Needs to be moved to model
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function lockInspectorBilling($id) {
        $workorder = Workorder::find($id);
        $billingLocked = $workorder->billing_locked;

        switch ($billingLocked) {
            case null:
            case 0:
                $workorder->billing_locked = 1;
                break;
            case 1:
                $workorder->billing_locked = 0;
                break;
        }

        $workorder->save();
        return response()->json(compact('workorder'), 200);
    }
}