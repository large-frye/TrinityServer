<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 8/8/16
 * Time: 9:21 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel as ExcelReader;
use Log;

class Excel {
    public function __construct() {

    }

    public function bulkUploadCategories($request) {
      $file = $_FILES['file'];
      $name = $file['name'];
      file_put_contents($name, file_get_contents($file['tmp_name']));
      $data = [];

      ExcelReader::load($name, function($reader) use (&$data) {
        array_push($data, $reader->get());
      });

      foreach ($data as $row) {
        // Check to see if parent exists
        try {
          $parent = Categories::where('parent_id', 0)->where('name', $row->parent)->firstOrFail();
          try {
            $subParent = Categories::where('parent_id', $parent->id)->where('name', $row->subParent)->firstOrFail();
          } catch (ModelNotFoundException $e) {

          }

        } catch (ModelNotFoundException $e) {
          $parent = $this->insertParent($row->parent);
          $subParent = $this->insertSubParent($row->subParent, $parent->id);
        }

      }

      return response()->json(compact('results', 'data'), 200);
    }

    private function insertParent($name) {
      $category = new Categories();
      $category->name = $name;
      $category->parent_id = 0;
      $category->save();
      return $category;
    }

    private function insertSubParent($name, $parentId) {
      $category = new Categories();
      $category->name = $name;
      $category->parent_id = $parentId;
      $category->save();
      return $category;
    }
}