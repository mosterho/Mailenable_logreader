<?php

include 'mail_logreader.php';
include '/home/ESIS/GeoLocate/geolocate_API.php';

$work_logentries = $_GET['arg_numberoflogs'];
$work_IPS = $_GET['arg_entries'];

### Instantiate a new load reader class. Call the function that reads the directory entries.
### The class will keep track of the array data.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($work_logentries, $work_IPS);

$cls_geolocate = new cls_geolocateapi();

//var_dump($cls_logs);

echo '
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Mailenable Log Reader will track the IPs that hit the server the most via SMTP">
        <meta name="author" content="Marty Osterhoudt">
        <meta name="generator" content="Hugo 0.79.0">
        <title>Mailenable Log Reader</title>
        <!-- Bootstrap core CSS -->
        <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <!-- Custom styles for this template -->
        <link href="grid.css" rel="stylesheet">
    </head>
';

echo '
    <body class="py-4">
        <main>
            <div class="container">
                <h1>Mailenable Log Reader</h1>
                <p class="lead">Display IPs that connect with the Mailenable server via SMTP</p>
';

echo '
<div class="row">
<div class="col-2">
';
echo 'IP Address';
echo '</div>';

echo '
<div class="col-4">
';
echo 'IP Geolocation information';
echo '
</div>
';

echo '
<div class="col-1 text-end">
';
echo 'Counter';
echo '
</div>
';

echo '
<div class="col-2">
';
echo 'Last Date/time';
echo '
</div>
';

echo '
<div class="col-1">
';
echo 'Blacklist?';
echo '
</div>
';

echo '
</div>
';

$work_counter = 0;
foreach ($cls_logs->array_data as $work_key => $work_data) {

    $work_counter += 1;
    if ($work_counter < $work_IPS) {
        
        $arg_ip = substr_replace($work_key,'1',-4);  // Replace '0/24' with '1'
        // Retrieve and display certain IP Geolocate info. 
        $cls_geolocate->fct_retrieve_IP_info($arg_ip);
        $work_IPinfo = $cls_geolocate->response;  // Response contains IP info as a string in JSON format
        $work_IPinfo_JSON =json_decode($work_IPinfo, True);  // "True" creates an associative array
        // String together some attributes of the IP information for output.
        $work_IPinfo_text = $work_IPinfo_JSON["country_name"] . ', ' . $work_IPinfo_JSON["region_name"] . ', ' . $work_IPinfo_JSON["city_name"] . ', ' . $work_IPinfo_JSON["as"] ; 

        // Set blacklist flag
        if($work_data[2] == 1){
            $dsp_blacklist = 'YES';
        }
        else {
            $dsp_blacklist = '';
        }

        // Output info
        // IP address
        echo '
        <div class="row">
        <div class="col-2">
        ';
        echo $work_key;
        echo '
        </div>
        ';

        // IP geolocate info
        echo '
        <div class="col-4">
        ';
        echo $work_IPinfo_text;
        echo '
        </div>
        ';
        
        // Counter
        echo '
        <div class="col-1 text-end">
        ';
        echo $work_data[0];
        echo '
        </div>
        ';

        // Laste date/time
        echo '
        <div class="col-2">
        ';
        echo $work_data[1];
        echo '
        </div>
        ';

        // Blacklist?
        echo '
        <div class="col-1">
        ';
        echo $dsp_blacklist;
        echo '
        </div>
        ';

        echo '
        </div>
        ';
    }
}

echo '
        </div>
        </main>
        <script src="../assets/js/popper.min.js"></script>
        <script src="../bootstrap/js/bootstrap.min.js"></script>
    </body>
</html>
';

?>