<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->welcome();
});

# Login
$app->post('auth/login', 'Account@signIn');
$app->get('auth/logout', 'Account@signOut');

# Account
#$app->post('account/user/sign-in', 'Account@sign_in');
#$app->post('account/user/create', 'Account@create_user');
#$app->post('account/user/update', 'Account@update_user');


# Protected routes
$app->group(['prefix' => 'admin', 'middleware' => array('jwt.auth', 'authorization.admin')], function($app) {
    # Workorders
    $app->get('/workorders/time/{time}/{type}', 'App\Http\Controllers\Workorders@getWorkordersByTime');
    $app->get('/workorders/{start}/{end}', 'App\Http\Controllers\Workorders@getWorkorders');
    $app->get('/workorder/all', 'App\Http\Controllers\Workorders@all');
    $app->get('/workorder/statuses', 'App\Http\Controllers\Workorders@getStatuses');
    $app->get('/workorder/{id}', 'App\Http\Controllers\Workorders@get');

    $app->post('/workorder/save', 'App\Http\Controllers\Workorders@save');
    $app->post('/workorder/update', 'App\Http\Controllers\Workorders@update');

    # Reports
    $app->get('/reports/get', 'App\Http\Controllers\Reports@get');
    $app->get('/reports/by-status/{status}', 'App\Http\Controllers\Reports@byStatus');

    # Inspections
    $app->get('/inspections/outcomes', 'App\Http\Controllers\Inspections@getOutcomes');
    $app->get('/inspections/{id}', 'App\Http\Controllers\Inspections@get');

    # Inspection Form
    $app->get('/form/get', 'App\Http\Controllers\Form@get');
    $app->post('/form/save', 'App\Http\Controllers\Form@save');
    $app->post('/form/upload', 'App\Http\Controllers\Form@uploadForm');

    # Counts (sub of workorders)
    $app->get('/workorders/counts', 'App\Http\Controllers\Workorders@getCounts');

    # Clients
    $app->get('/users/inspectors', 'App\Http\Controllers\Account@getInspectors');
    $app->get('/users/{type}', 'App\Http\Controllers\Account@getAdjusters');
    $app->get('/users/insured/{id}', 'App\Http\Controllers\Account@getInsuredProfile');

    # User
    $app->post('/users/create', 'App\Http\Controllers\Account@create_user');

});

# Inspector accounts
$app->group(['prefix' => 'inspector', 'middleware' => array('jwt.auth', 'authorization.inspector')], function($app) {
    $app->get('/workorders/{id}', 'App\Http\Controllers\Inspector@getWorkorders');
    $app->get('/reports/{status}/{id}', 'App\Http\Controllers\Inspector@getReports');
});

