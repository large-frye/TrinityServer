<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/15/16
 * Time: 1:10 PM
 */

namespace App\Http\Controllers;

use App\Models\Count;
use App\Models\Field;
use App\Models\Report;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Input;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Workorder;
use App\Models\Invoice;
use DB;

class Reports extends BaseController {

    var $reportModel;
    var $countModel;
    var $thirtyDays;
    var $sixtyDays;
    var $ninetyDays;
    var $invoice;
    var $workorder;

    const NEW_INSPECTION = 1;
    const CALLED_PH = 2;
    const ALERT = 3;
    const SCHEDULED = 4;
    const SENT = 5;
    const INVOICED = 6;
    const NEW_PICKUP = 7;
    const RESCHEDULE = 8;
    const IN_PROCESS = 9;
    const ON_HOLD = 10;
    const INSPECTION_COMPLETED = 11;
    const PRE_INVOICE = 12;
    const INSPECTOR_ATTENTION_REQUIRED = 13;
    const OFFICE_ATTENTION_REQUIRED = 14;
    const CLOSED = 15;
    const CANCELLED = 16;
    const CLOSED_CANCELLED = 17;
    const INSPECTED = 18;

    public function __construct() {
        $this->workorder = new Workorder();
        $this->reportModel = new Report();
        $this->countModel = new Count();
        $this->invoice = new Invoice();
        $this->thirtyDays = new \DateTime('- 30 days');
        $this->sixtyDays = new \DateTime('- 60 days');
        $this->ninetyDays = new \DateTime('- 90 days');
    }

    public function generate($id) {
        $meta = $this->reportModel->getMetaData($id);
        $data = $this->reportModel->getInspection($id);
        $html = view('basic-report', ['meta' => $meta, 'inspection' => $data[0]]);
        return $this->reportModel->generate($html);
    }

    public function get() {
        $reports = [];
        $fields = [];

        try {
            $reports = $this->getBaseQuery(false, false)
                ->get();
            $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                'Inspector', 'Date of Inspection', 'Date Created');
            $fields = $this->createAssociateFieldArray($stringFields, $fields);
            $name = array(ucfirst(str_replace('-', ' ', 'All')));

            return response()->json(compact('reports', 'fields', 'name'));

        } catch (Exception $e) {
            return response()->json(compact('e'), 500);
        }
    }

    public function getInspectorReports($id, $status, $inspectionType = false) {
        $reports = [];
        $fields = [];
        $header = null;

        try {
            switch ($status) {
                case 'all':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'All Inspections';

                    break;
                case 'new-pickups':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::NEW_PICKUP)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Inspector', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Date of Inspection',
                        'Inspection Outcome', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'New Pickups';

                    break;

                case 'insp-input-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::INSPECTOR_ATTENTION_REQUIRED)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspector Input Required Inspections';

                    break;

                case 'today':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->today, $this->countModel->tomorrow])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Today\'s Inspections';
                    break;

                case 'tomorrow':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->tomorrow, $this->countModel->nextTwoDays])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Tomorrow\'s Inspections';
                    break;

                case 'yesterday':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->yesterday, $this->countModel->today])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Yesterday\'s Inspections';
                    break;

                case 'this-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisWeek, $this->countModel->nextWeek])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Week\'s Inspections';
                    break;

                case 'last-week':
                    $lastWeek = date('Y-m-d 23:59:59', strtotime('last sunday'));
                    $date = new \DateTime($lastWeek);
                    $date->modify('-7 day');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastWeek, $this->countModel->nextWeek])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Week\'s Inspections';
                    break;

                case 'next-week':
                    $nextWeek = date('Y-m-d 23:59:59', strtotime('next sunday'));
                    $date = new \DateTime($nextWeek);
                    $date->modify('+7 day');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextWeek, $this->countModel->twoNextWeek])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Week\'s Inspections';
                    break;

                case 'this-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisMonth, $this->countModel->nextMonth])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Month\'s Inspections';
                    break;

                case 'last-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastMonth, $this->countModel->nextMonth])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Month\'s Inspections';
                    break;

                case 'next-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextMonth, $this->countModel->lastDayOfNextMonth])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Month\'s Inspections';
                    break;
            }

            $name = array(ucfirst(str_replace('-', ' ', $status)));
            return response()->json(compact('reports', 'fields', 'name', 'header'));

        } catch (QueryException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * @param $status
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function byStatus($status, $inspectionType) {

        $reports = [];
        $fields = [];
        $header = null;

        try {

            switch ($status) {
                case 'open':

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereNotIn('work_order.status_id', [Reports::CLOSED, Reports::CLOSED_CANCELLED])
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Open Inspections';

                    break;

                case 'inspector-attention-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::INSPECTOR_ATTENTION_REQUIRED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection',  'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Requiring Inspector\'s Attention';
                    break;

                case 'office-attention-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::OFFICE_ATTENTION_REQUIRED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspector', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Requiring Office Attention';
                    break;

                case 'new-pickups':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::NEW_PICKUP)
                        ->get();
                    $stringFields = array('Customer ID', 'Inspector', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'New Pickups From Inspectors';
                    break;
                case 'new':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::NEW_INSPECTION)
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'New Inspections';
                    break;

                case 'process-reschedule':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereIn('status_id', [Reports::IN_PROCESS, Reports::RESCHEDULE])
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'Date of Last Contact', 'City', 'State', 'Adjuster',
                        'Date of Inspection', 'Inspection Type', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Need To Be Scheduled';
                    break;

                case 'on-hold':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::ON_HOLD)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are On Hold';
                    break;
                case 'scheduled':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::SCHEDULED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'Date of Last Contact', 'City', 'State',
                        'Adjuster', 'Date of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Scheduled Inspections';
                    break;
                case 'post-inspection-date':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::SCHEDULED)
                        ->where('date_of_inspection', '<', date('Y-m-d h:i:s'))
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are Past Their Inspection Date';
                    break;
                case 'inspected':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::INSPECTED)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspected';
                    break;
                case 'pre-invoice':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::PRE_INVOICE)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspection Outcome', 'Date Invoiced',
                        'Adjuster', 'Insurance Company', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Need one';
                    break;
                case 'invoiced':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::INVOICED)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim #', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Have Been Invoiced & Are Waiting For Payment';
                    break;
                case 'invoiced-past-30-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->whereBetween('invoice.date', [strtotime($this->sixtyDays->format('Y-m-d h:i:s')),
                            strtotime($this->thirtyDays->format('Y-m-d h:i:s'))])
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Waiting For Payment Longer Than 30 Days';
                    break;
                case 'invoiced-past-60-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->whereBetween('invoice.date', [strtotime($this->ninetyDays->format('Y-m-d h:i:s')),
                            strtotime($this->sixtyDays->format('Y-m-d h:i:s'))])
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Waiting For Payment Longer Than 60 Days';
                    break;
                case 'invoiced-past-90-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->where('invoice.date', '<', strtotime($this->ninetyDays->format('Y-m-d h:i:s')))
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $header = 'Inspections Waiting For Payment Longer Than 90 Days';
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'closed':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CLOSED)
                        ->get();
                    $stringFields = array('Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Date Pymt Received', 'Date Created');
                    $header = 'Closed Inspections';
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'cancelled':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CANCELLED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $header = 'Cancelled Inspections';
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'cancelled-closed':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CLOSED_CANCELLED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Closed Inspections (Cancelled)';
                    break;
                case 'today':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->today, $this->countModel->tomorrow])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Today\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'tomorrow':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->tomorrow, $this->countModel->nextTwoDays])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Tomorrow\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'yesterday':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->yesterday, $this->countModel->today])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Yesterday\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisWeek, $this->countModel->nextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'last-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastWeek, $this->countModel->nextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'next-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextWeek, $this->countModel->twoNextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisMonth, $this->countModel->nextMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'last-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastMonth, $this->countModel->thisMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'next-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextMonth, $this->countModel->lastDayOfNextMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-year':
                    $reports = $this->getBaseQuery('outcome_type', $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->year, $this->countModel->nextYear])
                        ->groupBy('work_order.id')
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Year\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;
                
                case 'last-year':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastYear, $this->countModel->year])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Year\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;
                    
            }

            $name = array(ucfirst(str_replace('-', ' ', $status)));
            return response()->json(compact('reports', 'fields', 'name', 'header'));

        } catch (QueryException $e) {
            return response()->json(compact('e'));
        }
    }

    private function createAssociateFieldArray($stringFields, $fields) {
        foreach ($stringFields as $field) {
            array_push($fields, array('header' => $field, 'key' => strtolower(str_replace(' ', '_', $field))));
        }

        return $fields;
    }

    private function getInspectionType($type) {
        switch (strtolower($type)) {
            case 'basic':
                return 0;
            case 'ladderassist':
                return 5;
            case 'expert':
                return 1;
        }
        return false;
    }

    private function getInspectionStr($type) {
        switch (strtolower($type)) {
            case 'basic':
            case 'expert':
                return ucfirst($type);
            case 'ladderassist':
                return 'Ladder Assist';
        }
        return false;
    }


    /**
     * @param bool $metaKey
     * @return mixed
     */
    private function getBaseQuery($metaKey = false, $inspectionType) {
        $select = array('work_order.id as customer_id',
            DB::raw('CONCAT(work_order.first_name, " ", work_order.last_name) as insured'), 'u.name as adjuster',
            'p.insurance_company', 'work_order.state', 'inspection_types.name as inspection_type',
            DB::raw('DATE_FORMAT(FROM_UNIXTIME(date_of_inspection / 1000), "%Y-%m-%d") as date_of_inspection'),
            DB::raw('DATE_FORMAT(FROM_UNIXTIME(date_of_inspection / 1000), "%h:%m:%s") as time_of_inspection'),
            'work_order.created_at as date_created', 'work_order.city',
            'u2.name as inspector');

        $query = DB::table('work_order');
        $query->select($select)
            ->leftJoin('user as u', 'work_order.adjuster_id', '=', 'u.id')
            ->leftJoin('user as u2', 'work_order.inspector_id', '=', 'u2.id')
            ->leftJoin('user_profiles as p', 'u.id', '=', 'p.user_id')
            ->leftJoin('inspection_types', 'work_order.inspection_type', '=', 'inspection_types.id');

        // Delimit by inspection type
        if ($inspectionType && $inspectionType != 'all') {
            $inspectionType = $this->getInspectionType($inspectionType);
            $query->where('work_order.inspection_type', $inspectionType);
        }

        return $query;
    }

}