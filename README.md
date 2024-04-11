# Mailenable Log Reader

This is an initial project to load and display Mailenable logs using PHP on a separate Ubuntu server that runs Apache (it's what I'm most familiar with). Since .NET and IIS are installed on the Mailenable server, a separate project using C#/Razor/Blazor is in the works. 

This set of PHP programs will summarize the IP addresses with the highest number of hits to the Mailenable server. It currently looks at the SMTP logs, but can be easily changed to look at IMAP and POP logs.

<b><u>PLEASE NOTE:</u></b> This is not integrated with a Mailenable Windows server's firewall to stop brute force attacks; my mail Enable server is hosted on Microsoft Azure and I update the network security group to protect against incoming attacks. If you need brute force protection, check out https://github.com/ljans/shielding. I haven't tried this myself, but it looks promising.

## Details
There are six parts to this project:
1. index.php
2. mail_logreader_v2.php
3. logreaderapp.json
4. mailenable.js
5. mail_update_logreaderappjson.php
6. Geolocate module (please see another one of my projects [Github Geolocate](https://github.com/mosterho/GeoLocate))

### index.php
This is the initial PHP to call to load and display Mailenable logs. The call to the website contains two arguments:
1. Number of entries to display, and;
2. Number of logs to read

Please see the example link below. It will display the top 10 entries read from the 5 most recent logs (not necessarily the last 5 days!):

https://mail.example.com/index.php?arg_entries=10&arg_numberoflogs=5

The index.php will instantiate a class from the mail_logreader.php script as well as the Geolocate API. Once these are set, the code will read the entries from the mail_logreader.php class. For each row read, it will retrieve the Geolocation information for the IP via the Geolocate API class. 

Once the data are collected, they are output via PHP "echo" functions to produce HTML. Once the page is rendered, there are two buttons that appear near the headings: "sort by count" and "sort by last update". The page will initially display data that is sorted in descending order by count. Clicking on the "sort by count" button will refresh the page with updated counts. CLicking on the "sort by last update" button will also read the most current data and display the rows in descending order by last update date. There is a "Copy IP" button that will copy the IP to the clipboard (note: this works only if the URL uses secure https:). The "blacklisted?" column displayes "YES" if the IP address is in the "blacklist" section of the logreaderapp.json file. The button on the far right will display one of four options:
* Add to Blacklist
* Remove from Blacklist
* Added to blacklist, refresh page (disabled)
* Removed from Blacklist, refresh page (disabled)

Once a "add/remove" button is clicked, hitting either the "sort by count" or "sort by last update" button will refresh the page. Clicking on the "Add to Blacklist" button doesn't do anything other than add the IP address to the logreaderapp.json "blacklist" section.


### mail_logreader_v2.php 
This is the workhorse of this project. This will read the SMTP logs in the Mailenable system.

The __construct will read the logreaderapp.json file. The JSON file contains multiple sections, some of them are duplicates: method1 and 2, system, path1 and 2, user, password, password key file, whitelist, and blacklist. Please see the logreaderapp.json section for details.

Once the JSON file is loaded, the fct_readdir function must be called with the two arguments specified in the web link. The fct_readdir will retrieve the directory name found in the logreaderapp.json file. It will then scan the directory, parsing out the SMTP log file names beginning with "SMTP-Activity". For each SMTP log file, it will then call the fct_readfile.

The fct_readfile function will read the log file data from the name passed in as arguments from fct_readdir. The IP address is broken down via regex, looking for the first three octets. The string '0/24' is tacked on to the three octets, which is then tested to see if it is in the whitelist section of the JSON file. If not, a counter is set and incremented by one. The date of the entry is compared to the most recent obtained. If the most recent entry's date is greater than the "hold" date, the "hold" date is replaced. The IP address is then checked against the blacklist. An array in the class will contain the IP address, counter, lmost recent date, and blacklist flag (T/F).

### logreaderapp.json
The logreaderapp.json file contains the following entries. <b><u>Please note that "method1" or "method2" must be renamed to "method", depending on which mode you require. The same goes for "path1" and "path2".</u></b> I included both methods and paths for documentation only. In fact, you can delete the method and path entries that you don't use.
- method1: contains the beginning of the path to access a remote file using SSH.
- method2: contains the beginning of the path to access a remote file using FTPS. 
- system: This is the system that contains the files. It can be reference with either a URL or IP address
- path1: This contains the FTP path to the log files; this example references the full path on a windows 2019 server.
- path2: This contains the FTP path to the log files; this example references the virtual directory path on a windows 2019 server.
- user: user name for SSH or FTP connections.
- password: password for the user name above OR to work with the password key file (see next item).
- password key file: This is optional. This is a separate project (which is in testing mode) to simulate having a key file in a separate folder, similar to certificate key files. The password in the key file is hashed. If this is blank, user and password must be filled-in.
- whitelist: This contains the IP addresses to ignore.
- blacklist: This contains the list of black listed IP addresses. You may not need any entries in this section of the JSON file; I use it to work with my load balancer/reverse proxy and Microsoft Azure server's network security group.

The logreaderapp.json file looks like this (the server, pwd_key, and blacklist sections are changed). Note the "\\" for the the directory names when the path is on a Windows server:
<pre>
{
    "method1": "ssh2.sftp://",
    "method2": "ftps://",
    "system": "mail.example.com",
    "path1": "C:\\Program Files (x86)\\Mail Enable\\Logging\\SMTP\\",
    "path2": "/ftp_mailenable_virtdir/SMTP/",
    "user": "user",
    "pwd": "password",
    "pwd_key": "/Encryption/test/mailenable_logreader/key01.json",
    "whitelist": [
        "10.0.0.0/8",
        "10.3.0.0/24",
        "10.126.26.0/24",
        "127.0.0.0/24",
        "172.16.0.0/16",
        "192.168.0.0/24",
        "192.168.1.0/24"
    ],
    "blacklist": [
        "111.111.111.0/24"
    ]
}
</pre>

### mailenable.js
The Javascript portion of this project handles the button clicks on the header section and detail lines. It also calls the mail_update_logreaderappjson.php program to update the logreaderapp.json file based on IP button that is clicked.

### mail_update_logreaderappjson.php
This PHP program is backend processing only. it will read, add, and delete entries in the logreaderapp.json file.

### Sample Output
#### Sort by Count
![Sample Output Sort by Count](Sample_webpage_4.JPG)

#### Sort by Last Update
![Sample Output Sort by Last Update](Sample_webpage_5.JPG)