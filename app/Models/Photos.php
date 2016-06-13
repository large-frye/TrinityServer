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
use Illuminate\Support\Facades\Log;

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
            $categorizedPhotos = $this->categorizePhotosv2($photos);

            return response()->json(compact('categorizedPhotos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        } catch (Exception $e) {
            return response()->json(compact('e'), 200);
        }
    }

    /**
     * Get labeled photos, filtered by $workorderId & $labelId.
     * @param $workorderId
     * @param $labelId
     */
    public function getLabeledPhotos($workorderId, $parentId, $subParentId, $labelName) {
        try {
            $label = Categories::where('name', $labelName)->first();
            $photos = Photos::where('workorder_id', $workorderId)
                ->where('parent_id', $parentId)
                ->where('sub_parent_id', $subParentId)
                ->where('label', $label->name)
                ->get();
            return response()->json(compact('photos'), 200);
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
        $urls = $shared->upload($_FILES, $request, $path);

        // store all the photo names
        $names = [];

        try {
            foreach ($_FILES as $key => $value) {
                $file = $_FILES[$key];

                array_push($names, $file['name']);

                // Add photo
                $photo = new Photos(array(
                    'file_name' => $file['name'],
                    'workorder_id' => $id,
                    'label' => '',
                    'parent_id' => null,
                    'sub_parent_id' => null,
                    'file_url' => $urls[$key],
                    'id' => null
                ));
                $photo->save();
            }

            $photos = Photos::where('workorder_id', $id)->get();
            $categorizedPhotos = $this->categorizePhotosv2($photos, $names);

            return response()->json(compact('categorizedPhotos'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        } catch (QueryException $e) {
            return response()->json(compact('e'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 200);
        }
    }

    public function savePhotos($request) {
        try {
            foreach($request->photos as $photo) {
                if (!is_null($photo)) {
                    if (!isset($photo['id'])) {
                        foreach ($photo as $p) { $this->updatePhoto($p); }
                    } else {
                        $this->updatePhoto($photo);
                    }
                }

            }
            return response()->json(array('photos' => $request->photos), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        }
    }

    protected function updatePhoto($photo) {
        $p = Photos::find($photo['id']);
        $p->label = $photo['label'];
        $p->parent_id = $photo['parent_id'];
        $p->sub_parent_id = $photo['sub_parent_id'];
        $p->display_order = isset($photo['display_order']) ? $photo['display_order'] : null;
        $p->save();
    }

    public function deletePhotos($request) {
        try {
            if ($request->has('photos')) {
                $photos = $request->photos;
                foreach ($photos as $photo) {
                    $photoRef = Photos::find($photo['id']);
                    $key = 'inspections/' . $photoRef['workorder_id'] . '/photos/' . $photo['file_name'];
                    Shared::remove($key);
                    $photoRef->delete();
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Reorganize photos as in {parentId} => array(photos)
     * @param $photos
     * @param $names
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

    private function categorizePhotosv2($photos, $names = []) {
        $categorizedPhotos = [];

        foreach ($photos as $photo) {
            if (!isset($photo->sub_parent_id) || !isset($photo->parent_id)) {

                if (!isset($categorizedPhotos['notCategorized'])) {
                    $categorizedPhotos['notCategorized'] = array($photo);
                } else {
                    array_push($categorizedPhotos['notCategorized'], $photo);
                }
            }

            if (in_array($photo->file_name, $names)) {
                $photo->recently_upload = true;
            }
        }

        $categorizedPhotos['all'] = $photos;

        return $categorizedPhotos;
    }
}