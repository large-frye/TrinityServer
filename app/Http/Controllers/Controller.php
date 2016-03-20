<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\WorkOrders;
use App\Models\Counts;
use DateTime;

class Controller extends BaseController
{

    public function __construct()
    {
        $this->counts = new Counts();
    }

    /**
     * @param $id
     */
    public function getWorkOrders($id) {
        // return response()->json(
    }
}
