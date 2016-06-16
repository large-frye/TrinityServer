<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/15/16
 * Time: 11:15 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Http\ResponseFactory;

class Resource extends Model {
    public $table = 'resources';
    public $fillable = ['id', 'name', 'file_url', 'file_type'];

    /**
     * Save a resource
     * @param $request
     * @return ResponseFactory
     */
    public function saveResource($request) {
        try {
            $resources = $request->resources;

            foreach($resources as $r) {
                if (isset($r['id'])) {
                    $resource = Resource::find($r['id']);
                } else {
                    $resource = new Resource();
                }

                // save/update resources
                $resource->name = $request->name;
                $resource->file_url = $request->file_url;
                $resource->file_type = $request->file_type;
                $resource->save();
            }

            return response()->json(compact('resources'), 200);
        } catch (ModelNotFoundException $e) {
            die ($e);
        }
    }
}