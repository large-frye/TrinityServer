<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/24/16
 * Time: 8:55 AM
 */

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\Workorder;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class Inspector extends BaseController {

    public function __construct()
    {
        $this->workorderModel = new Workorder();
        $this->reportModel = new Reports();
    }

    public function getWorkorders($id)
    {
        return $this->workorderModel->getInspectorWorkorders($id);
    }

    public function getReports($status, $id)
    {
        return $this->reportModel->getInspectorReports($id, $status);
    }
}