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
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', 'http://52.2.169.5:8000');
        $response->header('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}