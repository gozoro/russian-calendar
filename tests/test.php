<?php

require_once __DIR__.'/../src/RussianCalendar.php';

$calendar = new \gozoro\russian_calendar\RussianCalendar('ru', __DIR__.'/cache');




print "test method checkFullWorkingDay(): ";

if(!$calendar->checkFullWorkingDay('01.12.2025'))
{
	die("FAIL: '01.12.2025' as string is not full working day\n");
}

if(!$calendar->checkFullWorkingDay(strtotime('01.12.2025')))
{
	die("FAIL: '01.12.2025' as timestamp is not full working day\n");
}

if(!$calendar->checkFullWorkingDay(new DateTime('01.12.2025')))
{
	die("FAIL: '01.12.2025' as DateTime is not full working day\n");
}

if($calendar->checkFullWorkingDay('01.12.2025', array(1)))
{
	die("FAIL: '01.12.2025' is not weekend when monday is weekend\n");
}

if($calendar->checkFullWorkingDay('06.12.2025'))
{
	die("FAIL: '06.12.2025' is weekend\n");
}

if($calendar->checkFullWorkingDay('31.12.2025'))
{
	die("FAIL: '31.12.2025' is weekend \n");
}

if($calendar->checkFullWorkingDay('07.03.2025'))
{
	die("FAIL: '07.03.2025' is not full working  day\n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method checkHoliday(): ";

if(!$calendar->checkHoliday('08.03.2025'))
{
	die("FAIL: '08.03.2025' as string is holiday\n");
}

if(!$calendar->checkHoliday(strtotime('08.03.2025')))
{
	die("FAIL: '08.03.2025' as timestamp is holiday\n");
}


if(!$calendar->checkHoliday(new DateTime('08.03.2025')))
{
	die("FAIL: '08.03.2025' as DateTime is holiday\n");
}


if(!$calendar->checkHoliday('01.01.2025'))
{
	die("FAIL: '01.01.2025' is holiday\n");
}

if($calendar->checkHoliday('31.12.2025'))
{
	die("FAIL: '08.03.2025' as string is holiday\n");
}

print "OK\n";



///////////////////////////////////////////////////////////////////////////////////

print "test method checkShortWorkingDay(): ";

if(!$calendar->checkShortWorkingDay('07.03.2025'))
{
	die("FAIL: '07.03.2025' as string is short working day\n");
}

if(!$calendar->checkShortWorkingDay(strtotime('07.03.2025')))
{
	die("FAIL: '07.03.2025' as timestamp is short working day\n");
}

if(!$calendar->checkShortWorkingDay(new DateTime('07.03.2025')))
{
	die("FAIL: '07.03.2025' as DateTime is short working day\n");
}

if(!$calendar->checkShortWorkingDay('11.06.2025'))
{
	die("FAIL: '11.06.2025' as string is short working day\n");
}

if($calendar->checkShortWorkingDay('10.06.2025'))
{
	die("FAIL: '10.06.2025' as string is not short working day\n");
}

print "OK\n";



///////////////////////////////////////////////////////////////////////////////////

print "test method checkWeekend(): ";

if(!$calendar->checkWeekend('01.01.2025'))
{
	die("FAIL: '01.01.2025' as string is weekend\n");
}

if(!$calendar->checkWeekend(strtotime('01.01.2025')))
{
	die("FAIL: '01.01.2025' as timestamp is weekend\n");
}

if(!$calendar->checkWeekend(new DateTime('01.01.2025')))
{
	die("FAIL: '01.01.2025' as DateTime is weekend\n");
}

if(!$calendar->checkWeekend('06.12.2025'))
{
	die("FAIL: '06.12.2025' as string is weekend\n");
}

if($calendar->checkWeekend('05.12.2025'))
{
	die("FAIL: '05.12.2025' as string is not weekend\n");
}

if(!$calendar->checkWeekend('06.12.2025', array(6,0)))
{
	die("FAIL: '06.12.2025' is weekend\n");
}

if($calendar->checkWeekend('06.12.2025', array(0)))
{
	die("FAIL: '06.12.2025' is not weekend when weekend only sunday\n");
}


print "OK\n";





///////////////////////////////////////////////////////////////////////////////////

print "test method checkWorkingDay(): ";

if(!$calendar->checkWorkingDay('05.12.2025'))
{
	die("FAIL: '05.12.2025' as string is working day\n");
}

if(!$calendar->checkWorkingDay(strtotime('05.12.2025')))
{
	die("FAIL: '05.12.2025' as timestamp is working day\n");
}

if(!$calendar->checkWorkingDay(new DateTime('05.12.2025')))
{
	die("FAIL: '05.12.2025' as timestamp is working day\n");
}

if($calendar->checkWorkingDay('06.12.2025'))
{
	die("FAIL: '06.12.2025' is not working day\n");
}

if($calendar->checkWorkingDay('07.12.2025', array(0,6)))
{
	die("FAIL: '07.12.2025' is not working day\n");
}

if(!$calendar->checkWorkingDay('07.12.2025', array(6)))
{
	die("FAIL: '07.12.2025' is not working day when sunday is not weekend \n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method getCacheDuration(): ";
if($calendar->getCacheDuration() != 86400)
{
	die("FAIL: cache duration must be 86400\n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method getCacheDuration(): ";

if($calendar->getCacheFolder() != __DIR__.'/cache')
{
	die("FAIL: cache folder must be ".__DIR__.'/cache'."\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getHolidayDates(): ";

if($calendar->getHolidayDates('03.01.2025') != array(
	'2025-01-01',
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08'
))
{
	die("FAIL: Holiday date array does not match the correct value\n");
}


if($calendar->getHolidayDates('03.01.2025', false) != array(
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08'
))
{
	die("FAIL: Holiday date array (with not full) does not match the correct value\n");
}

if($calendar->getHolidayDates('03.01.2025', false, 'd.m.Y') != array(
    '04.01.2025',
    '05.01.2025',
    '06.01.2025',
    '07.01.2025',
    '08.01.2025'
))
{
	die("FAIL: Holiday date array (with date format) does not match the correct value\n");
}

if($calendar->getHolidayDates('07.03.2025') != array())
{
	die("FAIL: Holiday date array (07.03.2025) not empty array\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getHolidayName(): ";

if($calendar->getHolidayName('07.03.2025') != '')
{
	die("FAIL: Holiday name (07.03.2025) not empty\n");
}

if($calendar->getHolidayName('08.03.2025') != 'Международный женский день')
{
	die("FAIL: Holiday name (08.03.2025) does not match with 'Международный женский день'\n");
}

if($calendar->getHolidayName('01.01.2025') != 'Новогодние каникулы')
{
	die("FAIL: Holiday name (01.01.2025) does not match with 'Новогодние каникулы'\n");
}


print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getLocale(): ";

if($calendar->getLocale() != 'ru')
{
	die("FAIL: locale\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getCountry(): ";

if($calendar->getCountry() != 'ru')
{
	die("FAIL: country\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getNextWorkingDay(): ";

if($calendar->getNextWorkingDay('01.01.2026') != '2026-01-12')
{
	die("FAIL: next working day must be 12.01.2026\n");
}

if($calendar->getNextWorkingDay('01.01.2026', array(6)) != '2026-01-11')
{
	die("FAIL: next working day must be 2026-01-11'\n");
}

if($calendar->getNextWorkingDay('01.01.2026', array(6), 'd.m.Y') != '11.01.2026')
{
	die("FAIL: next working day must be 11.01.2026\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////

print "test method getWeekendDates(): ";

if($calendar->getWeekendDates('01.01.2025') != array(
    '2024-12-29',
    '2024-12-30',
    '2024-12-31',
    '2025-01-01',
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08',
))
{
	die("FAIL: Weekend date array does not match the correct value\n");
}

if($calendar->getWeekendDates('01.01.2025', array(0,6)) != array(
    '2024-12-29',
    '2024-12-30',
    '2024-12-31',
    '2025-01-01',
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08',
))
{
	die("FAIL: Weekend date array (with weekends=[0,6]) does not match the correct value\n");
}

if($calendar->getWeekendDates('01.01.2025', array(4,5)) != array(
    '2024-12-30',
    '2024-12-31',
    '2025-01-01',
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08',
    '2025-01-09',
    '2025-01-10'
))
{
	die("FAIL: Weekend date array (with weekends=[4,5]) does not match the correct value\n");
}

if($calendar->getWeekendDates('17.01.2025') != array())
{
	die("FAIL: Weekend date array must be empty\n");
}

if($calendar->getWeekendDates('18.01.2025') != array(
	'2025-01-18',
    '2025-01-19'
))
{
	die("FAIL: Weekend date array (18.01.2025) does not match the correct value\n");
}

if($calendar->getWeekendDates('01.01.2025', array(0,6), true) != array(
	'2024-12-29',
    '2024-12-30',
    '2024-12-31',
    '2025-01-01',
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08',
))
{
	die("FAIL: Weekend date array (01.01.2025 with fullArray=true) does not match the correct value\n");
}

if($calendar->getWeekendDates('01.01.2025', array(0,6), false) != array(
    '2025-01-02',
    '2025-01-03',
    '2025-01-04',
    '2025-01-05',
    '2025-01-06',
    '2025-01-07',
    '2025-01-08',
))
{
	die("FAIL: Weekend date array (01.01.2025 with fullArray=false) does not match the correct value\n");
}

if($calendar->getWeekendDates('01.01.2025', array(0,6), false, 'd.m.Y') != array(
    '02.01.2025',
    '03.01.2025',
    '04.01.2025',
    '05.01.2025',
    '06.01.2025',
    '07.01.2025',
    '08.01.2025'
))
{
	die("FAIL: Weekend date aray (01.01.2025 with fullArray=false) does not match the correct value\n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method getWeekendFrom(): ";

if($calendar->getWeekendFrom('2025-11-03') != '2025-11-01')
{
	die("FAIL: weekend 2025-11-03 (as string) moved from 2025-11-01\n");
}

if($calendar->getWeekendFrom(strtotime('2025-11-03')) != '2025-11-01')
{
	die("FAIL: weekend 2025-11-03 (as timestamp) moved from 2025-11-01\n");
}

if($calendar->getWeekendFrom(new DateTime('2025-11-03')) != '2025-11-01')
{
	die("FAIL: weekend 2025-11-03 (as DateTime) moved from 2025-11-01\n");
}

if($calendar->getWeekendFrom('2025-11-03', 'd.m.Y') != '01.11.2025')
{
	die("FAIL: weekend 2025-11-03 (as string) moved from 01.11.2025\n");
}

if($calendar->getWeekendFrom('2025-11-04') != null)
{
	die("FAIL: weekend 2025-11-04 (as string) not moved from\n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method getWeekendTo(): ";

if($calendar->getWeekendTo('2025-11-01') != '2025-11-03')
{
	die("FAIL: weekend 2025-11-01 (as string) moved to 2025-11-03\n");
}

if($calendar->getWeekendTo(strtotime('2025-11-01')) != '2025-11-03')
{
	die("FAIL: weekend 2025-11-01 (as timestamp) moved to 2025-11-03\n");
}

if($calendar->getWeekendTo(new DateTime('2025-11-01')) != '2025-11-03')
{
	die("FAIL: weekend 2025-11-01 (as DateTime) moved to 2025-11-03\n");
}

if($calendar->getWeekendTo('2025-11-01', 'd.m.Y') != '03.11.2025')
{
	die("FAIL: weekend 2025-11-01 (as string) moved to 03.11.2025\n");
}

if($calendar->getWeekendTo('2025-11-04') != null)
{
	die("FAIL: weekend 2025-11-04 (as string) not moved to\n");
}

print "OK\n";

///////////////////////////////////////////////////////////////////////////////////

print "test method getSourcePublicUrl(): ";

if($calendar->getSourcePublicUrl() != "https://raw.githubusercontent.com/xmlcalendar/data/refs/heads/master")
{
	die("FAIL: source public url\n");
}

print "OK\n";
///////////////////////////////////////////////////////////////////////////////////


print "\n\nAll test: OK\n";