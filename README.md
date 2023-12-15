
# uholidays

uholidays is a web service which provides the current list of university holidays, from the source of Hong Kong government and the tradition of the University of Hong Kong.

**1. Dependency**

	https://github.com/u01jmg3/ics-parser

**2. Installation**

	Copy the folder uholidays to a path of your web server.

**3. Show Holidays**  

	URL: [http://www.fbe.hku.hk/webform/uholidays/index.php](http://www.fbe.hku.hk/webform/uholidays/index.php)   
	It will simply show the list of university holiday in JSON data format. For example:
	  [
			{
			 "date": "20180925",
			 "name": "The day following the Chinese Mid-Autumn Festival",
			 "day": "Tuesday",
			 "type": "FULL",
			 "source": "gov"
			},
			{
			 "date": "20181001",
			 "name": "National Day",
			 "day": "Monday",
			 "type": "FULL",
			 "source": "gov"
			},
			{
			 "date": "20181224",
			 "name": "Christmas Eve (University holiday) - to be confirmed",
			 "day": "Monday",
			 "type": "FULL",
			 "source": "hku"
			},
	  ]

**4. Check/Find Holidays**  

	We can check whether a date is university holiday or not by calling the following function:
	
	**checkHoliday($date_str)**  
	​ Input: $date_str is in the format of yyyymmdd  
	 Output:
		 true when it is a holiday
		false when it is not a holiday

	**Example:**
	[**http://www.fbe.hku.hk/webform/uholidays/test_check.php**](http://www.fbe.hku.hk/webform/uholidays/test_check.php)
	​  
	<?php
		$show_json = 0;
		include "./index.php";
		var_dump(checkHoliday('20180925')); // will show true
		var_dump(checkHoliday('20180926')); // will show false
	?>

	We can find the information of a university holiday calling the following function:

	**findHoliday($date_str)**  
	​	Input:
			$date_str is in the format of yyyymmdd  
		Output:
			an object of a holiday
			null when it is not a holiday

			**Example:**

	[http://www.fbe.hku.hk/webform/uholidays/test_find.php](http://www.fbe.hku.hk/webform/uholidays/test_find.php)  

	<?php
	  $show_json = 0;
	  include "./index.php";
	  print_json(findHoliday('20180925'));
	  /*
	    // will show the following
	    {
	    "date": "20180925",
	    "name": "The day following the Chinese Mid-Autumn Festival",
	    "day": "Tuesday",
	    "type": "FULL",
	    "source": "gov"
	    }
	  */
	  print_json(findHoliday('20180926')); // will show null
	?>  


**5. Holiday Update**  

	When using the include file each time, it will check a stored file first. when the file contains equal to less than a number of records, it will update the list from the following government web page:  

	[http://www.1823.gov.hk/common/ical/gc/en.ics](http://www.1823.gov.hk/common/ical/gc/en.ics)  

	The output of the iCal events will be converted to a list of holidays and saved as a file for retrieval next time.  

**6. University Holidays**  

	The following university holidays will be added to the list. Note that no extra holidays will be allocated when it is on Saturday or Sunday.
	 - The day preceding Lunar New Year's Day (PM)  
	 - University Foundation Day (Full day of 16 March)  
	 - Christmas Eve (Full day of 24 December)  
	 - New Year's Eve (PM of 31 December)​     

**​7. Remarks**

	The current server at www.fbeitt.hku.hk seems unable to read from any external website by php  **cURL/file_get_contents** functions. However, the JSON file stored in the folder contains the holidays until  **31 Dec 2019**. Before that time, we should be able to migrate to the automated system.  

	It is recommended to install this service to another server with proper_ **_file_get_contents_** _functions._  

	$REFRESH_COUNTER (default = 3) located in the  **index.php**  is to control when the iCal file will be read. When there are just $REFRESH_COUNTER of future holidays remaining in the current list, the system will read the iCal from the website again.
