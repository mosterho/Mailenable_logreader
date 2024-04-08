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
  public $ftp_method;
  public $ftp_system;
  public $app_path;
  public $app_user;
  public $app_pwd;
  public $app_pwd_key;
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
    $this->ftp_method     = $JSONdata['method'];
    $this->ftp_system     = $JSONdata['system'];
    $this->app_path       = $JSONdata['path'];
    $this->app_user       = $JSONdata['user'];  ## default user
    $this->app_pwd        = $JSONdata['pwd'];   ## default password if pwd_key is empty
    $this->app_pwd_key    = $JSONdata['pwd_key'];  ## contains path and JSON filename for user and password
    $this->wrk_whitelist  = $JSONdata['whitelist'];
    $this->wrk_blacklist  = $JSONdata['blacklist'];

    if ($this->app_pwd_key != '' and $this->app_user == '') {
      $file_name = '/home/ESIS/Encryption/basic_encryption_user.php';
      if (is_file($file_name)) {
        include $file_name;
      } else {
        echo PHP_EOL . 'Something went wrong with include for encryption module';
        return;
      }
      $encryption_data = file_get_contents($this->app_pwd_key);
      $encryption_data_decoded = json_decode($encryption_data, true);
      $work_cls_encryption = new cls_encryption($encryption_data_decoded['password'], $encryption_data_decoded['hash']);
      $work_cls_encryption->fct_password_verify();
      $this->app_user = $encryption_data_decoded['user'];
      $this->app_pwd = $work_cls_encryption->password;
    }
  }

  ### Function to read the directory share to obtain the log files to read.
  ### As each log file is determined, call another function to summarize
  ### each IP address that is encountered.
  ### Allow for defaults for arguments if the class is instantiated from a separate program.
  function fct_readdir($argentryfiles = PHP_INT_MAX, $arg_button_hit = 'btn_sortbycount')
  {
    $this->nbr_entryfiles = $argentryfiles;

    ## Using ftp_ssl_connect is tricky, need firewall setup correctly on server due to control and data channels, 
    ## including random ports, etc.
    ## also set app_path correctly (/SMTP/SMTP-Activity*.log)
    $ftp_conn = ftp_ssl_connect($this->ftp_system);
    $ftp_conn_result = ftp_login($ftp_conn, $this->app_user, $this->app_pwd);
    ftp_pasv($ftp_conn, true);
    $file_list = ftp_nlist($ftp_conn, $this->app_path);

    # read each entry that contains a log file name.
    foreach ($file_list as $direntry) {
      $this->fct_readfile($direntry);
      # Increment counter to jump out of loops
      $this->wrk_nbr_of_files_read++;
      # if the number of files read is equal to the argument,
      # break out of foreach loop
      if ($this->wrk_nbr_of_files_read >= $this->nbr_entryfiles) {
        break;
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


  ### Function to read a log's data and update the class's data array
  function fct_readfile($arg_file_input)
  {
    $const_tab = chr(0x09);  // Tab key (not really a constant variable)
    ## create connection for file open, for example:
    ## ftps://user:password@servername/ftp_folder/SMTP-Activity-240402.log
    $work_connect = $this->ftp_method . $this->app_user . ':' . $this->app_pwd . '@' . $this->ftp_system . $arg_file_input;
    //echo $work_connect.'<br>';
    $myfile = fopen($work_connect, "r") or die("Unable to open " . $work_connect . " file!");
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
###  Uncomment the mainline code for debugging only.
#######################################################################

### Instantiate a new class. Call the function that reads the directory entries.
### The class will keep track of the array data.
//$cls_logs = new cls_logdata();
//$cls_logs->fct_readdir(5, 10);
//var_dump($cls_logs->array_data);

?>