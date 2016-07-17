<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 7/16/16
 * Time: 1:51 AM
 */

namespace App\Models;

use App\Util\Shared;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class WorkorderFile extends Model {
    protected $table = 'workorder_files';
    protected $fillable = ['workorder_id', 'file_url', 'id', 'file_type', 'created_at', 'updated_at'];

    public static function uploadFiles($request) {
        try {
            $workorderId = $request->workorderId;
            $user = $request->username;
            $type = $request->uploadType;

            $shared = new Shared();
            $path = 'inspections/' . $workorderId . '/files';
            $spl = new \SplFileInfo($_FILES['file_0']['name']);
            $fileExt = $spl->getExtension();
            $fileName = $request->files3Name . '.' . $fileExt;
            $displayName = $type . ' - ' . $_FILES['file_0']['name'];

            // upload
            $urls = $shared->upload($_FILES, $request, $path, $fileName);

            // add file info to db
            $workorderFile = new WorkorderFile();
            $workorderFile->workorder_id = $workorderId;
            $workorderFile->file_url = $urls['file_0'];
            $workorderFile->file_type = $type;
            $workorderFile->display_name = $displayName;
            $workorderFile->update_by = $user;
            $workorderFile->save();

            $workorderFiles = WorkorderFile::where('workorder_id', $workorderId)->get();
            return response()->json(compact('workorderFiles'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }
}