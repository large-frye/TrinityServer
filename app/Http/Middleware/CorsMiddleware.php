<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/21/16
 * Time: 11:22 PM
 */

namespace App\Http\Middleware;


class CorsMiddleware
{

    const ENDPOINT = 'http://52.205.216.249:8000';
    public static $crossOrigin = CorsMiddleware::ENDPOINT;
    var $fileBase = 'http://52.205.216.249/exports';
    var $dockerBase = '/usr/share/nginx/html/trinity-server/resources/docker';

    public function handle($request, \Closure $next) {
        $response = $next($request);
        $url = $request->url();

        preg_match('/api\.trinity\.dev/', $url, $matches);

        if (count($matches) > 0) {
            CorsMiddleware::$crossOrigin = 'http://localhost:8000';
            $this->fileBase = 'storage';
            $this->dockerBase = '/php/TrinityServer/resources/docker';
        }

        $request->session()->put('fileBase', $this->fileBase);
        $request->session()->put('dockerBase', $this->dockerBase);
        $request->session()->save();

        $response->header('Access-Control-Allow-Methods', '*');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', CorsMiddleware::$crossOrigin);
        $response->header('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}