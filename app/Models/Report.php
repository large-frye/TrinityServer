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

    public function generate($content, $id, $request) {

        $cwd = getcwd();
        $reportName = '/reports/' . $id . '_' . 'report.pdf';
        $photoName = '/reports/' . $id . '_' . 'photos.pdf';
        $explanationsName = '/reports/' . $id . '_' . 'explanations.pdf';
        $sketchName = '/reports/' . $id . '_' . 'sketch.pdf';
        $finalName = '/reports/' . $id . '_final.pdf';
        $dockerBase = $request->session()->get('dockerBase');

        // unlink old files
        $this->unlinkFiles(array($cwd . $reportName, $cwd . $photoName, $cwd . $finalName));

        // report overview
        $reportPdf = App::make('dompdf.wrapper');
        $reportPdf->loadHTML($content['report']);
        $reportOutput = $reportPdf->output();

        // sketch pdf
        $sketchPdf = App::make('dompdf.wrapper');
        $sketchPdf->setPaper('A4', 'landscape');
        $sketchPdf->loadHTML($content['sketches']);
        $sketchOutput = $sketchPdf->output();

        // photos pdf
        $photosPdf = App::make('dompdf.wrapper');
        $photosPdf->loadHTML($content['photos']);
        $photosOutput = $photosPdf->output();

        file_put_contents($cwd . $reportName, $reportOutput);
        file_put_contents($cwd . $photoName, $photosOutput);
        file_put_contents($cwd . $sketchName, $sketchOutput);

        // only if explanations != false
        if ($content['explanations'] != false) {
            $explanationsPdf = App::make('dompdf.wrapper');
            $explanationsPdf->loadHTML($content['explanations']);
            $explanationsOutput = $explanationsPdf->output();
            file_put_contents($cwd . $explanationsName, $explanationsOutput);
            file_put_contents('reports/report.sh', '#!/bin/bash' . "\n" .
                'pdftk ' .
                $reportName . ' ' .
                $sketchName . ' ' .
                $photoName . ' ' .
                $explanationsName .
                '  cat output ' . $finalName . "\n" .
                'chmod 777 ' . $finalName);
        } else {
            file_put_contents('reports/report.sh', '#!/bin/bash' . "\n" .
                'pdftk ' .
                $reportName . ' ' .
                $sketchName . ' ' .
                $photoName .
                ' cat output ' . $finalName . "\n" .
                'chmod 777 ' . $finalName);
        }

        // run our docker-compose
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
                'policy_num', 'claim_num', 'date_of_inspection', 'user.name as adjuster',
                DB::raw('CONCAT(user_profiles.first_name, " ", user_profiles.last_name) as adjusterName'),
                'user_profiles.insurance_company as insurance_company', 'work_order.inspection_outcome')
            ->leftJoin('user', 'user.id', '=', 'work_order.adjuster_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'user.id')
            ->where('work_order.id', $id)->get();
        
        // We need to convert our time string to a date
        $time = $data[0]->date_of_inspection / 1000;
        $data[0]->date_of_inspection = date('Y-m-d h:i:s', $time);
        return $data;
    }

    public function getExplanations($meta, $data) {
        $finalDamages = [];
        $damages = array('Interior Damage' => 'Interior damages were present. Interior leaks can be a result of hail and/or wind damage, but can also be a result of installation error 
                                           or improper maintenance of vent pipe flashing. Refer to the damage assessment for comments regarding any water intrusion concerns.',
            'Mechanical Damage' => 'Mechanical damage is defined as damage which has occurred due to other than weather related conditions. Some good examples of mechanical 
                                             damage are the holes left in the shingles due to the use of toe boards during the roofing process. Other forms of mechanical damage are 
                                             foot traffic from installers and inspectors which can leaves areas of marred and/or exposed asphalt, shingle bundle scrapes, tool 
                                             marks, etc...',
            'High Nailing' => 'High nailing is a common incorrect installation method that can adversely impact the longevity of your roofing product. When a shingle is 
                                        nailed too high, the nail will miss the head lap of the shingle installed beneath it, causing the shingle to be nailed in only half the 
                                        recommended nailing places. In laminated shingles, this can cause delamination of the upper and lower laminate. High nailing often results 
                                        in slippage - refer to the paragraph entitled “Slippage” (if present) for the explanation of slippage.',
            'Nail Extrusions' => 'Nail extrusions are often mistaken for wind damage. When a nail is pushed out through the forces of expansion and contraction on the 
                                           shank of a nail it will cause the nail to raise out of the position in which it was installed. This will cause the shingle directly 
                                           above it to be raised into the air giving the appearance that the wind has lifted it up. When viewed from the ground, the shingles 
                                           lifted by nail extrusions can appear very prominent and may seem to be a source of a possible leak. This, however, is not typically 
                                           the case. The nails that have been extruded should be hammered back into place or removed and replaced with a new nail. Routine 
                                           maintenance of these areas can fix the problem.',
            'Water Intrusion' => 'Water intrusion concerns are defined as areas of possible leaks. These areas should be attended to immediately to prevent any future 
                                           water damage to the interior of the dwelling. Refer to the damage assessment for information regarding the possible leak entry points 
                                           noted on the roofing system. In most cases, leaks that are maintenance related from continual seepage, often take an extended 
                                           period of time to finally show as a stain on your ceiling or walls. Heavy rain and driving wind can accelerate the continual seepage, 
                                           causing the stain to appear during a single storm. This will often lead the policy holder to believe that there is a major problem 
                                           with the roofing system, which is often not the case. General routine inspection and maintenance should be performed yearly to insure 
                                           all aspects of the roofing system are in proper working order.',
            'Vent Pipe Failing Failure' => 'Most roofing systems have plumbing pipes that protrude through the surface of the shingles. These pipes are used to draw 
                                                     air into the plumbing system. Some of the pipes use a rubber flashing boot to route rainwater away from the plumbing 
                                                     pipe and onto the shingles. As time progresses in the life span of the roof, the UV rays of the sun, and other elements 
                                                     of weather cause the rubber material to dry rot, crack, and split open. Many leaks are caused by this degradation of rubber 
                                                     material and are often mistaken for leaks caused by hail or wind damage. This water intrusion concern on the roofing system 
                                                     should be addressed immediately. Rubber vent pipe flashing should be replaced every 7-10 years to insure that they are in 
                                                     proper working condition.',
            'Lichen Growth' => 'Lichen is an organic growth that often appears in, but is not limited to, the shaded portions of the roof. Lichen is an invasive growth 
                                         that feeds on the asphalt layer of a shingle often removing the granules directly below the lichen growth. When lichen is removed from 
                                         the shingle surface it can often look like hail marks because of the circular shape of lichen growth. Lichen growth can be differentiated 
                                         from hail damage based on the absence of impact marks (i.e. mat fracture). Furthermore, the areas of granule loss left behind from lichen
                                          growth are usually only on portions of the entire roof slope, so it does not appear in a consistent pattern throughout the entire slope.
                                           Hail damage will not occur on one section of an entire slope, but it will damage the entire slope consistently throughout. Lichen damage 
                                           can further be differentiated from hail damage by the lack of damage to the reinforcement mat.',
            'Algae Growth' => 'Algae growth is a non invasive organic growth that appears on the portions of the roof that are more shaded (typically the North and East 
                                        slopes, but not limited to these slopes). This black or red looking growth does not affect the performance or the longevity of your 
                                        roofing system. It is only a visual blemish that can be removed by cleaning methods or the installation of certain metal components 
                                        to create a toxic environment which prevents algae growth. Spatter within the algae growth is used to confirm that hail has impacted 
                                        the roof recently, also indicating the size of the hail involved.',
            'Spatter Present' => 'Spatter is the result of hail impacting areas of grime or oxidized metals, which removes the grime or oxidation, leaving a 
                     cleaned off area. This area of growth removal indicates the size and diameter of the hailstone. Usually spatter is found in the algae growth on the
                      shaded portions of a roof. Hail spatter is also found on objects that oxidize from the weather (e.g.: vinyl siding, satellite dishes, air 
                          conditioning units).',
            'Blistering' => 'During the manufacturing process, moisture in the form of water vapor and other gasses can become entrapped in the top layer 
                     of the bituminous asphalt coating prior to granule application. When the shingle is heated after installation on the roof, these entrapped
                      gasses can expand and form a bubble within the asphalt coating - this is called a closed blister because the asphalt coating remains intact 
                      with granule covering. When the top of the blister bursts, the granules on the blister are released and a small crater is formed - this is 
                      called an open blister. Blisters can be differentiated from hail damage because blistering does not damage the shingle reinforcing mat. Also,
                       granule loss due to blistering is upward and outward from within the shingle. Some shingles will still blister even with proper ventilation 
                       but unventilated roofs are more susceptible to blistering.',
            'Slippage' => 'Slippage of an asphalt roofing system is always the result of high nailing. High nailing is an incorrect installation method. 
                     Slippage typically occurs when a roof is steep, which can lead to shingles slipping downward out of their proper placement. When a shingle is 
                     nailed too high, the nail misses the head lap of the shingle installed beneath it. This means the shingle will be nailed in only half the 
                     recommended nailing places. For laminated shingles this can cause delamination of the upper and lower laminate.',
            'Flashing Breach' => 'Most roofs have a series of flashing elements in key places to route water away from chimneys, skylights, framing walls, 
                     and other roofing transitions. It is the job of the counter-flashing and step-flashing to keep these areas watertight by routing the 
                     rainwater back on top of the roofing system. Often, during extended periods of heavy driving rain, these areas can be breached causing 
                     interior damage to a dwelling. Improper installation of these flashing elements can cause a leak to appear slowly over an extended period 
                     of time, often not affecting the interior walls or ceilings for years after installation.');
        foreach ($damages as $damageKey => $damageVal) {
            if (!isset($meta->roof_conditions_array))
                return $finalDamages;

            $roof_conditions = explode(',', $meta->roof_conditions_array);

            if (in_array($damageKey, $roof_conditions)) {
                array_push($finalDamages, '<p>' . $damageVal . '</p>');
            }
        }

        return $finalDamages;
    }



}