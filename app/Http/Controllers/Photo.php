<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/29/16
 * Time: 8:46 PM
 */

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Util\Shared;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Photos;

class Photo extends BaseController {
    var $photoModel;
    var $categoryModel;

    public function __construct()
    {
        $this->photoModel = new Photos();
        $this->categoryModel = new Categories();
    }

    public function getPhotos($id) {
        return $this->photoModel->getPhotos($id);
    }

    public function uploadPhotos(Request $request, $id) {
        return $this->photoModel->uploadPhoto($request, $id);
    }

    public function getParentCategories($id) {

        try {
            $categories = Categories::where('parent_id', 0)->get();

            if ($id != -1) {
                foreach ($categories as $category) {
                    $this->photoModel->setCategoryPhotoCount($category, $id);
                }
            }

            return response()->json(compact('categories'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }

    }

    public function getSubCategories($parentId, $workorderId) {
        try {
            $categories = Categories::where('parent_id', $parentId)->get();
            if ($workorderId != -1) {
                foreach ($categories as $category) {
                    $this->photoModel->setSubCategoryPhotoCount($category, $workorderId);
                }
            }
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

    public function getCategoryTree() {
        $tree = $this->categoryModel->buildCategoryList();
        return response()->json(compact('tree'), 200);
    }
    
    public function getLabeledPhotos($workorderId, $parentId, $subParentId, $labelName) {
        return $this->photoModel->getLabeledPhotos($workorderId, $parentId, $subParentId, rawurldecode($labelName));
    }

    public function savePhotos(Request $request) {
        return $this->photoModel->savePhotos($request);
    }

    public function rotatePhotos(Request $request) {
        return $this->photoModel->rotateImages($request);
    }

    public function deletePhotos(Request $request, $workorderId) {
        $this->photoModel->deletePhotos($request);
        return $this->photoModel->getPhotos($workorderId);
    }

    public function resizePhotos(Request $request) {
        return $this->photoModel->resize($request);
    }

    /**
     * Use workorder_id {$id} to find all the photo files and send to Shared::createZip
     * @param $id
     */
    public function getZippedFiles($id) {
        try {
            $files = Photos::where('workorder_id', $id)->get();
            $zipFileUrl = Shared::createZip($files, '/tmp/photos' . $id . '.zip', $id);
            $zipFileUrl = 'https://s3.amazonaws.com/trinity-content/' . $zipFileUrl;
            return response()->json(array('file' => $zipFileUrl), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }
}