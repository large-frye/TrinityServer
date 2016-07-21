<!doctype html>
<html>
<head><meta http-equiv=Content-Type content="text/html; charset=UTF-8">
    <title>Basic Inspection</title>
    <style type="text/css">
        * {
            margin:0px; padding:0; margin-top: 10px;
        }
        body{
            font:14px Georgia, serif;
            line-height: 24px;
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
            padding-bottom: 1em;
        }
        table.top {
            width: 49.5%;
            margin-top: 20px;
        }
        table.position-right{
            margin-top: -130px !important;
            margin-left: 280px !important;
            width: 85%;
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
        td.center, p.center {
            text-align: center;
        }
        p {
            line-height: 20px;
        }
        ul
        {
            list-style-type: none;
        }
        .sectionDescrip{
            font:14px;
        }
        .hailDmg{
            font:14px;
        }
        .damageLi{
            font-size: 15px;
            list-style-type: none;
            margin: 0em;
            padding: 0em;
        }
        .damageUL{
            position:relative;
            left:2em;
            list-style: none;
        }
        .redTxt{
            color: red ;
        }
        .imgContainer{
            width:450px;
            padding-top:2.1cm;
            display: block;
            position:relative;
            left:50px;
            margin-bottom: 52px;
        }
        .imgDiv{
            display: block;
            padding: 10px;
            margin-top: 50px;
        }
        .parentCatHead{
            margin-bottom:20px;
        }
        .imgCl{
            margin-bottom:20px;
        }
        .ground-inspection {
            position: relative;
            padding-top: 2em;
            padding-bottom: 2em;
        }
        .page-break { page-break-after: always; }
        .clear { clear: both; }
        .header { color: rgb(117, 41, 43); font-weight: 600; }
        .row-header { text-align:  center; color: rgb(253, 0, 17); border: 1px solid black; padding: 5px; }
        .blue { color: rgb(45, 130, 253); font-weight: 600; }
        .red { color: rgb(253, 0, 17); text-decoration: underline; }
        .sketch-helper { position: relative !important; left: -300px !important; }
    </style>
</head>
<body>

<div id="page-wrap">
    <div class="top-bar">
        <table>
            <tr><th class="header">Trinity Inspections, LLC</th></tr>
            <tr><td class="center">P.O. Box 938</td></tr>
            <tr><td class="center">Locust, NC 28097</td></tr>
        </table>
    </div>

    <table class="top">
        <tr><th>Policy Holder:</th><td class="border"><?php echo $inspection->insured ;?></td></tr>
        <tr><th>Street:</th><td class="border"><?php echo $inspection->address;?></td></tr>
        <tr><th>City/State/Zip:</th><td class="border"><?php echo $inspection->addressLine2; ?></td></tr>
        <tr><th>Claim #:</th><td class="border">&nbsp;<?php echo $inspection->policy_num;?></td></tr>
    </table>
    <table border="1" class="top position-right">
        <tr><th>Adjuster Name:</th><td class="border">&nbsp;<?php echo $inspection->adjuster;?></td></tr>
        <tr><th>Insurance Company:</th><td class="border"><?php echo $inspection->insurance_company;?></td></tr>
        <tr><th>Date:</th><td class="border">&nbsp;<?php echo $inspection->date_of_inspection;?></td></tr>
    </table>

    <div class="ground-inspection">
        <h4 class="row-header">GROUND INSPECTION</h4>
        <p>During our ground level walk around inspection of the loss<span class="blue">
        <?php echo isset($meta->collateral_damages_array) ? "we did find collateral damage" : "we did not find collateral damage";?></span>
            to the following building materials that may be more susceptible to wind or hail.</p>
        <p class="blue"><?php echo isset($meta->collateral_damages_array) ? str_replace(',', ', ', $meta->collateral_damages_array) : null;?></p>
        <?php if (isset($meta->collateral_damages_comments)) {
            echo "<h4>Collateral Damages Comments: </h4><p>" . $meta->collateral_damages_comments . "</p>";
        } ?>

        <h4 class="row-header">ROOF INSPECTION</h4>

        <!-- Wind damage -->
        <h5 class="red">WIND DAMAGE:</h5>
        <p class="sectionDescrip">Our wind damage inspection consists of inspecting every roof slope to verify any and
            all wind damaged components to all types of roofing systems.</p>
        <?php

            if (isset($meta->wind_front_shingles_damaged) || isset($meta->wind_rear_shingles_damaged) || isset($meta->wind_left_shingles_damaged)
            || isset($meta->wind_right_shingles_damaged)) {
                $output = '<ul class="damageUL">';
                $damages = ['North (Front)' => isset($meta->wind_front_shingles_damaged) ? $meta->wind_front_shingles_damaged : false,
                    'South (Rear)' => isset($meta->wind_rear_shingles_damaged) ? $meta->wind_rear_shingles_damaged : false,
                    'East (Right)' => isset($meta->wind_right_shingles_damaged) ? $meta->wind_right_shingles_damaged : false,
                    'West (Left)' => isset($meta->wind_left_shingles_damaged) ? $meta->wind_left_shingles_damaged : false
                ];
                foreach ($damages as $label => $value) {
                    if ($value) {
                        $li = '<li class="damageLi">During our inspection of the <span class="redTxt">' . $label . '</span> facing scope 
                            we found <span class="red">' . $value . ' </span> wind-damaged shingles.</li>';
                        $output .= $li;
                    }
                }

                $output .= '</ul>';
                echo $output;
            } else {
                echo '<p>There was no wind damage found during our inspection.</p>';
            }

        ?>
        <!-- End wind damage -->

        <!-- Metal damage -->
        <?php
            if (isset($meta->metal_damages_array )|| isset($meta->metal_damage_hail_size) ||
                isset($meta->metal_damage_comments)) {
                echo '<h5 class="red">METAL DAMAGE:</h5>' .
                    '<p>We also found cosmetic denting to the thin gauge aluminum vents on the roof: ' . $meta->metal_damages_array . '</p>';
                ;
            }
        ?>
        <!-- End Metal damage -->

        <!-- Hail damage -->
        <h5 class="red">HAIL DAMAGE:</h5>
        <p class="sectionDescrip">Our hail damage inspection consists of looking on all directional slopes for granular
            displacement on the shingles that are about the size in diameter of a dime, which may or may not be supported
            by mat fracture. These areas of granular displacement must be across the entire directional slope that we
            are assessing (which is a characteristic of hail damage). We use a 10’ X 10’ test square on all 4
            directional slopes to test the statistical average of hail.
        </p>
        <?php

            if (isset($meta->hail_front_shingles_damaged) || isset($meta->hail_rear_shingles_damaged) || isset($meta->hail_left_shingles_damaged)
                || isset($meta->hail_right_shingles_damaged)) {
                $output = '<ul class="damageUL">';
                $damages = ['North (Front)' => isset($meta->hail_front_shingles_damaged) ? $meta->hail_front_shingles_damaged : false,
                    'South (Rear)' => isset($meta->hail_rear_shingles_damaged) ? $meta->hail_rear_shingles_damaged : false,
                    'East (Right)' => isset($meta->hail_right_shingles_damaged) ? $meta->hail_right_shingles_damaged : false,
                    'West (Left)' => isset($meta->hail_left_shingles_damaged) ? $meta->hail_left_shingles_damaged : false
                ];
                foreach ($damages as $label => $value) {
                    if ($value) {
                        $li = '<li class="damageLi">During our inspection of the <span class="redTxt">' . $label . '</span> facing scope 
                                we found <span class="red">' . $value . ' </span> hail-damaged shingles.</li>';
                        $output .= $li;
                    }
                }

                $output .= '</ul>';
                echo $output;
            } else {
                echo '<p>There was no hail damage found during our inspection.</p>';
            }

        ?>
        <!-- End hail damage -->

        <!-- Inspection Summary -->
        <h4 class="row-header">INSPECTION SUMMARY</h4>
        <?php
            if (isset($meta->general_comments)) {
                echo '<p>' . $meta->general_comments . '</p>';
            }
        ?>
        <!-- Inspection Summary -->

    </div>
</div>
</body>
</html>