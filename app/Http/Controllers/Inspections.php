<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/22/16
 * Time: 8:28 PM
 */

namespace App\Http\Controllers;

use App\Models\Inspection;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class Inspections extends BaseController {

    var $inspectionModel;

    public function __construct() {
        $this->inspectionModel = new Inspection();
    }

    public function get($id) {
        return $this->inspectionModel->getInspection($id);
    }

    public function getOutcomes() {
        return $this->inspectionModel->inspectionOutcomes();
    }
}