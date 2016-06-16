<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/30/16
 * Time: 10:57 AM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use League\Flysystem\Exception;

class Categories extends Model
{
    public $table = 'categories';
    public $fillable = ['id', 'name'];

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveCategories($request) {
        // Category 1
        $categories = $request->categories;

        try {
            foreach ($categories as $category) {
                $cat = Categories::find($category['id']);
                $cat->name = $category['name'];
                $cat->display_order = $category['display_order'];
                $cat->save();
            }

            try {
                if ($request->has('subCategories')) {
                    $subCategories = $request->subCategories;
                    foreach ($subCategories as $category1) {
                        foreach ($category1 as $category) {
                            $cat = Categories::find($category['id']);
                            $cat->name = $category['name'];
                            $cat->display_order = $category['display_order'];
                            $cat->save();
                        }
                    }   

                    return response()->json(compact('categories', 'subCategories'), 200);
                }
            } catch (Exception $e) {
                echo "Doesn't exist";
            }



            return response()->json(compact('categories'), 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        }
    }

    public function buildCategoryList() {
        $categories = array();

        try {
            $parentCategories = Categories::where('parent_id', 0)->get();
            foreach ($parentCategories as $parentCategory) {
                // Find the children for this $parentCategory
                $childCategories = Categories::where('parent_id', $parentCategory->id)->get();

                // Create an array for parent category
                $parent = $this->createCategoryArray($parentCategory);

                foreach ($childCategories as $childCategory) {
                    // Labels
                    $labelCategories = Categories::where('parent_id', $childCategory->id)->get();

                    $child = $this->createCategoryArray($childCategory);

                    foreach($labelCategories as $labelCategory) {
                        $label = $this->createCategoryArray($labelCategory);

                        if (!isset($child['children'])) {
                            $child['children'] = array($label);
                        } else {
                            array_push($child['children'], $label);
                        }
                    }

                    if (!isset($parent['children'])) {
                        $parent['children'] = array($child);
                    } else {
                        array_push($parent['children'], $child);
                    }
                }

                $categories[$parentCategory->id] = $parent;
            }
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }


        return response()->json(compact('categories'), 200);
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function saveCategory($request) {
        try {
            $category = new Categories();
            $category->name = $request->name;
            if ($request->has('parent_id')) {
                $category->parent_id = $request->parent_id;
            }
            if ($request->has('allowed_to_be_parent')) {
                $category->allowed_to_be_parent = $request->allowed_to_be_parent;
            }
            if ($request->has('display_order')) {
                $category->display_order = $request->display_order;
            }
            $category->save();
            return response()->json(compact('category'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteCategory($id) {
        try {
            // Get category info
            $category = Categories::find($id);

            // Get all children, if there are any
            $children = Categories::where('parent_id', $id)->get();

            // Delete all the children
            foreach ($children as $child) {
                $child->delete();
            }

            // Delete category
            $category->delete();

            return response()->json(array('message' => 'category ' . $category->name . ' deleted'), 200);

        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    protected function createCategoryArray($category) {
        return array(
            'id' => $category->id,
            'name' => $category->name,
            'display_order' => $category->display_order
        );
    }
}