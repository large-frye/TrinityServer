<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 6/26/16
 * Time: 5:08 PM
 */

namespace App\Models;

use Illuminate\Http\Request;
use League\Flysystem\Exception;


class Logger
{
    const LOG_DELIMITER = ";";
    const LOG_KEY_DELIMITER = ",";
    const FILE = 'file';
    const TEXT = 'text';

    /**
     * Log when a change has happened to a work order.
     * @param $data
     * @param $type
     * @return Log
     * @throws Exception
     */
    public static function log($data) {
        $changedItems = [];
        $adjuster = [];
        $inspector = [];
        $type = Logger::TEXT;
        $attachmentLocation = "";

        try {
            $workorder = Workorder::find($data->id);
            $log = new Log();
            $log->updated_by = $data->updated_by;
            $log->workorder_id = $data->id;


            if (get_class($data) == 'Illuminate\Http\Request') {
                if ($data->has('type') && $data->type == Logger::FILE) {
                    $type = Logger::FILE;
                }

                if ($data->has('attachment_location')) {
                    $attachmentLocation = $data->attachment_location;
                }

                $log->message = "added file";
            } else {
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

                if (isset($data['adjuster']))
                    array_push($changedItems, Logger::findChangedItems($data['adjuster'], $adjuster));

                if (isset($data['inspector']))
                    array_push($changedItems, Logger::findChangedItems($data['inspector'], $inspector));

                $log->message = Logger::createLogMessage($changedItems);
                $log->fields = Logger::getFields($changedItems);
            }

            $log->type = $type;
            $log->attachment_location = $attachmentLocation;
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
                $msg .= "<strong>"  . $changeChild['key'] . "</strong>" . " was changed. <br/> <strong>To:</strong> " . $changeChild['new'] . "<br/>
                 <strong>From:</strong> " . $changeChild['old']
                    . Logger::LOG_DELIMITER;
            }
        }
        return substr($msg, 0, strlen($msg) - 1);
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
                if (preg_match('/date/', $item) == 1) {
                    $oldDate = date('Y-m-d h:i A', $value / 1000);
                    $newDate = date('Y-m-d h:i A', $needle[$item] / 1000);
                    array_push($items, array('old' => $oldDate, 'new' => $newDate, 'key' => $item));
                } else {
                    array_push($items, array('old' => $value, 'new' => $needle[$item], 'key' => $item));
                }

            }
        }
        return $items;
    }

}