<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);

$url = "http://www.1823.gov.hk/common/ical/gc/en.ics";
$REFRESH_COUNTER = 3;
//$url = "en.ics";
//$url = "ics_HK.ics";

//
// https://github.com/u01jmg3/ics-parser
// https://intranet.cs.hku.hk/csintranet/contents/general/shared/holidays.jsp
// https://www.1823.gov.hk/eng/blog/calendar.aspx
// release date: Ends/Friday, May 8, 2015 HKT 12:15
// ref: https://www.labour.gov.hk/eng/news/holidays_list.htm
//

require_once './vendor/autoload.php';
use ICal\ICal;

function wlog($s){
  //echo $s . "\r\n";
  echo $s . "<br/>";
}

function print_json($json){
	//echo "<pre>".json_encode($json, JSON_PRETTY_PRINT)."</pre>";
  wlog('<pre>'.json_encode($json, JSON_PRETTY_PRINT).'</pre>');
}

///////////////////////////////////////////////////////////////////////

function json_clone($obj, $asso = FALSE){
	return json_decode( json_encode($obj), $asso);
}

///////////////////////////////////////////////////////////////////////

function getQS($name){
	global $test_qs;
	if (isset($test_qs)){
		if (isset($test_qs[$name])){
			return $test_qs[$name];
		} else {
			return '';
		}
	} else if (isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	} else {
		//echo "missed $name<br/>";
		return '';
	}
	//return isset($_REQUEST[$name]) ? $_REQUEST[$name] : '';
}

///////////////////////////////////////////////////////////////////////

function findPrevDate($date_str){
  $date_obj = new DateTime($date_str);
  return $date_obj->modify('-1 day')->format("Ymd");
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

function isPastDate($date_now_obj, $date_str){
  $date_obj = new DateTime($date_str);
  $diff = $date_now_obj->diff($date_obj);
  $days = $diff->days * ( $diff->invert ? -1 : 1);
  return $days < 0;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

function addHoliday(&$holidays, &$calendar_years, $date_now_obj, $date_str, $holiday_name, $holiday_type, $source){
  // check if it is past date
  if (isPastDate($date_now_obj, $date_str)){
    return;
  }

  // the day of the week
  $date_obj = new DateTime($date_str);
  $day = $date_obj->format('l');
  if ($source == "hku" && ($day == "Saturday" || $day == "Sunday")){
    // ignore the saturday and sunday
    //$holiday_name .= '***'; // for tesitng only
    return;
  }

  // add year to calendar years
  $year = $date_obj->format('Y');
  $calendar_years[$year] = true;
  $holidays[] = [
    "date" => $date_str,
    "name" => $holiday_name,
    "day" => $day,
    "type" => $holiday_type,
    "source" => $source,
  ];
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

function sort_holiday($a, $b){
  $a1 = $a['date'];
  $b1 = $b['date'];
  if ($a1 == $b1){
    return 0;
  } else if ($a1 > $b1){
    return 1;
  } else {
    return -1;
  }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////
// date format: yyyymmdd
/////////////////////////////////////////////////////////////////////////////////////////////////////////
function checkHoliday($date_str){
  global $holidays;
  $output = findHoliday($date_str);
  if ($output != null){
    $output = true;
  } else {
    $output = false;
  }
  return $output;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

function findHoliday($date_str){
  global $holidays;
  $output = null;
  forEach ($holidays as $holiday){
    if ($date_str == $holiday['date']){
      $output = $holiday;
      break;
    }
  }
  return $output;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////

// global variables
$forceTimeZone = false;
$date_now_str = 'now';
//$date_now_str = '20201225';
$date_now_obj = new DateTime($date_now_str);
$calendar_years = [];
$holidays = [];
//exit;

// read from file
$file_json = getcwd() . "/holiday.json";
if (file_exists($file_json)){
  $holidays_orig = json_decode(file_get_contents($file_json), TRUE);
  //print_json($holidays_orig); exit;
  forEach ($holidays_orig as $holiday){
    $date_str = $holiday['date'];
    if (!isPastDate($date_now_obj, $date_str)){
      $holidays[] = json_clone($holiday, TRUE);
    }
  }
}

// read and extract event from gov ical
if (sizeof($holidays) <= $REFRESH_COUNTER){
  $holidays = [];
  try {
    $ical = new ICal($url, array(
      'defaultSpan'                 => 2,     // Default value
      'defaultTimeZone'             => 'UTC',
      'defaultWeekStart'            => 'MO',  // Default value
      'disableCharacterReplacement' => false, // Default value
      'skipRecurrence'              => false, // Default value
      'useTimeZoneWithRRules'       => false, // Default value
    ));
  } catch (\Exception $e) {
      die($e);
  }
  if (sizeof($ical->events()) > 0){
    $events_ical = $ical->sortEventsWithOrder($ical->events());
    forEach ($events_ical as $event_ical){
      $date_str = $event_ical->dtstart;
      addHoliday($holidays, $calendar_years, $date_now_obj, $date_str, $event_ical->summary, 'FULL', 'gov');
    }
    //print_json($calendar_years);

    // finding for "Lunar New Year's Day" in the event
    forEach ($holidays as $holiday){
      //$holiday_name = $holiday['name'];
      $holiday_name = $holiday['name'];
      switch ($holiday_name){
        case "Lunar New Year's Day":
          $date_str = findPrevDate($holiday['date']);
          addHoliday($holidays, $calendar_years, $date_now_obj, $date_str, "The day preceding Lunar New Year's Day (University Holiday)", 'PM', 'hku');
          break;
      }
    }

    // adding other university holidays
    forEach($calendar_years as $year => $temp){
      addHoliday($holidays, $calendar_years, $date_now_obj, $year."0316", "University Foundation Day", 'FULL', 'hku');
      addHoliday($holidays, $calendar_years, $date_now_obj, $year."1224", "Christmas Eve (University holiday) - to be confirmed", 'FULL', 'hku');
      addHoliday($holidays, $calendar_years, $date_now_obj, $year."1231", "New Year's Eve (University holiday) - to be confirmed", 'PM', 'hku');
    }
    // sorting
    usort($holidays, 'sort_holiday');

    // write to file
    file_put_contents($file_json, json_encode($holidays));
  }
}

if (!isset($show_json) && getQS('show_json') != ''){
  $show_json = getQS('show_json');
}
if (!isset($show_json) || $show_json != 0){
  print_json($holidays);
}


?>
