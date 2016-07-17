<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/7/16
 * Time: 8:38 PM
 */

namespace App\Models;

use App\Http\Controllers\Reports;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\Exception;

class WorkorderNotes extends Model {
    protected $table = 'workorder_notes';
    protected $fillable = ['id', 'created_at', 'updated_at', 'text', 'user_id', 'workorder_id'];

    protected $alerts = array(
        Reports::ALERT_TO_INSPECTOR => 'ALERT TO INSPECTOR: ',
        Reports::ALERT_ADMIN, 'ALERT ADMIN: ',
        Reports::ALERT_OFFICE, 'ALERT OFFICE: ',
        'alert_from_inspector', 'ALERT FROM INSPECTOR: ' // TODO: make constant
    );

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

    public function deleteNotes($request) {
        try {
            $notes = $request->notes;
            $noteIds = [];
            $workorderId = null;

            foreach ($notes as $note) {
                array_push($noteIds, $note['id']);
                if ($workorderId == null)
                    $workorderId = $note['workorder_id'];
            }

            $dbNotes = WorkorderNotes::whereIn('id', $noteIds)->get();

            foreach ($dbNotes as $dbNote) {
                $dbNote->delete();
            }

            $notes = WorkorderNotes::where('workorder_id', $workorderId)->get();
            return response()->json(compact('notes'), 200);
        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function saveAlertNote($request) {

        try {
            $alerts = $request->alerts;
            $workorderId = $request->workorderId;
            $workorder = Workorder::find($workorderId);

            // Need to set the workorder alert for this $request
            foreach ($alerts as $key => $bool) {
                $workorder[$key] = $bool;
            }

            $workorder->save();

            // Add note text to WorkorderNotes
            $note = New WorkorderNotes();
            $note->text = 'ALERT: ' . $request->text;
            $note->username = $request->username;
            $note->workorder_id = $workorderId;
            $note->save();
            $notes = WorkorderNotes::where('workorder_id', $workorderId)->get();

            return response()->json(compact('workorder', 'notes'), 200);
        } catch (\Exception $e) {
            return response()->json(compact('e'), 500);
        }



    }
}