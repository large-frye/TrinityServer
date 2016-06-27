<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/26/16
 * Time: 10:40 PM
 */

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Routing\Controller as BaseController;

class Logger extends BaseController
{

    public function getWorkorderLog($id) {
        try {
            $logs = Log::where('workorder_id', $id)->orderBy('created_at', 'desc')->get();
            foreach ($logs as $log) {
                $log->updater;
                $log->updater->profile;
            }
            return response()->json(compact('logs'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

}