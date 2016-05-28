<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/15/16
 * Time: 1:10 PM
 */

namespace App\Http\Controllers;

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

        try {
            switch ($status) {
                case 'all':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;
                case 'new-pickups':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::NEW_PICKUP)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Inspector', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Date of Inspection',
                        'Inspection Outcome', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;

                case 'insp-input-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::INSPECTOR_ATTENTION_REQUIRED)
                        ->where('inspector_id', $id)
                        ->get();

                    $stringFields = array('Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;

                case 'today':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'tomorrow':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 23:59:59', strtotime('1 day')),
                            date('Y-m-d 00:00:00', strtotime('1 day'))])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'yesterday':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 00:00:00', strtotime('-1 day')),
                            date('Y-m-d 23:59:59', strtotime('-1 day'))])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'this-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 23:59:59', strtotime('last sunday')),
                            date('Y-m-d 00:00:00', strtotime('next sunday'))])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'last-week':
                    $lastWeek = date('Y-m-d 23:59:59', strtotime('last sunday'));
                    $date = new \DateTime($lastWeek);
                    $date->modify('-7 day');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$date->format('Y-m-d h:i:s'),
                            $lastWeek])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'next-week':
                    $nextWeek = date('Y-m-d 23:59:59', strtotime('next sunday'));
                    $date = new \DateTime($nextWeek);
                    $date->modify('+7 day');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$date->format('Y-m-d h:i:s'),
                            $nextWeek])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'this-month':
                    $firstDay = new \DateTime('first day of this month');
                    $lastDay = new \DateTime('last day of this month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'last-month':
                    $firstDay = new \DateTime('first day of last month');
                    $lastDay = new \DateTime('last day of last month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'next-month':
                    $firstDay = new \DateTime('first day of next month');
                    $lastDay = new \DateTime('last day of next month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->where('inspector_id', $id)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;



            }

            $name = array(ucfirst(str_replace('-', ' ', $status)));
            return response()->json(compact('reports', 'fields', 'name'));

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

        try {

            switch ($status) {
                case 'open':

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereNotIn('work_order.status_id', [Reports::CLOSED, Reports::CLOSED_CANCELLED])
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;

                case 'inspector-attention-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::INSPECTOR_ATTENTION_REQUIRED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection',  'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'office-attention-required':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::OFFICE_ATTENTION_REQUIRED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspector', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'new-pickup':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('work_order.status_id', '=', Reports::NEW_PICKUP)
                        ->get();
                    $stringFields = array('Customer ID', 'Inspector', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'new':

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::NEW_INSPECTION)
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;

                case 'process-reschedule':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereIn('status_id', [Reports::IN_PROCESS, Reports::RESCHEDULE])
                        ->get();

                    $stringFields = array('Customer ID', 'Insured', 'Date of Last Contact', 'City', 'State', 'Adjuster',
                        'Date of Inspection', 'Inspection Type', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);

                    break;

                case 'on-hold':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::ON_HOLD)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'scheduled':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::SCHEDULED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'Date of Last Contact', 'City', 'State',
                        'Adjuster', 'Date of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'post-inspection-date':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::SCHEDULED)
                        ->where('date_of_inspection', '<', date('Y-m-d h:i:s'))
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'inspected':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::INSPECTED)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'pre-invoice':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::PRE_INVOICE)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Inspection', 'Inspection Outcome', 'Date Invoiced',
                        'Adjuster', 'Insurance Company', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'invoiced':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::INVOICED)
                        ->get();
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim #', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'invoiced-past-30-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->whereBetween('invoice.date', [$this->sixtyDays->format('Y-m-d h:i:s'), $this->thirtyDays->format('Y-m-d h:i:s')])
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'invoiced-past-60-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->whereBetween('invoice.date', [$this->ninetyDays->format('Y-m-d h:i:s'), $this->sixtyDays->format('Y-m-d h:i:s')])
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'invoiced-past-90-days':
                    $reports = DB::table('work_order')->join('invoice', 'work_order.invoice_id', '=', 'invoice.id')
                        ->where('work_order.status_id', '=', Reports::INVOICED)
                        ->where('invoice.date', '<', $this->ninetyDays->format('Y-m-d h:i:s'))
                        ->get();
                    $stringFields = array('Customer ID', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Claim #', 'Insured', 'State');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'closed':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CLOSED)
                        ->get();
                    $stringFields = array('Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Outcome',
                        'Date of Inspection', 'Date Invoiced', 'Date Pymt Received', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'cancelled':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CANCELLED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'cancelled-closed':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::CLOSED_CANCELLED)
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                case 'today':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'tomorrow':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 23:59:59', strtotime('1 day')),
                            date('Y-m-d 00:00:00', strtotime('1 day'))])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'yesterday':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 00:00:00', strtotime('-1 day')),
                            date('Y-m-d 23:59:59', strtotime('-1 day'))])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'this-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [date('Y-m-d 23:59:59', strtotime('last sunday')),
                            date('Y-m-d 00:00:00', strtotime('next sunday'))])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'last-week':
                    $lastWeek = date('Y-m-d 00:00:00', strtotime('last sunday'));
                    $thisWeek = date('Y-m-d 23:59:59', strtotime('this sunday'));

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$lastWeek, $thisWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'next-week':
                    $nextWeek = date('Y-m-d 23:59:59', strtotime('next sunday'));
                    $date = new \DateTime($nextWeek);
                    $date->modify('+7 day');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$date->format('Y-m-d h:i:s'),
                            $nextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'this-month':
                    $firstDay = new \DateTime('first day of this month');
                    $lastDay = new \DateTime('last day of this month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'last-month':
                    $firstDay = new \DateTime('first day of last month');
                    $lastDay = new \DateTime('last day of last month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');

                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'next-month':
                    $firstDay = new \DateTime('first day of next month');
                    $lastDay = new \DateTime('last day of next month');

                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$firstDay->format('Y-m-d 00:00:00'),
                            $lastDay->format('Y-m-d 23:59:59')])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'this-year':
                    $startYear = date('Y-01-01 00:00:00');
                    $endYear = date('Y-12-31 23:59:59');
                    $reports = $this->getBaseQuery('outcome_type', $inspectionType)
                        ->whereBetween('date_of_inspection', [$startYear, $endYear])
                        ->groupBy('work_order.id')
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                
                case 'last-year':
                    $startYear = date('Y-01-01 00:00:00', strtotime('-1 year'));
                    $endYear = date('Y-12-31 23:59:59', strtotime('-1 year'));
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$startYear, $endYear])
                        ->get();
                    $stringFields = array('Customer ID', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;
                    
            }

            $name = array(ucfirst(str_replace('-', ' ', $status)));
            return response()->json(compact('reports', 'fields', 'name'));

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
    }


    /**
     * @param bool $metaKey
     * @return mixed
     */
    private function getBaseQuery($metaKey = false, $inspectionType) {
        $select = array('work_order.id as customer_id',
            DB::raw('CONCAT(work_order.first_name, " ", work_order.last_name) as insured'), 'u.name as adjuster',
            'p.insurance_company', 'work_order.state', 'inspection_types.name as inspection_type',
            DB::raw('DATE_FORMAT(date_of_inspection, \'%Y-%m-%d\') as date_of_inspection'),
            DB::raw('DATE_FORMAT(date_of_inspection, \'%h:%i:%s\') as time_of_inspection'),
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