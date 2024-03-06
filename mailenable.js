// after clicking on PHP html "on_click" for an individual IP to add/remove blacklist entries.
function fct_button_click_IPaddremove(arg_IP, arg_blacklisted) {
  let wrk_button = "btn-" + arg_IP;
  if (!arg_blacklisted) {
    document.getElementById(wrk_button).innerHTML = "Added to Blacklist, refresh page";
    document.getElementById(wrk_button).className = 'btn btn-warning';
    fct_JSON_update(arg_IP, 'add');  // 'add' means add to blacklist
  }
  else {
    document.getElementById(wrk_button).innerHTML = "Removed from Blacklist, refresh page";
    document.getElementById(wrk_button).className = 'btn btn-light';
    fct_JSON_update(arg_IP, 'delete');
  }
  //console.log(document.getElementById(wrk_button));
  document.getElementById(wrk_button).disabled = true;
}

function fct_JSON_update(parm_IP, parm_adddelete) {
  // depending on 2nd parameter, add or delete IP from blacklist in JSON file
  const xhttp = new XMLHttpRequest();
  work_xhttp_tosend = 'mail_update_logreaderappjson.php';
  work_xhttp_arglist = '?arg_IP=' + parm_IP + '&arg_action=' + parm_adddelete;
  work_xhttp_tosend += work_xhttp_arglist;
  xhttp.onload = function () {
    work_xhttp_responseText = this.responseText;
  }
  xhttp.open("GET", work_xhttp_tosend);
  xhttp.send();
}
 
function fct_button_click_copyIP(parm_IP) {
  // Get the IP address
  let wrk_button = "IP-" + parm_IP;
  var copyText = document.getElementById(wrk_button).innerHTML;
  console.log(copyText);
  // clipboard function doesn't work if not using HTTPS.
  // Uncaught TypeError: Cannot read properties of undefined (reading 'writeText')
  navigator.clipboard.writeText(copyText);
}

