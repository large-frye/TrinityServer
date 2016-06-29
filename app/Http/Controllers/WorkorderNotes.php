<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/7/16
 * Time: 8:41 PM
 */

namespace App\Http\Controllers;


use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\WorkorderNotes as Notes;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class WorkorderNotes extends BaseController {

    var $notesModel;

    public function __construct() {
        $this->notesModel = new Notes();
    }

    public function getNotes($workorderId) {
        try {
            $notes = Notes::where('workorder_id', $workorderId)->orderBy('created_at', 'desc')->get();
            return response()->json(compact('notes'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(compact('e'), 200);
        }
    }

    public function saveNote(Request $request, $id) {
        return $this->notesModel->saveNote($request, $id);
    }
}