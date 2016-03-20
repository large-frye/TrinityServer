<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/24/16
 * Time: 12:43 AM
 */

namespace App\Http\Middleware;

use Closure;
use App\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;

class Admin
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->session()->get('role') == User::ADMIN) {
            return $response;
        }

        return response()->json(['error' => 'Not authorized'], 401);
    }
}