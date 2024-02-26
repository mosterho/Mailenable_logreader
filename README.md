# Mailenable Log Reader

*** This is an initial project to load and display Mailenable logs using PHP on a separate Ubuntu server that runs Apache (because it's what I'm most familiar with). Since .NET and IIS are installed on the Mailenable server, a separate project using C#/Razor/Blazor is in the works. 

This git contains PHP and Bootstrap 5 CSS to display abusive incoming IP addresses. This is not integrated with a Mailenable Windows server's firewall to stop brute force attacks; I use a Progress/Kemp load balancer/reverse proxy to protect against incoming attacks. 

This set of PHP programs will summarize the IP addresses with the highest number of hits to the Mailenable server. It currently looks at the SMTP logs, but can be easily changed to look at IMAP or POP logs.

If you need automatic brute force protection, check out https://github.com/ljans/shielding. I haven't tried this myself, but it looks promising.

There are four parts to this project:
1. index.php
2. mail_logreader.php
3. logreaderapp.json
4. Geolocate module (please see another one of my projects [Github Geolocate](https://github.com/mosterho/GeoLocate))

### index.php
This is the initial PHP to call to load and display Mailenable logs. The call to the website contains two arguments:
1. Number of entries to display, and;
2. Number of logs to read

An example of the link I use is as follows. It will display the top 10 entries read from the 5 most recent logs (not necessarily the last 5 days!):

http://ubuntu20desktop03.marty.local:8099/index.php?arg_entries=10&arg_numberoflogs=5

The index.php will instantiate a class from the mail_logreader.php script as well as the Geolocate API. Once these are set, the code will read the entries from the mail_logreader.php class. For each row read, it will retrieve the Geolocation information for the IP via the Geolocate API class. 

Once the data are collected, they are output via PHP "echo" functions that mimic HTML.

### mail_logreader.php 
This is the workhorse of this project. This will read the SMTP logs in the Mailenable system (FTP must be setup on the Mailenable system). 

The __construct will read the logreaderapp.json file. The JSON file contains the  three sections: path, whitelist, and blacklist. Please see the logreaderapp.json section for details.

Once the JSON file is loaded, the fct_readdir function must be called with the two arguments specified in the web link. The fct_readdir will retrieve the directory name found in the logreaderapp.json file. It will then scan the directory, parsing out the SMTP log file names via the fct_regex_results function. The regex looks for log file names such as "SMTP-Activity-240212.log". For each SMTP log file, it will then call the fct_readfile.

The fct_readfile function will read the log file data from the name passed in as arguments from fct_readdir. The IP address is broken down via regex, looking for the first three octets. The string '0/24' is tacked on to the three octets, which is then tested to see if it is in the whitelist section of the JSON file. If not, a counter is set (or incremented by one). The date of the entry is compared to the most recent obtained. If the most recent entry's date is greater than the "hold" date, the "hold" date is replaced. The IP address is then checked against the blacklist. An array in the class will contain the IP address, counter, lmost recent date, and blacklist flag (T/F).

### logreaderapp.json
The logreaderapp.json file contains the following:
- path: This contains the FTP path to the log files;
- whitelist: This contains the IP addresses to ignore, and;
- blacklist: This contains my list of black listed IP addresses. You may not need any entries in this section of the JSON file. I use it to work with my load balancer/reverse proxy.

A portion of my logreaderapp.json file looks like this (the IP addresses in the path and blacklist sections are changed):
{
  "path": "ftp://10.10.10.10/SMTP/",
    "whitelist": [
    "10.0.0.0/8",
    "172.16.0.0/16",
    "192.168.0.0/24",
    "192.168.1.0/24"
  ],
  "blacklist": [
    "999.888.777.0/24",
    "999.888.666.0/24"
  ]
}


### Sample Output

![Sample Output](/Mailenable_logreader/Sample_webpage.JPG)