<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 8/8/16
 * Time: 9:37 PM
 */

namespace App\Http\Middleware;

use Closure;

class Office {
    const OFFICE = 5;

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->session()->get('role') == Office::OFFICE) {
            return $response;
        }

        return response()->json(['error' => 'Not authorized'], 401);
    }
}