<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/1/16
 * Time: 11:37 PM
 */

namespace App\Http\Controllers;

use App\Models\Categories;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use League\Flysystem\Exception;

class Settings extends BaseController {

    var $categoryModel;

    public function __construct() {
        $this->categoryModel = new Categories();
    }

    public function saveCategories(Request $request) {
        return $this->categoryModel->saveCategories($request);
    }

    public function saveCategory(Request $request) {
        return $this->categoryModel->saveCategory($request);
    }

    public function getParents() {
        try {
            $categories = Categories::where('allowed_to_be_parent', 1)->get();
            return response()->json(compact('categories'), 200);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

}