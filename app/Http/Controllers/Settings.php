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

class Settings extends BaseController {

    var $categoryModel;

    public function __construct() {
        $this->categoryModel = new Categories();
    }

    public function saveCategories(Request $request) {
        return $this->categoryModel->saveCategories($request);
    }

}