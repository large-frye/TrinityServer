<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/29/16
 * Time: 8:22 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Input;
use League\Flysystem\Exception;
use App\Util\Shared;

class Photos extends Model
{
    public $table = 'photos';
    public $fillable = ['file_name', 'workorder_id', 'label', 'parent_id', 'sub_parent_id', 'file_url', 'id'];

    /**
     * @param $id
     * @return mixed
     */
    public function getPhotos($id) {
        try {
            $photos = Photos::where('workorder_id', $id)->get();
            $categorizedPhotos = [];

            // Reorganize photos as in {parentId} => array(photos);
            foreach ($photos as $photo) {
                if (!isset($categorizedPhotos[$photo->parent_id])) {
                    $categorizedPhotos[$photo->parent_id] = array($photo);
                } else {
                    array_push($categorizedPhotos[$photo->parent_id], $photo);
                }
            }

            return response()->json(compact('categorizedPhotos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 500);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    /**
     * @param $id
     */
    public function uploadPhoto($request, $id) {
        $shared = new Shared();
        $path = 'inspections/' . $id . '/photos';
        $url = $shared->upload($_FILES, $request, $path);

        try {
            // Add photo
            $photo = new Photos(array(
                'file_name' => $_FILES['file']['name'],
                'workorder_id' => $id,
                'label' => null,
                'parent_id' => null,
                'sub_parent_id' => null,
                'file_url' => $url,
                'id' => null
            ));
            $photo->save();
            $photos = Photos::where('workorder_id', $id)->get();
            return response()->json(compact('photos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 200);
        }
    }
}