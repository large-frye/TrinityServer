<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 1/11/16
 * Time: 6:17 PM
 */

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Exception\HttpResponseException;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Illuminate\Http\Response as IlluminateResponse;
use App\Models\WorkOrders;
use App\Models\Counts;
use Illuminate\Support\Facades\Mail;
use App\User;

class Account extends BaseController {

    public function __construct() {
        //
        $this->user = new User();
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function get_credentials(Request $request) {
        return $request->only('email', 'password');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function create_user(Request $request) {
        try {
            \App\User::where('email', $request->email)->firstOrFail();
            return response()->json(['error' => 'user exists already'], 500);
        } catch (ModelNotFoundException $e) {

            $user = new \App\User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = password_hash($request->password, PASSWORD_BCRYPT);

            if ($user->save()) {
                $data = array('name' => $request->name);

                Mail::send('createuser', $data, function($msg) use ($request) {
                    $msg->to([$request->email]);
                    $msg->subject('New Account on trinity.is');
                });

                return response()->json(compact('user'));
            } else {
                return response()->json(['error' => 'could not save user'], 500);
            }
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function signIn(Request $request) {

        try {
            $this->validate($request, [
                'email' => 'required|email|max:255', 'password' => 'required'
            ]);

        } catch (HttpResponseException $e) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid auth',
                    'status_code' => IlluminateResponse::HTTP_BAD_REQUEST
                ]],
                IlluminateResponse::HTTP_BAD_REQUEST,
                $headers = []
            );
        }

        try {

            // make sure that a user exists already.
            $user = \App\User::where('email', $request->email)->firstOrFail();
            $user->profile;
            $roleId = $user->rolesUser[0]->role_id;

            // Set our session role value now
            $request->session()->put('role', $roleId);
            $request->session()->save();

            // return a value back to admin to let know what route it should follow next
            switch ($roleId) {
                case User::ADMIN:
                    $user->appRole = 'admin';
                    break;
                case User::INSPECTOR:
                    $user->appRole = 'inspector';
                    break;
                case User::CLIENT:
                    $user->appRole = 'client';
                    break;
            }

            $credentials = $this->get_credentials($request);

            try {
                if (! $token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'invalid_credentials'], 401);
                }
            } catch (JWTException $e) {
                return response()->json(['error' => 'could_not_create_token'], 500);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([compact('user'), compact('token')]);
    }

    public function signOut(Request $request)
    {
        // flush our session data
        $request->session()->flush();

        // create a new session id
        $request->session()->regenerate();
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function update_user(Request $request) {

        try {
            $input = \Illuminate\Support\Facades\Input::all();
            \App\User::where('email', $request->email)->firstOrFail();
            unset($input['query_string']);

            if ($input['password']) {
                $input['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            \App\User::where('email', '=', $request->email) ->update($input);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAdjusters($type) {
        return $this->user->findAdjusters($type);
    }

    public function getInspectors() {
        return $this->user->findInspectors(User::INSPECTOR);
    }

    public function getInsuredProfile($id) {
        return $this->user->findInsuredProfile($id);
    }
}