<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/29/16
 * Time: 8:46 PM
 */

namespace App\Http\Controllers;

use App\Models\Categories;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Photos;

class Photo extends BaseController {
    var $photoModel;

    public function __construct()
    {
        $this->photoModel = new Photos();
    }

    public function getPhotos($id) {
        return $this->photoModel->getPhotos($id);
    }

    public function uploadPhotos(Request $request, $id) {
        return $this->photoModel->uploadPhoto($request, $id);
    }

    public function getParentCategories(Request $request) {

        try {
            $categories = Categories::where('parent_id', 0)->get();
            return response()->json(compact('categories'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }

    }

    public function getSubCategories(Request $request, $id) {
        try {
            $categories = Categories::where('parent_id', $id)->get();
            return response()->json(compact('categories'), 200);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getMicroCategories(Request $request, $id) {
        try {
            $categories = Categories::where('sub_parent_id', $id)->get();
            return response()->json(compact('categories'), 200);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getPhotosByParent($id, $parentId) {
        try {
            $photos = Photos::where('parent_id', $parentId)->where('workorder_id', $id)->get();
            return response()->json(compact('photos'), 200);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function savePhotos(Request $request) {
        return $this->photoModel->savePhotos($request);
    }

    public function deletePhotos(Request $request, $workorderId) {
        $this->photoModel->deletePhotos($request);
        return $this->photoModel->getPhotos($workorderId);
    }
}