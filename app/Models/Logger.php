<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/26/16
 * Time: 5:08 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\ModelNotFoundException;
use League\Flysystem\Exception;

class Logger
{

    const LOG_DELIMITER = ";";
    const LOG_KEY_DELIMITER = ",";

    /**
     * Log workorder changes
     * @param $data
     * @return Log
     */
    public static function log($data) {
        $changedItems = [];
        $adjuster = [];
        $inspector = [];

        // Check to see if the workorder exists;
        try {
            $workorder = Workorder::find($data->id);
            if (isset($data->adjuster)) {
                $workorder->adjuster;
                $workorder->inspector;
                $adjuster = $workorder['relations']['adjuster']['attributes'];
                $inspector = $workorder['relations']['inspector']['attributes'];
            }

            $order = $workorder['attributes'];
            $data = get_object_vars($data);

            // Get changed items
            array_push($changedItems, Logger::findChangedItems($data, $order));
            array_push($changedItems, Logger::findChangedItems($data['adjuster'], $adjuster));
            array_push($changedItems, Logger::findChangedItems($data['inspector'], $inspector));

            $log = new Log();
            $log->workorder_id = $data['id'];
            $log->message = Logger::createLogMessage($changedItems);
            $log->updated_by = $data['updated_by'];
            $log->fields = Logger::getFields($changedItems);
            $log->save();

            return $log;

        } catch (Exception $e) {
            throw new Exception('Issue logging workorder change. Ref: ' . $e->getMessage());
        }
    }

    /**
     * Create a log message, delimited by LOG_DELIMITER
     * @param $changedItems
     * @return string
     */
    private static function createLogMessage($changedItems) {
        $msg = "";
        foreach ($changedItems as $changedParent) {
            foreach ($changedParent as $changeChild) {
                $msg .= $changeChild['key'] . " was changed to " . $changeChild['new'] . " from " . $changeChild['old']
                    . Logger::LOG_DELIMITER;
            }
        }
        return $msg;
    }

    /**
     * Get the field names that were change and create a string delimited by LOG_KEY_DELIMITER
     * @param $changeItems
     * @return string
     */
    private static function getFields($changeItems) {
        $fields = "";
        foreach ($changeItems as $changedParent) {
            foreach ($changedParent as $changeChild) {
                $fields .= $changeChild['key'] . Logger::LOG_KEY_DELIMITER;
            }
        }
        return substr($fields, 0, strlen($fields) - 1);
    }

    /**
     * Find changed items
     * @param $needle
     * @param $haystack
     * @return array
     */
    private static function findChangedItems($needle, $haystack) {
        $items = [];
        foreach ($haystack as $item => $value) {
            if (isset($needle[$item]) && $needle[$item] != $value) {
                array_push($items, array('old' => $value, 'new' => $needle[$item], 'key' => $item));
            }
        }
        return $items;
    }

}