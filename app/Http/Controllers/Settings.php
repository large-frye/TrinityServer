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
use Maatwebsite\Excel\Facades\Excel;

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

    public function categorize($categories) {
        $ret = [];
        foreach ($categories as $category) {
            if ($category->parent_id == 0) {
                if (!isset($ret[0])) {
                    $ret[0] = array($category);
                } else {
                    array_push($ret[0], $category);
                }
            }
        }

        return $ret;
    }

    public function deleteCategory($id) {
        return $this->categoryModel->deleteCategory($id);
    }

    public function createExcel() {
        ob_end_clean();
        ob_start();

        $categories = $this->categorize(Categories::all());

        print_r($categories);

//        Excel::create('Bulk Upload', function($excel) use (&$parents) {
//            foreach ($parents as $parent) {
//                $sheetname = $parent->name;
//                if (strlen($parent->name) >= 31) {
//                    $sheetname = substr($parent->name, 0, 28) . '...';
//                }
//                $excel->sheet($sheetname, function($sheet) use (&$parents) {
//                    foreach ($parents)
//                });
//            }
////            $excel->sheet('sheet name', function($sheet) {
////                $sheet->fromArray(['data', 'data']);});
//        })->download('xlsx');
    }

}