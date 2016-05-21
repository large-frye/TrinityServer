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

    const ENDPOINT = 'http://52.2.169.5:8000';

    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        $url = $request->url();
        $crossOrigin = CorsMiddleware::ENDPOINT;

        preg_match('/api\.trinity\.dev/', $url, $matches);

        if (count($matches) > 0) {
            $crossOrigin = 'http://localhost:8000';
        }

        $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
        $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
        $response->header('Access-Control-Allow-Origin', $crossOrigin);
        $response->header('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}