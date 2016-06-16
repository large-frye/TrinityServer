<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/15/16
 * Time: 11:10 PM
 */

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Support\Facades\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Resources extends BaseController {
    
    var $resourceModel;
    
    public function __construct() {
        $this->resourceModel = new Resource();
    }

    public function saveResource(Request $request) {
        return $this->resourceModel->saveResource($request);
    }
}