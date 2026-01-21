# russian-calendar

Производственный календарь РФ на основе xmlcalendar.ru для PHP.
Russian working calendar based on the xmlcalendar.ru for PHP.







Установка
------------
```code
	composer require gozoro/russian-calendar
```





Использование
-----
```php
$calendar = new \gozoro\russian_calendar\RussianCalendar('ru');

$date = '2019-01-02';
print "Дата: ".$date."\n";
print "ЭТО РАБОЧИЙ ДЕНЬ? ".($calendar->checkWorkingDay($date)?"ДА":"НЕТ")."\n"; // НЕТ

print "ЭТО ПОЛНЫЙ РАБОЧИЙ ДЕНЬ? ".($calendar->checkFullWorkingDay($date)?"ДА":"НЕТ")."\n"; // НЕТ

print "ЭТО КОРОТКИЙ РАБОЧИЙ ДЕНЬ? ".($calendar->checkShortWorkingDay($date)?"ДА":"НЕТ")."\n"; // НЕТ

print "ЭТО ВЫХОДНОЙ ДЕНЬ? ".($calendar->checkWeekend($date)?"ДА":"НЕТ")."\n"; // ДА

print "ЭТО ПРАЗДНИЧНЫЙ ДЕНЬ? ".($calendar->checkHoliday($date)?"ДА":"НЕТ")."\n"; // ДА

print "НАЗВАНИЕ ПРАЗДНИКА: ".$calendar->getHolidayName($date)."\n"; // Новогодние каникулы (в ред. Федерального закона от 23.04.2012 № 35-ФЗ)

print "СЛЕДУЮЩИЙ РАБОЧИЙ ДЕНЬ: ".$calendar->getNextWorkingDay($date)."\n"; // 2019-01-09
```




**Выходные дни**

По умолчанию выходными считаются суббота и воскресенье.
Это можно изменить указав выходные дни при вызове методов.
В этом случае выходными днями будут считаться только указанные дни недели.
```php
$my_weekends = [0]; // выходной только воскресенье, суббота рабочий день
$calendar->checkWorkingDay($date, $my_weekends);
```




**Продолжительность выходных и праздников**

Дополнительно можно получить список последовательных дат выходного или праздничного периода.


Получение списка дат выходного периода
```php
$weekends = [0,6];

// полный список
print_r($calendar->getWeekendDates($date, $weekends, true);

//Array
//(
//    [0] => 2018-12-30
//    [1] => 2018-12-31
//    [2] => 2019-01-01
//    [3] => 2019-01-02
//    [4] => 2019-01-03
//    [5] => 2019-01-04
//    [6] => 2019-01-05
//    [7] => 2019-01-06
//    [8] => 2019-01-07
//    [9] => 2019-01-08
//)

// только даты больше чем $date и даты в формате d.m.Y
print_r($calendar->getWeekendDates($date, $weekends, false, 'd.m.Y');

//Array
//(
//    [0] => 03.01.2019
//    [1] => 04.01.2019
//    [2] => 05.01.2019
//    [3] => 06.01.2019
//    [4] => 07.01.2019
//    [5] => 08.01.2019
//)
```


Получение списка дат праздничного периода
```php
// полный список
print_r($calendar->getHolidayDates($date, true);

// Array
//(
//    [0] => 2019-01-01
//    [1] => 2019-01-02
//    [2] => 2019-01-03
//    [3] => 2019-01-04
//    [4] => 2019-01-05
//    [5] => 2019-01-06
//    [6] => 2019-01-07
//    [7] => 2019-01-08
//)

// только даты больше чем $date и даты в формате d.m.Y
$holidayArray = $calendar->getHolidayDates($date, false, 'd.m.Y');
print_r($holidayArray);

//Array
//(
//    [0] => 03.01.2019
//    [1] => 04.01.2019
//    [2] => 05.01.2019
//    [3] => 06.01.2019
//    [4] => 07.01.2019
//    [5] => 08.01.2019
//)


// Сколько дней осталось отдыхать?
print count($holidayArray); // 6
```



**Переносы выходных**

Выходные могут перенести Постановлением Правительства РФ,
например в 2025 году, с субботы 1 ноября на понедельник 3 ноября. 

```php
// С какой даты перенесен выходной день 2025-11-03?
print $calendar->getWeekendFrom('2025-11-03'); // '2025-11-01'

// На какую дату перенесен выходной день 2025-11-01?
print $calendar->getWeekendTo('2025-11-01'); // '2025-11-03'
```




**Кэширование**

По умолчанию для получения XML-файлов с данными, класс делает запросы к `raw.githubusercontent.com/xmlcalendar/data/...`.
Чтобы класс не делал долгих запросов к сайту, можно закэшировать XML-файл локально.
Для этого в конструкторе нужно указать путь к директории, куда будет скопирован XML-файл
и время кэша в секундах.

```php
$cacheFolder = '/var/www/site/runtime/xmlcalendar';
$cacheDuration = 60*60*24; // кэш файла на сутки
$calendar = new \gozoro\russian_calendar\RussianCalendar('ru', $cacheFolder, $cacheDuration);
```

**Названия праздников на английском**

```php
$calendar = new \gozoro\russian_calendar\RussianCalendar('ru:en');
```


**Производственные календари других стран**

```php

// Казахстан
$calendar = new \gozoro\russian_calendar\RussianCalendar('kz');

// Беларусь
$calendar = new \gozoro\russian_calendar\RussianCalendar('by');

// Узбекистан
$calendar = new \gozoro\russian_calendar\RussianCalendar('uz');

// Украина
$calendar = new \gozoro\russian_calendar\RussianCalendar('uk');
```
