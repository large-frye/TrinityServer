<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/11/16
 * Time: 5:44 PM
 */

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}