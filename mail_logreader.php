<?php

###########################################################################
### MAIL log reader (backend processing)
### This script will read the mail logs that contain TCPIP data
### and summarize the external IP addresses with the most TCPIP hits
### The results are placed in an associative array in the class
### key: IP address  data: {count, last update date, blacklist flag}
###########################################################################

#######################################################################
### Define class object and functions
#######################################################################

class cls_logdata
{
  public $array_data = array();
  public $app_path;
  public $nbr_entryfiles = 0;
  public $wrk_whitelist;
  public $wrk_blacklist;
  public $wrk_nbr_of_files_read = 0;


  ### __construct function of class to read the JSON file
  ### and setup application variables.
  function __construct()
  {
    $jsonstuff = file_get_contents("logreaderapp.json");
    $JSONdata = json_decode($jsonstuff, True);  # "True" will generate an associative array from JSON data
    $this->app_path       = $JSONdata['path'];
    $this->wrk_whitelist  = $JSONdata['whitelist'];
    $this->wrk_blacklist  = $JSONdata['blacklist'];
  }

  ### Function to read the directory share to obtain the log files to read.
  ### As each log file is determined, call another function to summarize
  ### each IP address that is encountered.
  ### Allow for defaults for arguments if the class is instantiated from a separate program.
  function fct_readdir($argentryfiles = PHP_INT_MAX, $arg_button_hit = 'btn_sortbycount')
  {
    $this->nbr_entryfiles = $argentryfiles;
    $dirlist = scandir($this->app_path, 1); # "1" will scan directory of files in descending order
    # read each entry that contains a log file name.
    foreach ($dirlist as $direntry) {
      $work_nbr_matches = preg_match_all('/SMTP-Activity-\d{2}\d{2}\d{2}.log/', $direntry, $regexresult);  # SMTP-Activity-240212.log
      //if($work_nbr_matches == 0){
      //$work_nbr_matches = preg_match_all('/IMAP-Activity-\d{2}\d{2}\d{2}.log/', $direntry, $regexresult);  # IMAP-Activity-240212.log
      //}
      if ($work_nbr_matches > 0) {
        $this->fct_regex_results($regexresult);
        # Increment counter to jump out of loops
        $this->wrk_nbr_of_files_read++;
        # if the number of files read is equal to the argument,
        # break out of foreach loop
        if ($this->wrk_nbr_of_files_read >= $this->nbr_entryfiles) {
          break;
        }
      }
    }

    ## sort the array data by count in descending order (the first column in array_data).
    ## the foreach is a technique from PHP.NET to sort a column other than the first one. 
    ## reminder: $key is IP address.
    foreach ($this->array_data as $key => $row) {
      $array_count[$key]  = $row[0];
      $array_datetime[$key] = $row[1];
    }
    ## Whern using array_multisort, MUST include the original "$this->array_data" as the last argument
    if ($arg_button_hit == 'btn-sortbycount') {
      array_multisort($array_count, SORT_DESC, $array_datetime, SORT_DESC, $this->array_data);
    } elseif ($arg_button_hit == 'btn-sortbydate') {
      array_multisort($array_datetime, SORT_DESC, $array_count, SORT_DESC, $this->array_data);
    }
  }


  ### Function to read regex results
  function fct_regex_results($arg_regex_result)
  {
    foreach ($arg_regex_result as $indfile) {
      if ($indfile != '') {
        $parm_file = $this->app_path . $indfile[0]; #concatenate system name/path with filename
        # Call the function that reads the log file and accumulates the counts of each IP /24 octet.
        $this->fct_readfile($parm_file);
      }
    }
  }


  ### Function to read a log's data and update the class's data array
  function fct_readfile($arg_file_input)
  {
    $const_tab = chr(0x09);  // Tab key (not really a constant variable)
    $myfile = fopen($arg_file_input, "r") or die("Unable to open '.$arg_file_input.' file!");
    while (!feof($myfile)) {
      $thisline = fgets($myfile);
      $work_explode = explode($const_tab, $thisline);
      $work_nbr_elements = count($work_explode);
      ## Find the date and time (mm/dd/yy hh:mm:ss)
      if ($work_nbr_elements >= 4) {
        $datein = $work_explode[0];
        $temp = $work_explode[4];  //IPv4 address
        ## If the section of string is found, narrow it down to obtain the IP address three octets
        if ($temp != '') {
          $regex_count = preg_match_all('/\d{1,3}.\d{1,3}.\d{1,3}/', $temp, $arrayresult3);
          ## For practical reasons, use the first 3 octets to get a /24 bit address for comparison (for now)
          if ($regex_count > 0) {
            $IPin = $arrayresult3[0][0] . '.0/24';
            ## If there is a valid IP that is not in the whitelist array, add/update the data.
            if (!in_array($IPin, $this->wrk_whitelist)) {
              ## Set default values for counter and date (in case of new IP entry)
              $tmpcounter = 1;
              $tmp_date = $datein;
              ## If the IP is already in the array, update the counter and the latest hit date.
              if (array_key_exists($IPin, $this->array_data)) {
                ## Update counter.
                $tmpcounter = $this->array_data[$IPin][0];  # Get current count for an IP
                $tmpcounter++;
                ## Update most recent "hit" date.
                if ($this->array_data[$IPin][1] > $datein) {
                  $tmp_date = $this->array_data[$IPin][1];
                }
              }
              if (in_array($IPin, $this->wrk_blacklist)) {
                $work_blacklist_flag = True;
              } else {
                $work_blacklist_flag = False;
              }

              $this->array_data[$IPin] = array($tmpcounter, $tmp_date, $work_blacklist_flag);  # Update the new count for an IP
            }
          }
        }
      }
    }
    fclose($myfile);
  }
}

#######################################################################
### End of class object
#######################################################################


#######################################################################
### Begin mainline
#######################################################################


### Instantiate a new class. Call the function that reads the directory entries.
### The class will keep track of the array data.
//$cls_logs = new cls_logdata();
//$cls_logs->fct_readdir($argentryfiles, $argentryIPs);

//var_dump($cls_logs->array_data);

?>