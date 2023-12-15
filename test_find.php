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
print_json(findHoliday('20180926'));  // will show null
?>
