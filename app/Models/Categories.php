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
    public $fillable = ['id', 'parent_id', 'name', 'slug'];

    public function saveCategories($request) {
        // Category 1
        $categories = $request->categories;

        try {
            foreach ($categories as $category) {
                $cat = Categories::find($category['id']);
                $cat->name = $category['name'];
                $cat->save();
            }

            try {
                if ($request->has('subCategories')) {
                    $subCategories = $request->subCategories;
                    foreach ($subCategories as $category1) {
                        foreach ($category1 as $category) {
                            $cat = Categories::find($category['id']);
                            $cat->name = $category['name'];
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
}