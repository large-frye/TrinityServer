<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/22/16
 * Time: 8:28 PM
 */

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionTypes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    
    public function getInspectionTypes() {
        try {
            $types = InspectionTypes::whereIn('id', [0, 1, 5])->get();
            return response()->json(compact('types'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }
}