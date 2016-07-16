<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 7/16/16
 * Time: 1:49 AM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\WorkorderFile as WorkorderFileModel;

class WorkorderFile extends BaseController {

    public function getWorkorderFiles($workorderId) {
        try {
            $workorderFiles = WorkorderFileModel::where('workorder_id', $workorderId)->get();
            return response()->json(compact('workorderFiles'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function uploadWorkorderFiles(Request $request) {
        return WorkorderFileModel::uploadFiles($request);
    }
}