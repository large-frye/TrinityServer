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
use App\Util\Shared;

class Excel {
    public function __construct() {

    }

    public function generateExcelCategories($request) {
      $data = $this->getCategoryExcelData();
      ExcelReader::create('categories', function ($excel) use (&$data) {
        $excel->sheet('categories', function ($sheet) use (&$data) {
          $sheet->fromArray($data);
        });
      })->store('csv');

      return response()->json(array('fileLocation' => Shared::getExportPath($request->url(), 'categories.csv')), 200);
    }

    public function bulkUploadCategories($request) {
      $file = $_FILES['file'];
      $name = $file['name'];
      $data = [];

      ExcelReader::load($file['tmp_name'], function($reader) use (&$data) {
        array_push($data, $reader->get());
      });

      // find any content that doesn't exist and add.
      foreach ($data[0] as $row) {
        try {
          $parent = Categories::where('parent_id', 0)->where('name', $row->parent)->firstOrFail();
          try {
            $subParent = Categories::where('parent_id', $parent->id)->where('name', $row->subparent)->firstOrFail();
            try {
              $labels = Categories::where('parent_id', $subParent->id)->get();
              $this->consolidateLabels($labels, $row->labels, $subParent->id);
            } catch (ModelNotFoundException $e) {
              $this->insertLabels($row->labels, $subParent->id);
            }
          } catch (ModelNotFoundException $e) {
            // sub parent does not exist
            $subParent = $this->insertSubParent($row->subparent, $parent->id);
            $this->insertLabels($row->labels, $subParent->id);
          }
        } catch (ModelNotFoundException $e) {
          // parent and subparent do not exist
          $parent = $this->insertParent($row->parent);
          $subParent = $this->insertSubParent($row->subparent, $parent->id);
          $this->insertLabels($row->labels, $subParent->id);
        }
      }

      return response()->json(compact('data'), 200);
    }

    private function getCategoryExcelData() {
      $data = [];
      $parents = Categories::where('parent_id', 0)->get();

      foreach ($parents as $parent) {
        $parentName = $parent->name;
        $subParents = Categories::where('parent_id', $parent->id)->get();
        if ($subParents == null || count($subParents) == 0) {
          array_push($data, array('parent' => $parentName, 'subparent' => '', 'labels' => ''));
        }
        foreach ($subParents as $subParent) {
          $tmpLabels = "";
          $labelCount = 0;
          $subParentName = $subParent->name;
          $labels = Categories::where('parent_id', $subParent->id)->get();
          if ($labels == null || count($labels) == 0) {
            array_push($data, array('parent' => $parentName, 'subparent' => $subParentName, 'labels' => ''));
          }
          foreach ($labels as $label) {
            if ($labelCount + 1 == count($labels)) {
              $tmpLabels .= $label->name;
              array_push($data, array('parent' => $parentName, 'subparent' => $subParentName, 'labels' => $tmpLabels));
            }
            $tmpLabels .= $label->name . ",";
            $labelCount++;
          }
        }
      }

      return $data;
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

    private function insertLabels($labels, $subParentId) {
      $explodedLabels = $labels;
      if (gettype($labels) == "string")
        $explodedLabels = explode(",", $labels);

      if ($labels == "")
        return;

      foreach($explodedLabels as $explodedLabel) {
        $category = new Categories();
        $category->name = $explodedLabel;
        $category->parent_id = $subParentId;
        $category->save();
      }
    }

    private function consolidateLabels($labels, $rowLabels, $subParentId) {
      $explodedLabels = explode(",", $rowLabels);
      foreach($labels as $label) {
        if (in_array($label['name'], $explodedLabels)) {
          $search = array_search($label['name'], $explodedLabels);
          unset($explodedLabels[$search]);
        }
      }

      $this->insertLabels($explodedLabels, $subParentId);
    }
}