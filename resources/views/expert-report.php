<!doctype html>
<html>
<head><meta http-equiv=Content-Type content="text/html; charset=UTF-8">
    <title>Expert Inspection</title>
    <style type="text/css">
        * {
            margin:0; padding:0;
        }
        body{
            font:14px Georgia, serif;
        }
        #page-wrap{
            width:700px;
            margin: 0 auto;
            padding: 10px;
        }
        table{
            border-collapse: collapse; width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        table.top {
            width: 49.5%;
            position: relative;
            top: 30px;
        }
        table.position-right{
            position: relative;
            top: -70px;
            left: 360px;
        }
        tr.header {
            background-color: rgb(117, 41, 43);
            color: white;
        }
        td.border{
            border:1px solid #ccc; padding:6px;
        }
        thead {
            width:100%;position:fixed;
            height:109px;
        }
        td.center, p.center, h4.center, h3.center {
            text-align: center;
        }
        p, li {
            line-height: 20px;
        }
        .redTxt{
            color: red ;
        }
        .imgContainer{
            width:450px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .parentCatHead{
            margin-bottom:20px;
        }
        .imgCl{
            margin-bottom:20px;

        }
        p.lower { position: relative; top: 60px; }
        ul { padding-left: 15px; }
        li { padding: 10px; position: relative; left: 2em;}
        .page-break { page-break-after: always; position: absolute; top: 20px; }
        .clear { clear: both; }
        .header { color: rgb(117, 41, 43); font-weight: 600; }
        .row-header { text-align:  center; color: rgb(253, 0, 17); border: 1px solid black; padding: 5px; }
        .blue { color: rgb(45, 130, 253); font-weight: 600; }
        .red { color: rgb(253, 0, 17); text-decoration: underline; }
        .bigger-header { font-size: 16px; }
        .left { text-align: left; }
        .small { font-size: 10px; }
        .resize-signature { width: 300px; height: 100px; padding-top: 100px; color: black; }
        .relative { position: relative; }
        .padding-top: { padding-top: 25; }
        .damage-block : { position: relative; top: 20px; padding-bottom: 40px;}
        .sketch-helper { position: relative !important; left: -300px !important; }
    </style>
</head>
<body>

<!-- </div> -->

<div id="page-wrap">
<!--    <img src="--><?php //echo $_SERVER['DOCUMENT_ROOT'] . '/assets/gfx/logo-icon.png'; ?><!--" width="100" height="100" alt="test" style="text-align:center">-->
    <table>
        <tr><th class="header">Trinity Inspections, LLC</th></tr>
        <tr><td class="center">P.O. Box 938</td></tr>
        <tr><td class="center">Locust, NC 28097</td></tr>
    </table>

    <!-- policy holder -->
    <table class="top">
        <tr><th class="left">Policyholder Information:</th></tr>
        <tr><td><?php echo $inspection->insured; ;?></td></tr>
        <tr><td><?php echo $inspection->address ;?></td></tr>
        <tr><td><?php echo $inspection->addressLine2;?></td></tr>
        <tr><th class="left">Claim #:&nbsp;<?php echo $inspection->claim_num; ?></th></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td></tr>
        <tr><th class="left">Policy Holder Present: <?php echo $meta->insured_present; ?></th></tr>
        <tr><th class="left">Contractor Present: <?php echo $meta->was_the_roofer_present; ?></th></tr>
    </table>

    <table border="1" class="top position-right">
        <tr><th>Adjuster Name:</th><td class="border">&nbsp;
                <?php echo $inspection->adjusterName; ?>
            </td></tr>
        <tr><th>Insurance Company:</th><td class="border"><?php echo $inspection->insurance_company ?></td></tr>
        <tr><th>Date:</th><td class="border">&nbsp;<?php echo $inspection->date_of_inspection; ?></td></tr>
    </table>
    <h4 class="center">Inspection Overview:</h4>
    <p>On
        <?php
            echo date('n/j/Y', strtotime($inspection->date_of_inspection)) . ' at ' .
                date('h:i A', strtotime($inspection->date_of_inspection));
        ?> an inspection was made of this <?php echo str_replace('<br>', '', $meta->roof_height); ?>,
        <?php echo str_replace(',', ', ', $meta->siding_types_array); ?>
        sided dwelling with <?php echo str_replace(',', ', ', $meta->roofing_types_array); ?> roofing material.
        The policyholder

        <?php

            // insured present
            $insuredPresent = ' was not present.';

            if ($meta->insured_present == 'Yes') {
                $insuredPresent = ' was present ';
                $explain = ' to explain the extent of the damages present ';
                if ($meta->insured_stay_for_entire_inspection == 'No') {
                    $insuredPresent .= ' and we were able' . $explain . 'on the property.';
                } else {
                    $insuredPresent .= ' and we were not able ' . $explain . 'as they left the property prior to the inspection
                    being completed.';
                }
            }

            echo $insuredPresent;

            // roofer present
            $rooferPresent = ' There was not a roofer present for this inspection.';
            if ($meta->was_the_roofer_present == 'Yes') {
                $rooferPresent = ' The roofer was present ';
                if ($meta->did_roofer_stay_for_entire_inspection == 'Yes') {
                    $rooferPresent .= ' and stayed for the entire inspection.';

                    if ($meta->was_the_roof_climbed_by_the_roofer == 'Yes') {
                        $rooferPresent .= ' He/she climbed the roof ';
                    } else {
                        $rooferPresent .= ' He/she did not climb the roof ';
                    }


                    if ($meta->did_the_roofer_agree_with_wind == 'Yes' && $meta->did_the_roofer_agree_with_hail_assessment == 'Yes') {
                        $rooferPresent .= ' agreed with the wind & hail assessment.';
                    } else if ($meta->did_the_roofer_agree_with_wind == 'Yes' && $meta->did_the_roofer_agree_with_hail_assessment == 'No') {
                        $rooferPresent .= ' agreed with the wind assessment, but not with the hail assessment.';
                    } else if ($meta->did_the_roofer_agree_with_wind == 'No' && $meta->did_the_roofer_agree_with_hail_assessment == 'Yes') {
                        $rooferPresent .= ' agreed with the hail assessment, but not with the wind assessment.';
                    } else {
                        $rooferPresent .= ' did not agree with wind and hail assessment.';
                    }

                    if ($meta->did_the_roofer_refuse_test_squares == 'Yes') {
                        $rooferPresent .= ' He/she refused test squares.';
                    } else {
                        $rooferPresent .= ' He/she did not refuse test squares.';
                    }

                } else {
                    $rooferPresent .= ' and did not stay for the entire inspection.';
                }
            }

            echo $rooferPresent;
        ?>
    </p>
    <p class="small lower">All opinions expressed in this report are based on factual evidence found at the dwelling
        listed above, at the date and time of inspection. It is understood by all parties involved that this inspection
        and report is provided on a “Limited Liability” basis, and the maximum liability by the inspector and/or
        Trinity Inspections LLC for errors and omissions, negligence, or from damage of surrounding roofing products
        that may cause any problems, shall be limited to the amount of the fee paid for this inspection.
    </p>

    <div class="resize-signature">&nbsp;</div>

    <p class="relative">Anthony Giordano<br>
        Owner, Senior Certified Inspector<br>
        HAAG Certification # 201006130<br>
        NC Adjusters License # 12760239<br>
        SC Adjusters License # 625784</p>

    <div class="page-break"></div>
</div>
</body>
</html>