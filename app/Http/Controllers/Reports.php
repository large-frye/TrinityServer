<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 2/15/16
 * Time: 1:10 PM
 */

namespace App\Http\Controllers;

use App\Http\Middleware\CorsMiddleware;
use App\Models\Count;
use App\Models\Field;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\WorkorderFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Input;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\Workorder;
use App\Models\Invoice;
use DB;
use Maatwebsite\Excel\Facades\Excel;

class Reports extends BaseController {

    var $reportModel;
    var $countModel;
    var $thirtyDays;
    var $sixtyDays;
    var $ninetyDays;
    var $invoice;
    var $workorder;
    var $typeToId;

    // Status Ids
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
    const REPORTING = 19;
    const INVOICE_ALACRITY = 20;
    const INVOICING = 21;

    // Alert types
    const ALERT_TO_INSPECTOR = 'alert_to_inspector';
    const ALERT_OFFICE = 'alert_office';
    const ALERT_ADMIN = 'alert_admin';

    // Attention types
    const ADMIN_ATTN = 'admin';
    const OFFICE_ATTN = 'office';
    const INSPECTOR_ATTN = 'inspector';

    // Report types
    const EXPERT = 'expert';
    const LADDER_ASSIST_WITH_REPORT = 'ladder_assist_with_report';

    public function __construct() {
        $this->workorder = new Workorder();
        $this->reportModel = new Report();
        $this->countModel = new Count();
        $this->invoice = new Invoice();
        $this->thirtyDays = new \DateTime('- 30 days');
        $this->sixtyDays = new \DateTime('- 60 days');
        $this->ninetyDays = new \DateTime('- 90 days');

        $this->typeToId = array(
            'new' => ReportType::createReport(Reports::NEW_INSPECTION, 'new', false),
            'open' => ReportType::createReport(array(Reports::CLOSED, Reports::CLOSED_CANCELLED), 'open', true),
            'inspector-attention-required', ReportType::createReport(Reports::INSPECTOR_ATTENTION_REQUIRED, 'inspector-attention-required', false),
            'office-attention-required' => ReportType::createReport(Reports::OFFICE_ATTENTION_REQUIRED, 'office-attention-required' , false),
            'admin-attention-required' => ReportType::createReport(Reports::OFFICE_ATTENTION_REQUIRED, 'admin-attention-required' , false),
            'new-pickups' => ReportType::createReport(Reports::NEW_PICKUP, 'new-pickups' , false),
            'process-reschedule' => ReportType::createReport(array(Reports::IN_PROCESS, Reports::RESCHEDULE), 'process-reschedule' , false),
            'on-hold' => ReportType::createReport(Reports::ON_HOLD, 'on-hold' , false),
            'scheduled' => ReportType::createReport(Reports::SCHEDULED, 'scheduled' , false),
            'inspected' => ReportType::createReport(Reports::INSPECTED, 'inspected' , false),
            'pre-invoice' => ReportType::createReport(Reports::PRE_INVOICE, 'pre-invoice' , false),
            'invoiced' => ReportType::createReport(Reports::INVOICED, 'invoiced' , false),
            'reporting' => ReportType::createReport(Reports::REPORTING, 'reporting' , false),
            'inv-alacrity' => ReportType::createReport(Reports::INVOICE_ALACRITY, 'inv-alacrity', false),
            'invoicing' => ReportType::createReport(Reports::INVOICING, 'invoicing', false),
            'closed' => ReportType::createReport(Reports::CLOSED, 'closed', false), 
            'cancelled' => ReportType::createReport(Reports::CANCELLED, 'cancelled' , false),
            'cancelled-closed' => ReportType::createReport(Reports::CLOSED_CANCELLED, 'cancelled-closed', false),
            'today' => ReportType::createReport(null, 'today', false, [$this->countModel->today, $this->countModel->tomorrow]),
            'tomorrow' => ReportType::createReport(null, 'tomorrow' , false, [$this->countModel->tomorrow, $this->countModel->nextTwoDays]),
            'yesterday' => ReportType::createReport(null, 'yesterday' , false, [$this->countModel->yesterday, $this->countModel->today] ),
            'this-week' => ReportType::createReport(null, 'this-week' , false, [$this->countModel->thisWeek, $this->countModel->nextWeek] ),
            'next-week' => ReportType::createReport(null, 'next-week' , false, [$this->countModel->nextWeek, $this->countModel->twoNextWeek] ),
            'last-week' => ReportType::createReport(null, 'last-week' , false, [$this->countModel->lastWeek, $this->countModel->thisWeek] ),
            'this-month' => ReportType::createReport(null, 'this-month' , false, [$this->countModel->thisMonth, $this->countModel->nextMonth] ),
            'next-month' => ReportType::createReport(null, 'next-month' , false, [$this->countModel->nextMonth, $this->countModel->lastDayOfNextMonth] ),
            'last-month' => ReportType::createReport(null, 'last-month' , false,  [$this->countModel->lastMonth, $this->countModel->thisMonth]),
            'this-year' => ReportType::createReport(null, 'this-year' , false,  [$this->countModel->year, $this->countModel->nextYear]),
            'last-year' => ReportType::createReport(null, 'last-year' , false, [$this->countModel->lastYear, $this->countModel->year])
        );
    }

    /**
     * Generate a pdf report
     * @param $id
     * @return mixed
     */
    public function generate(Request $request, $id) {
        $meta = $this->reportModel->getMetaData($id);
        $data = $this->reportModel->getInspection($id);
        $photos = $this->reportModel->getPhotos(($id));
        $sketches = WorkorderFile::where('workorder_id', $id)->where('file_type', 'sketch')->get();
        $sketchHtml = '';
        $type = $data[0]->inspection_outcome == '9' ? Reports::EXPERT : Reports::LADDER_ASSIST_WITH_REPORT;

        $explanations = false;

        if ($type === Reports::EXPERT) {
            $html = view('expert-report', ['meta' => $meta, 'inspection' => $data[0]])->render();

            // create explanations here.
            $explanations = view('explanations',
                ['explanations' => $this->reportModel->getExplanations($meta, $data[0])])->render();

        } else {
            $html = view('basic-report', ['meta' => $meta, 'inspection' => $data[0]])->render();
        }

        // photos
        $photosHtml = view('photos', ['photos' => $photos])->render();

        // sketches
        foreach($sketches as $sketch) {
            $sketchHtml .= '<img src="' . $sketch->file_url . '" alt="" style="max-width:100%; position: absolute; top: -50px;"/>';
        }

        // create content array with data for report
        $content = array('report' => $html, 'explanations' => $explanations, 'photos' => $photosHtml,
            'sketches' => $sketchHtml);

        // end pdf url
        try {
            $pdfUrl = $this->reportModel->generate($content, $id, $request);
        } catch (\Exception $e) {
            return response()->json(array('error' => $e), 200);
        }

        return response()->json(array('pdfUrl' => $pdfUrl), 200);
    }

    public function get() {
        $reports = [];
        $fields = [];

        try {
            $reports = $this->getBaseQuery(false, false)->get();
            $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                'Inspector', 'Date of Inspection', 'Date Created');
            $fields = $this->createAssociateFieldArray($stringFields, $fields);
            $name = array(ucfirst(str_replace('-', ' ', 'All')));
            $header = 'All Inspections';

            return response()->json(compact('reports', 'fields', 'header', 'name'));

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
                    $reports = $this->reportsByStatus(array(Reports::CLOSED, Reports::CLOSED_CANCELLED), $inspectionType, true);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Open Inspections';
                    break;

                case 'inspector-attention-required':
                    $reports = $this->getReportsByAttType(Reports::ALERT_TO_INSPECTOR, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection',  'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Requiring Inspector\'s Attention';
                    break;

                case 'office-attention-required':
                    $reports = $this->getReportsByAttType(Reports::ALERT_OFFICE, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspector', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Requiring Office Attention';
                    break;

                case 'admin-attention-required':
                    $reports = $this->getReportsByAttType(Reports::ALERT_ADMIN, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspector', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Requiring Admin Attention';
                    break;

                case 'new-pickups':
                    $reports = $this->reportsByStatus(Reports::NEW_PICKUP, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Inspector', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'New Pickups From Inspectors';
                    break;

                case 'new':
                    $reports = $this->reportsByStatus(Reports::NEW_INSPECTION, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'New Inspections';
                    break;

                case 'in-process':
                    $reports = $this->reportsByStatus(Reports::IN_PROCESS, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections In Process';
                    break;

                case 'process-reschedule':
                    $reports = $this->reportsByStatus([Reports::IN_PROCESS, Reports::RESCHEDULE], $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Status', 'Insured', 'City', 'State', 'Adjuster',
                        'Date of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Need To Be Scheduled';
                    break;

                case 'reschedule':
                    $reports = $this->reportsByStatus(Reports::RESCHEDULE, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'Date of Last Contact', 'City', 'State', 'Adjuster',
                        'Date of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Need To Be Rescheduled';
                    break;

                case 'on-hold':
                    $reports = $this->reportsByStatus(Reports::ON_HOLD, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date of Inspection', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are On Hold';
                    break;

                case 'scheduled':
                    $reports = $this->reportsByStatus(Reports::SCHEDULED, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'Date of Last Contact', 'City', 'State',
                        'Adjuster', 'Date of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Scheduled Inspections';
                    break;

                case 'post-inspection-date':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->where('status_id', '=', Reports::SCHEDULED)
                        ->where('date_of_inspection', '<', time() * 1000)
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are Past Their Inspection Date';
                    break;

                case 'inspected':
                    $reports = $this->reportsByStatus(Reports::INSPECTED, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Date of Inspection', 'Inspector', 'Inspection Time',
                        'Inspection Outcome', 'Insured', 'State', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspected That Have Been Completed';
                    break;

                case 'pre-invoice':
                    $reports = $this->getInspectorReports(Reports::PRE_INVOICE, $status);
                    $stringFields = array('Customer ID', 'Claim Num', 'Date of Inspection', 'Inspection Outcome', 'Date Invoiced',
                        'Adjuster', 'Insurance Company', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Need one';
                    break;

                case 'invoiced':
                    $reports = $this->reportsByStatus(Reports::INVOICED, $inspectionType);
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim Num', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Have Been Invoiced & Are Waiting For Payment';
                    break;

                case 'reporting':
                    $reports = $this->reportsByStatus(Reports::REPORTING, $inspectionType);
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim Num', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are Ready For A Report';
                    break;

                case 'inv-alacrity':
                    $reports = $this->reportsByStatus(Reports::INVOICE_ALACRITY, $inspectionType);
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim Num', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections That Are Ready To Be Submitted To Alacrity For Payment';
                    break;

                case 'invoicing':
                    $reports = $this->reportsByStatus(Reports::INVOICING, $inspectionType);
                    $stringFields = array('Customer ID', 'Date of Invoiced', 'Date of Inspection', 'Inspection Outcome',
                        'Adjuster', 'Insurance Company', 'Claim Num', 'Insured', 'State', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Inspections Ready For Invoicing Through Quickbooks';
                    break;

                case 'closed':
                    $reports = $this->reportsByStatus(Reports::CLOSED, $inspectionType);
                    $stringFields = array('Customer Id', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company',
                        'Inspection Outcome', 'Date of Inspection', 'Date Invoiced', 'Date Pymt Received', 'Date Created');
                    $header = 'Closed Inspections';
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'cancelled':
                    $reports = $this->reportsByStatus(Reports::CANCELLED, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $header = 'Cancelled Inspections';
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    break;

                case 'cancelled-closed':
                    $reports = $this->reportsByStatus(Reports::CLOSED_CANCELLED, $inspectionType);
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Insurance Company', 'Inspection Type',
                        'Inspector', 'Date Cancelled', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Closed Inspections (Cancelled)';
                    break;

                case 'today':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->today, $this->countModel->tomorrow])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Today\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'tomorrow':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->tomorrow, $this->countModel->nextTwoDays])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Tomorrow\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'yesterday':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->yesterday, $this->countModel->today])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Yesterday\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisWeek, $this->countModel->nextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'last-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastWeek, $this->countModel->thisWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector', 'Date of Inspection',
                        'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'next-week':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextWeek, $this->countModel->twoNextWeek])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'City', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Adjuster', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Week\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->thisMonth, $this->countModel->nextMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Inspector', 'Date of Inspection',
                        'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'last-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastMonth, $this->countModel->thisMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Last Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'next-month':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->nextMonth, $this->countModel->lastDayOfNextMonth])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Time of Inspection', 'Inspection Type', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'Next Month\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;

                case 'this-year':
                    $reports = $this->getBaseQuery('outcome_type', $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->year, $this->countModel->nextYear])
                        ->groupBy('work_order.id')
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector',
                        'Date of Inspection', 'Inspection Type', 'Inspection Outcome', 'Date Created');
                    $fields = $this->createAssociateFieldArray($stringFields, $fields);
                    $header = 'This Year\'s ' . $this->getInspectionStr($inspectionType) . ' Inspections';
                    break;
                
                case 'last-year':
                    $reports = $this->getBaseQuery(false, $inspectionType)
                        ->whereBetween('date_of_inspection', [$this->countModel->lastYear, $this->countModel->year])
                        ->get();
                    $stringFields = array('Customer ID', 'Claim Num', 'Insured', 'State', 'Adjuster', 'Inspector',
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

    private function getReportsByAttType($attentionType, $inspectionType) {
        $query = $this->getBaseQuery(false, $inspectionType);
        $query->where($attentionType, 1);
        return $query->get();
    }

    private function reportsByStatus($status, $inspectionType, $not = false, $date = false) {
        $base = $this->getBaseQuery(false, $inspectionType);
        if (is_array($status)) {
            if ($not) {
                $base->whereNotIn('work_order.status_id', $status);
            } else {
                $base->whereIn('work_order.status_id', $status);
            }
        } else if ($date) {
            $base->whereBetween('date_of_inspection', $date);
        } else {
            $base->where('work_order.status_id', '=', $status);
        }
        return $base->get();
    }

    public function exportToExcel(Request $request, $type) {
        $data = null;

        if (isset($type) && isset($this->typeToId[$type]) && $type != 'all') {
            $reportType = $this->typeToId[$type];
            $data = $this->reportsByStatus($reportType->getId(), null, $reportType->getNegate(), $reportType->getDate());
        } else {
            $data = $this->getBaseQuery(false, false)->get();
        }

        // convert all children to array
        foreach($data as $key => $child) {
            $data[$key] = (array) $child;
        }

        $status = Excel::create('reports', function ($excel) use (&$data) {
            $excel->sheet('sheetname', function ($sheet) use (&$data) {
                $sheet->fromArray((array) $data);
            });
        })->store('csv');

        if ($status) {
            $file = $request->session()->get('fileBase') . '/reports.csv';
            return response()->json(array('file' => $file));
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
            case 'ladder-assist-with-report':
                return 0;
            case 'ladder-assist':
                return 5;
            case 'expert':
                return 1;
        }
        return false;
    }

    private function getInspectionStr($type) {
        switch (strtolower($type)) {
            case 'ladder-assist-with-report':
                return 'Ladder Assist w/ Report';
            case 'cancelled':
            case 'expert':
                return ucfirst($type);
            case 'ladder-assist':
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
            'work_order.created_at as date_created', 'work_order.city', 'work_order.claim_num',
            'u2.name as inspector');

        $query = DB::table('work_order');
        $query->select($select)
            ->leftJoin('user as u', 'work_order.adjuster_id', '=', 'u.id')
            ->leftJoin('user as u2', 'work_order.inspector_id', '=', 'u2.id')
            ->leftJoin('user_profiles as p', 'u.id', '=', 'p.user_id')
            ->leftJoin('inspection_types', 'work_order.inspection_type', '=', 'inspection_types.id');

        // Delimit by inspection type
        if ($inspectionType && !in_array($inspectionType, array('all', 'cancelled'))) {
            $inspectionType = $this->getInspectionType($inspectionType);
            $query->where('work_order.inspection_type', $inspectionType);
        } else if ($inspectionType == 'cancelled') {
            $query->where('work_order.status_id', Reports::CANCELLED);
        }

        return $query;
    }

}