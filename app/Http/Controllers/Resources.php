<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/15/16
 * Time: 11:10 PM
 */

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Resources extends BaseController {
    
    var $resourceModel;
    
    public function __construct() {
        $this->resourceModel = new Resource();
    }

    public function saveResource(Request $request) {
        return $this->resourceModel->saveResource($request);
    }

    public function getResources(Request $request) {
        try {
            $resources = $this->resourceModel->getResources($request->types);
            return response()->json(compact('resources'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function uploadResource(Request $request) {
        return $this->resourceModel->uploadResource($request);
    }

    public function deleteResource($id) {
        try {
            $resource = Resource::find($id);
            $resourceType = $resource->resource_type;
            $resource->delete();
            $resources = $this->resourceModel->getResources(array($resourceType));
            return response()->json(compact('resources'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }
}