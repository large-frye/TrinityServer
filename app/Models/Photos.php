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
            $categorizedPhotos = $this->categorizePhotos($photos);

            return response()->json(compact('categorizedPhotos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        } catch (Exception $e) {
            return response()->json(compact('e'), 200);
        }
    }

    /**
     * @param $id
     */
    public function uploadPhoto($request, $id) {
        $shared = new Shared();
        $path = 'inspections/' . $id . '/photos';
        $urls = $shared->upload($_FILES, $request, $path);

        try {
            foreach ($_FILES as $key => $value) {
                $file = $_FILES[$key];

                // Add photo
                $photo = new Photos(array(
                    'file_name' => $file['name'],
                    'workorder_id' => $id,
                    'label' => $file['name'], // By default make it the file_name. The user can override this later.
                    'parent_id' => null,
                    'sub_parent_id' => null,
                    'file_url' => $urls[$key],
                    'id' => null
                ));
                $photo->save();
            }

            $photos = Photos::where('workorder_id', $id)->get();
            $categorizedPhotos = $this->categorizePhotos($photos);

            return response()->json(compact('categorizedPhotos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 200);
        }
    }

    /**
     * Reorganize photos as in {parentId} => array(photos)
     * @param $photos
     * @return array
     */
    private function categorizePhotos($photos) {
        $categorizedPhotos = [];

        foreach ($photos as $photo) {
            if (isset($photo->sub_parent_id)) {
                if (!isset($categorizedPhotos[$photo->parent_id][$photo->sub_parent_id])) {
                    $categorizedPhotos[$photo->parent_id][$photo->sub_parent_id] = array($photo);
                } else {
                    array_push($categorizedPhotos[$photo->parent_id][$photo->sub_parent_id], $photo);
                }
            } else if (isset($photo->parent_id)) {
                if (!isset($categorizedPhotos[$photo->parent_id]['no_sub_parent'])) {
                    $categorizedPhotos[$photo->parent_id]['no_sub_parent'] = array($photo);
                } else {
                    array_push($categorizedPhotos[$photo->parent_id]['no_sub_parent'], $photo);
                }
            } else {
                if (!isset($categorizedPhotos['no_parent'])) {
                    $categorizedPhotos['no_parent'] = array($photo);
                } else {
                    array_push($categorizedPhotos['no_parent'], $photo);
                }
            }

        }

        return $categorizedPhotos;
    }
}