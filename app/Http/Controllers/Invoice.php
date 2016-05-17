<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/11/16
 * Time: 10:46 PM
 */

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models;

class Invoice extends BaseController {

    private $invoiceModel;

    function __construct() {
        $this->invoiceModel = new Models\Invoice();
    }

    
    /**
     * @param $id
     */
    public function getInvoice($id) {
        return $this->invoiceModel->getSimpleWorkorder($id);
    }

    /**
     * @return array
     */
    public function getInvoiceWeeks() {
        return $this->invoiceModel->getWorkweeks();
    }

    public function getInvoicesByRange($start, $end) {
        return $this->invoiceModel->getInspectionsByRange($start, $end);
    }

    public function getInvoicesByInspector($start, $end, $id) {
        return $this->invoiceModel->getInspectionsByInspector($start, $end, $id);
    }
}