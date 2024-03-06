<?php

## This code is backend processing only. This will perform actions against the logreaderapp.json
## file. Any actions against the file are only made to the blacklist entries.
## When instantiating the class to maintain the blacklist entries, pass in two arguments:
## 1. an IP address in "0/24" (three octet) form, and
## 2. an action (read, add, delete).
## Note that if "read" is the action, the IP address is ignored.

class cls_logreaderapp
{
    public $wrk_IP = '';
    public $wrk_action = '';
    public $logfile = 'logreaderapp.json';
    public $parm_error_message = '';
    public $wrk_JSON_decode_filedata;
    public $wrk_JSON_encode_filedata;
    public $wrk_blacklist;

    function __construct($parm_IP = '', $parm_action = '')
    {
        $this->wrk_IP = $parm_IP;
        $this->wrk_action = $parm_action;
        if ($this->wrk_IP == '' and $this->wrk_action != 'read' or ($this->wrk_IP != '' and $this->wrk_action != 'read' and $this->wrk_action != 'add' and $this->wrk_action != 'delete')) {
            $this->parm_error_message = 'Issue with arguments passed to this __construct. File will be read and data placed into class/array';
        }
        $this->fct_read_file();
    }
    function fct_read_file()
    {
        $jsonstuff = file_get_contents($this->logfile);
        $this->wrk_JSON_decode_filedata = json_decode($jsonstuff, True);  # "True" will generate an associative array from JSON data
        $this->wrk_blacklist  = $this->wrk_JSON_decode_filedata['blacklist'];
    }
    function fct_insert_data()
    {
        array_push($this->wrk_blacklist, $this->wrk_IP);
        $this->fct_write_file();
    }
    function fct_delete_data()
    {
        $idx = array_search($this->wrk_IP, $this->wrk_blacklist);
        array_splice($this->wrk_blacklist, $idx);
        $this->fct_write_file();
    }
    function fct_write_file()
    {
        $this->wrk_JSON_decode_filedata['blacklist'] = $this->wrk_blacklist;
        $this->wrk_JSON_encode_filedata = json_encode($this->wrk_JSON_decode_filedata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        //echo $this->wrk_JSON_encode_filedata;
        file_put_contents($this->logfile, $this->wrk_JSON_encode_filedata);
    }
}
#######################################################################
### End of class object
#######################################################################

#######################################################################
### Begin mainline
#######################################################################

### Accept arguments, determine the IP address and action.
### use $_GET since this code is not using a form with method="POST".

if (isset($_GET['arg_IP'])) {
    $wrk_IP = $_GET['arg_IP'];
}
if (isset($_GET['arg_action'])) {
    $wrk_action = $_GET['arg_action'];
}

$class_logreaderapp = new cls_logreaderapp($wrk_IP, $wrk_action);

if ($class_logreaderapp->$parm_error_message == '') {
    if ($wrk_action == 'read') {
        return $class_logreaderapp->wrk_blacklist;
    } elseif ($wrk_action == 'add') {
        $class_logreaderapp->fct_insert_data();
    } elseif ($wrk_action == 'delete') {
        $class_logreaderapp->fct_delete_data();
    }
}

return $class_logreaderapp->parm_error_message;

?>