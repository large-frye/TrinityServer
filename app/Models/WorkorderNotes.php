<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/7/16
 * Time: 8:38 PM
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use League\Flysystem\Exception;

class WorkorderNotes extends Model {
    protected $table = 'workorder_notes';
    protected $fillable = ['id', 'created_at', 'updated_at', 'text', 'user_id', 'workorder_id'];

    public function saveNote($request, $id) {
        try {
            $note = New WorkorderNotes();
            $note->text = $request->text;
            $note->username = $request->username;
            $note->workorder_id = $id;
            $note->save();
            $notes = WorkorderNotes::where('workorder_id', $id)->get();
            return response()->json(compact('notes'), 200);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }

    }
}