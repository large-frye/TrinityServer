<?php

const PREFIX = 'api';

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

$app->get('/generate/{id}', 'Reports@generate');
$app->get('/settings/categories/create-excel', 'Settings@createExcel');

# Login

$app->group(['prefix' => 'api'], function ($app) {

    # Authentication
    $app->post('auth/login', 'App\Http\Controllers\Account@signIn');
    $app->get('auth/logout', 'App\Http\Controllers\Account@signOut');

});

    # Account
    #$app->post('account/user/sign-in', 'Account@sign_in');
    #$app->post('account/user/create', 'Account@create_user');
    #$app->post('account/user/update', 'Account@update_user');


# Protected routes
$app->group(['prefix' => 'api/admin', 'middleware' => array('jwt.auth', 'authorization.admin')], function($app) {
    # Workorders
    $app->get('/workorders/time/{time}/{type}', 'App\Http\Controllers\Workorders@getWorkordersByTime');
    $app->get('/workorders/notes/{id}', 'App\Http\Controllers\WorkorderNotes@getNotes');
    $app->get('/workorders/{start}/{end}', 'App\Http\Controllers\Workorders@getWorkorders');
    $app->get('/workorder/all', 'App\Http\Controllers\Workorders@all');
    $app->get('/workorder/statuses', 'App\Http\Controllers\Workorders@getStatuses');
    $app->get('/workorder/{id}', 'App\Http\Controllers\Workorders@get');

    $app->post('/workorder/log', 'App\Http\Controllers\Workorders@log');
    $app->post('/workorder/save', 'App\Http\Controllers\Workorders@save');
    $app->post('/workorder/update', 'App\Http\Controllers\Workorders@update');
    $app->post('/workorders/notes/delete', 'App\Http\Controllers\WorkorderNotes@deleteNotes');
    $app->post('/workorders/notes/save/{id}', 'App\Http\Controllers\WorkorderNotes@saveNote');

    # Reports
    $app->get('/reports/get', 'App\Http\Controllers\Reports@get');
    $app->get('/reports/export-csv/{type}', 'App\Http\Controllers\Reports@exportToExcel');
    $app->get('/reports/by-status/{status}/{type}', 'App\Http\Controllers\Reports@byStatus');

    # Inspections
    $app->get('/inspections/outcomes', 'App\Http\Controllers\Inspections@getOutcomes');
    $app->get('/inspections/types', 'App\Http\Controllers\Inspections@getInspectionTypes');
    $app->get('/inspections/{id}', 'App\Http\Controllers\Inspections@get');

    # Inspection Form
    $app->get('/form/get', 'App\Http\Controllers\Form@get');
    $app->post('/form/save', 'App\Http\Controllers\Form@save');
    $app->post('/form/upload', 'App\Http\Controllers\Form@uploadForm');

    # Counts (sub of workorders)
    $app->get('/workorders/counts', 'App\Http\Controllers\Workorders@getCounts');
    $app->get('/workorders/getTopCounts', 'App\Http\Controllers\Workorders@getTopCounts');

    # Clients
    $app->get('/users/insured/{id}', 'App\Http\Controllers\Account@getInsuredProfile');

    # User
    $app->post('/users/create', 'App\Http\Controllers\Account@create_user');
    $app->get('/users/inspectors', 'App\Http\Controllers\Account@getInspectors');

    # Billing
    $app->get('/billing/lock/{id}', 'App\Http\Controllers\Invoice@changeInspectorMileLockState');

    # Report Generate
    $app->get('/generate/{id}', 'App\Http\Controllers\Reports@generate');

    # Photos
    $app->get('/photos/zip/{id}', 'App\Http\Controllers\Photo@getZippedFiles');
    $app->get('/photos/sub-categories/{parentId}/{workorderId}', 'App\Http\Controllers\Photo@getSubCategories');
    $app->get('/photos/parent-categories/{id}', 'App\Http\Controllers\Photo@getParentCategories');
    $app->get('/photos/parent/{id}/{parentId}', 'App\Http\Controllers\Photo@getPhotosByParent');
    $app->get('/photos/{workorderId}/{parentId}/{subParentId}/{labelName}', 'App\Http\Controllers\Photo@getLabeledPhotos');
    $app->get('/photos/{id}', 'App\Http\Controllers\Photo@getPhotos');


    $app->post('/photos/resize', 'App\Http\Controllers\Photo@resizePhotos');
    $app->post('/photos/save', 'App\Http\Controllers\Photo@savePhotos');
    $app->post('/photos/rotate', 'App\Http\Controllers\Photo@rotatePhotos');
    $app->post('/photos/{id}', 'App\Http\Controllers\Photo@uploadPhotos');
    $app->post('/photos/delete/{workorderId}', 'App\Http\Controllers\Photo@deletePhotos');

    # Settings
    $app->post('/settings/categories/save', 'App\Http\Controllers\Settings@saveCategories');
    $app->post('/settings/categories/save-category', 'App\Http\Controllers\Settings@saveCategory');
    $app->get('/settings/categories/parents', 'App\Http\Controllers\Settings@getParents');
    $app->get('/settings/categories/delete/{id}', 'App\Http\Controllers\Settings@deleteCategory');

    # Logger
    $app->get('/logger/{id}', 'App\Http\Controllers\Logger@getWorkorderLog');

    # Resources
    $app->get('/resources/delete/{id}', 'App\Http\Controllers\Resources@deleteResource');
    $app->post('/resources/save', 'App\Http\Controllers\Resources@saveResource');
    $app->post('/resources/upload', 'App\Http\Controllers\Resources@uploadResource');
});

# Inspector accounts
$app->group(['prefix' => 'api/inspector', 'middleware' => array('jwt.auth', 'authorization.inspector')], function($app) {
    $app->get('/workorders/{id}', 'App\Http\Controllers\Inspector@getWorkorders');
    $app->post('/workorder/save', 'App\Http\Controllers\Workorders@save');
    $app->get('/reports/{status}/{id}', 'App\Http\Controllers\Inspector@getReports');
    $app->get('/inspections/{id}/{userId}', 'App\Http\Controllers\Workorders@getByInspector');
});


# Shared
$app->group(['prefix' => 'api/shared', 'middleware' => array('jwt.auth')], function($app) {
    $app->get('/users/inspectors', 'App\Http\Controllers\Account@getInspectors');
    $app->get('/users/{type}', 'App\Http\Controllers\Account@getAdjusters');

    # Billing
    $app->get('/billing/mileage/{id}/{week}', 'App\Http\Controllers\Invoice@getWeeklyInspectorMileage');
    $app->get('/billing/check-lock/{id}', 'App\Http\Controllers\Invoice@checkInspectorLock');
    $app->get('/billing/{start}/{end}', 'App\Http\Controllers\Invoice@getInvoicesByRange');
    $app->get('/billing/{start}/{end}/{id}', 'App\Http\Controllers\Invoice@getInvoicesByInspector');

    # Resources
    $app->post('/resources', 'App\Http\Controllers\Resources@getResources');

    $app->get('/billing/weeks', 'App\Http\Controllers\Invoice@getInvoiceWeeks');
    $app->post('/billing/mileage/save', 'App\Http\Controllers\Invoice@saveMileage');
});
