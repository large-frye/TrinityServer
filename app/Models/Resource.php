<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/15/16
 * Time: 11:15 PM
 */

namespace App\Models;

use App\Util\Shared;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Http\ResponseFactory;

class Resource extends Model {
    public $table = 'resources';
    public $fillable = ['id', 'name', 'file_url', 'file_type'];

    const FILE = 'file';
    const URL = 'url';

    // Resource types
    const RESOURCE = 'resource';
    const TRAINING_MATERIAL = 'trainingMaterial';
    const TRAINING_VIDEOS = 'trainingVideo';
    const OTHER = 'other';

    // Path resource names
    const PATH_RESOURCE = 'resources';
    const PATH_TRAINING_MATERIALS = 'training-materials';
    const PATH_TRAINING_VIDEOS = 'training-videos';
    const PATH_OTHER_RESOURCE = 'other-resources';

    /**
     * Save a resource
     * @param $request
     * @return ResponseFactory
     */
    public function saveResource($request) {
        try {

            $resource = new Resource();

            if ($request->has('id')) {
                $resource = Resource::find($request->id);
            }

            // save/update resources
            $resource->name = $request->name;
            $resource->item_url = $request->item_url;
            $resource->item_type = $request->item_type;
            $resource->display_order = $request->has('display_order') ? $request->display_order : 0;
            $resource->resource_type = $request->resource_type;
            $resource->save();
            $resources = Resource::where('resource_type', $request->resource_type)->orderBy('display_order')
                ->orderBy('created_at')->get();

            return response()->json(compact('resources'), 200);
        } catch (ModelNotFoundException $e) {
            die ($e);
        }
    }

    public function uploadResource($request) {
        $shared = new Shared();

        switch ($request->resource_type) {
            case Resource::RESOURCE:
                $path = Resource::PATH_RESOURCE;
                break;
            case Resource::TRAINING_MATERIAL:
                $path = Resource::PATH_TRAINING_MATERIALS;
                break;
            case Resource::TRAINING_VIDEOS:
                $path = Resource::PATH_TRAINING_VIDEOS;
                break;
            case Resource::OTHER:
                $path = Resource::PATH_OTHER_RESOURCE;
                break;
        }

        try {
            $urls = $shared->upload($_FILES, $request, $path);
            foreach ($urls as $filename => $url) {
                $resource = new Resource();
                $resource->name = $filename;
                $resource->item_url = $url;
                $resource->item_type = Resource::FILE;
                $resource->display_order = 0;
                $resource->resource_type = $request->resource_type;
                $resource->save();
            }

            $resources = $this->getResources(array($request->resource_type));

            return response()->json(compact('resources'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getResources($resourceTypes) {
        $types = array();

        foreach ($resourceTypes as $resourceType) {
            $types[$resourceType] = Resource::where('resource_type', $resourceType)->orderBy('display_order')
                ->orderBy('created_at')->get();
        }

        return $types;
    }
}