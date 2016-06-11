<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/21/16
 * Time: 11:46 PM
 */

namespace App\Http\Controllers;

use App\Models\WorkorderStatuses;
use App\User;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
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
     * @return mixed
     */
    public function getCounts() {
        $basic = $this->counts->findCounts(0);
        $expert = $this->counts->findCounts(1);
        $ladderAssist = $this->counts->findCounts(5);

        return response()->json(compact('basic', 'expert', 'ladderAssist'), 200);
    }

    public function getWorkordersByTime($time, $type) { return $this->workorder->findWorkordersByTime($time, $type); }

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

    public function getStatuses() {
        $statuses = WorkorderStatuses::all();
        return response()->json(compact('statuses'));
    }
}