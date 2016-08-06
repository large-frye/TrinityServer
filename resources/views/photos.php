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
        .damageLi{

            font:15px;
        }
        .imgContainer{
            width:450px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        .parentCatHead { margin-top: 30px; margin-bottom:20px; border-bottom: 1px solid black; }
        .imgCl{
            margin-bottom:20px;

        }
        p.lower { position: relative; top: 60px; }
        ul { padding-left: 15px; }
        li { padding: 10px; position: relative; left: 2em;}
        .page-break { page-break-after: always; }
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
        .imgCl { position: relative !important; left: 60px !important; margin-top: 20px !important;}
        .imgCl span {
            margin-bottom: 20px !important;
        }
    </style>
</head>
<body>
<div id="page-wrap">

    <!-- Photos -->
    <?php

        $pageBreak = false;
        $count = 0;
        $index = 0;

        foreach ($photos as $photo) {

            if (!isset($lastParentId) || $lastParentId != $photo->c1_id) {
                if (isset($lastParentId) && $lastParentId != $photo->c1_id)
                    echo '</div>';

                $break = '<div class="page-break"></div>';
                $header = '<div class="imgDiv"><h3 class="parentCatHead">' . $photo->c1_name . '</h3>';

                if (!$pageBreak && $count != 0) {
                    $header = $break . $header;
                }


                echo $header;

                $count = 0;
                $pageBreak = false;
            }

            $count++;

            echo '<div class="imgCl"><span>' . $photo->label . '</span><br>' .
                '<img class="photoImgView" src="' . $photo->file_url . '" style="width:600px;height:400px;position:relative;left:-100px;top:20px;margin-top:20px;" /></div>';

            if ($count % 2 == 0 && $index + 1 < count($photos)) {
                $count = 0;
                echo '<div class="page-break"></div>';
                $pageBreak = true;
            }

            $lastParentId = $photo->c1_id;
            $index++;
        }

        echo '</div>';
    ?>
    <!-- Photos -->

</div>
</body>
</html>