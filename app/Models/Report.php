<?php
/**
 * Created by IntelliJ IDEA.
 * User: andrewfrye
 * Date: 5/27/16
 * Time: 1:54 PM
 */

namespace App\Models;

use App\Util\Shared;
use Barryvdh\DomPDF;
use Illuminate\Support\Facades\App;
use DB;
use Illuminate\Support\Facades\Log;


class Report {

    protected static $damageDescriptions = [
        'wind_damage' => 'Our wind damage inspection consists of inspecting every roof slope to verify any and all wind 
            damaged components to alltypes of roofing systems',
        'hail_damage' => 'Our hail damage inspection consists of looking on all directional slopes for granular 
            displacement on the shingles that are
            about the size in diameter of a dime, which may or may not be supported by mat fracture. These areas of granular
            displacement must be across the entire directional slope that we are assessing (which is a characteristic of hail damage). We
            use a 10’ X 10’ test square on all 4 directional slopes to test the statistical average of hail.'
    ];

    public function generate($html, $photosHtml, $id, $request) {
        $cwd = getcwd();
        $reportName = '/reports/' . $id . '_' . 'report.pdf';
        $photoName = '/reports/' . $id . '_' . 'photos.pdf';
        $finalName = '/reports/' . $id . '_final.pdf';
        $dockerBase = $request->session()->get('dockerBase');

        // unlink old files
        $this->unlinkFiles(array($cwd . $reportName, $cwd . $photoName, $cwd . $finalName));

        // report overview
        $reportPdf = App::make('dompdf.wrapper');
        $reportPdf->loadHTML($html);
        $reportOutput = $reportPdf->output();

        // sketch pdf
//        $photosPdf = App::make('dompdf.wrapper');
//        $photosPdf->setPaper('A4', 'landscape');
//        $photosPdf->loadHTML('<p>test</p>');
//        $photosOutput = $photosPdf->output();

        // photos pdf
        $photosPdf = App::make('dompdf.wrapper');
        $photosPdf->loadHTML($photosHtml);
        $photosOutput = $photosPdf->output();

        file_put_contents($cwd . $reportName, $reportOutput);
        file_put_contents($cwd . $photoName, $photosOutput);
        file_put_contents('reports/report.sh', '#!/bin/bash' . "\n" .
            'pdftk ' . $reportName . ' ' . $photoName . ' cat output ' . $finalName . "\n" .
            'chmod 777 ' . $finalName);

        // Run our docker-compose
        exec('cd ' . $dockerBase . ' && pwd && ./run.sh 2>&1', $output);

        $shared = new Shared();
        $url = $shared->uploadLocalFile($cwd . $finalName, 'inspections/' . $id, $finalName);
        return $url;
    }

    private function unlinkFiles($files) {
        foreach($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function getMetaData($id) {
        $data = DB::table('inspection_meta')->where('workorder_id', $id)->get();
        $meta = [];

        foreach ($data as $key => $value) {
            $meta[$value->key] = $value->value;
        }

        $meta = (object) $meta;
        return $meta;
    }

    public function getPhotos($id) {
        $data = DB::table('photos as p')
            ->select('p.file_name', 'p.label', 'p.file_url', 'p.display_order', 'c1.display_order as c1_order',
                'c1.id as c1_id', 'c2.id as c2_id', 'c1.name as c1_name', 'c2.display_order as c2_order',
                'c2.name as c2_name')
            ->leftJoin('categories as c1', 'c1.id', '=', 'p.parent_id')
            ->leftJoin('categories as c2', 'c2.id', '=', 'p.sub_parent_id')
            ->where('workorder_id', $id)
            ->orderBy('c1_order')
            ->orderBy('c2_order')
            ->orderBy('display_order')
            ->get();

        return $data;
    }

    public function getInspection($id) {
        $data = DB::table('work_order')
            ->select(DB::raw('CONCAT(work_order.first_name, " ", work_order.last_name) as insured'),
                'work_order.address',
                DB::raw('CONCAT(work_order.city, "/", work_order.state, "/", work_order.zip_code) as addressLine2'),
                'policy_num', 'date_of_inspection', 'user.name as adjuster', 'user_profiles.insurance_company as insurance_company')
            ->leftJoin('user', 'user.id', '=', 'work_order.adjuster_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'user.id')
            ->where('work_order.id', $id)->get();
        
        // We need to convert our time string to a date
        $time = $data[0]->date_of_inspection / 1000;
        $data[0]->date_of_inspection = date('Y-m-d h:i:s', $time);
        return $data;
    }



}