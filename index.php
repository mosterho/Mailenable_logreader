<?php

include 'mail_logreader.php';
include '/home/ESIS/GeoLocate/geolocate_API.php';

## Set the default "button hit" to sort by count descending (number of hits).
## Retrieve the two argument values from the link to determine if there's a change
## in the number of IPs to display or logs to read.
## Check if the "sort by date" button was pressed on a prevously rendered page.
$button_hit = 'btn-sortbycount';
$work_logentries = $_GET['arg_numberoflogs'];
$work_IPS = $_GET['arg_entries'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['btn-sortbydate']) {
        $button_hit = 'btn-sortbydate';
    }
}

### Instantiate a new log reader class from "mail_logreader.php". Call the function that reads the directory entries.
### The class will keep track of the data.
$cls_logs = new cls_logdata();
$cls_logs->fct_readdir($work_logentries, $button_hit);

## Instantiate a Geolocate class (geolocate_API.php) to display location information.
$cls_geolocate = new cls_geolocateapi();


echo '
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="This webpage will display SMTP hits to the Mailenable server. The data are loaded in a separate PHP object/class \'mail_logreader.php\'">
        <meta name="author" content="Marty Osterhoudt">
        <title>Mailenable Log Reader</title>
        <!-- Bootstrap core CSS -->
        <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <!-- Custom styles for this template -->
        <link href="style.css" rel="stylesheet">
        <script src="mailenable.js"></script>
    </head>
';

echo '
<body>
<form method="post">
    <div class="container">
        <h1>Mailenable Log Reader</h1>
        <h3>Display IPs that connect with the Mailenable server via SMTP</h3><br>
        <div class="btn-group" role="group" aria-label="Basic example"> 
            <button type="submit" class="btn btn-secondary" id="btn-sortbycount" name="btn-sortbycount" value="btn-sortbycount" >Sort by Count</button>                     
            <button type="submit" class="btn btn-secondary" id="btn-sortbydate" name="btn-sortbydate" value="btn-sortbydate" >Sort by Last Update</button>                     
        </div>
        <table class="table"> 
            <thead> 
                <tr>
                    <th scope="col">Copy IP</th> 
                    <th scope="col">IP Address</th> 
                    <th scope="col">IP Information</th> 
                    <th scope="col">Count</th> 
                    <th scope="col">Last Update</th>
                    <th scope="col">Black listed?</th>
                    <th scope="col"></th> 
                </tr>                         
            </thead> 
            <tbody>             
';

## Read the log reader class's array data, setup variables for display
$work_counter = 0;
foreach ($cls_logs->array_data as $work_key => $work_data) {
    $work_counter++;   // Track the number of IPs to display
    if ($work_counter <= $work_IPS) {
        $arg_ip = substr_replace($work_key, '1', -4);  // Replace 4th octet of IP '0/24' with '1'
        ## Retrieve and display certain IP Geolocate info. 
        $cls_geolocate->fct_retrieve_IP_info($arg_ip);
        $work_IPinfo = $cls_geolocate->response;  // Response contains IP geolocate info in JSON format
        $work_IPinfo_JSON = json_decode($work_IPinfo, True);  // "True" creates an associative array
        ## String together some attributes of the IP Geolocation for output.
        $work_IPinfo_text = $work_IPinfo_JSON["country_name"] . ', ' . $work_IPinfo_JSON["region_name"] . ', ' . $work_IPinfo_JSON["city_name"] . ', ' . $work_IPinfo_JSON["as"];

        ## Set blacklist flag
        if ($work_data[2] == 1) {
            $dsp_blacklist = 'YES';
        } else {
            $dsp_blacklist = '';
        }

        ## Setup strings for copy button and IP address.
        $copybutton = '<td scope="row"><button type="button" class="btn btn-secondary" id="copybtn-' . $work_key . '" name="copybtn-' . $work_key . '" value="copybtn-' . $work_key . '"
        onclick="fct_button_click_copyIP(\'' . $work_key . '\')" >Copy IP</button></td>';
        $IP_string = '<td id="IP-' . $work_key . '" name=IP-"' . $work_key . '">' . $work_key . '</td>';
        echo '<tr> ';
        echo $copybutton;
        echo $IP_string;

        ## display Geolocate info, number of hits (count), last update date, and Blacklist flag
        echo ' 
        <td>' . $work_IPinfo_text . '</td> 
        <td class="text-end">' . $work_data[0] . '</td> 
        <td>' . $work_data[1] . '</td>
        <td>' . $dsp_blacklist . '</td> 
        ';

        ## Build a string to create a "blacklist" button
        $button_string = '<td> <button type="button"  id="btn-' . $work_key . '" name="btn-' . $work_key . '"  class=';
        if ($dsp_blacklist == '') {
            $button_string .= '"btn btn-light" onclick="fct_button_click_IPaddremove(\'' . $work_key . '\', false)">Add to Blacklist</button></td>';
        } else {
            $button_string .= '"btn btn-warning" onclick="fct_button_click_IPaddremove(\'' . $work_key . '\', true)">Remove from Blacklist</button></td>';
        }
        echo $button_string;
        ## Finish table row
        echo '</tr>';
    }
    ## if the counter reaches the number of IPs to display, break out of the foreach loop.
    else {
        break;
    }
}

echo '
</tbody>                     
</table>
</div>
<!-- Bootstrap core JavaScript
================================================== -->
<!-- Placed at the end of the document so the pages load faster -->
<script src="assets/js/popper.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</form>
</body>
</html>
';

?>