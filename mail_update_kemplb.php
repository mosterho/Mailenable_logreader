<?php

###  This program will (hopefully) add/remove entries
###  directly onto the Progress/Kemp load balancer 
###  packet routing filter blacklist.

#######################################################################
### Begin mainline
#######################################################################

### Accept arguments, determine the IP address and action.

if (isset($_GET['arg_IP'])) {
    $wrk_IP = $_GET['arg_IP'];
}
if (isset($_GET['arg_action'])) {
    $wrk_action = $_GET['arg_action'];
}

$ch = curl_init();
if (!$ch) {
    die("<br>Within mail_update_kemplb module, couldn't initialize a cURL handle");
}


if ($wrk_action == 'add') {
    $build_URL = "https://10.126.26.136/access/aclcontrol?add=black&addr=" . $wrk_IP;
} else if ($wrk_action == 'delete') {
    $build_URL = "https://10.126.26.136/access/aclcontrol?del=black&addr=" . $wrk_IP;
}

var_dump($build_URL);

curl_setopt($ch, CURLOPT_URL, $build_URL);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_FAILONERROR, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_USERPWD, 'bal:gzK$9yZPn^XoTdKV');

$response = curl_exec($ch);
curl_close($ch);

echo '<br>';
var_dump($response);

?>