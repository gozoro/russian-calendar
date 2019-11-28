# russian-calendar

Russian working calendar based on the xmlcalendar.ru
Производственный календарь РФ на основе xmlcalendar.ru.








Установка
------------
```code
	composer require gozoro/russian-calendar
```

Использование
-----
```php
$calendar = new RussianCalendar('ru');

$date = '2019-01-02';
print "Дата: ".$date."\n";
print "ЭТО РАБОЧИЙ ДЕНЬ? ".($calendar->checkWorkingDay($date)?"ДА":"НЕТ")."\n";

print "ЭТО ПОЛНЫЙ РАБОЧИЙ ДЕНЬ? ".($calendar->checkFullWorkingDay($date)?"ДА":"НЕТ")."\n";

print "ЭТО КОРОТКИЙ РАБОЧИЙ ДЕНЬ? ".($calendar->checkShortWorkingDay($date)?"ДА":"НЕТ")."\n";

print "ЭТО ВЫХОДНОЙ ДЕНЬ? ".($calendar->checkWeekend($date)?"ДА":"НЕТ")."\n";

print "ЭТО ПРАЗДНИЧНЫЙ ДЕНЬ? ".($calendar->checkHoliday($date)?"ДА":"НЕТ")."\n";

print "НАЗВАНИЕ ПРАЗДНИКА: ".$calendar->getHolidayName($date)."\n";

print "СЛЕДУЮЩИЙ РАБОЧИЙ ДЕНЬ: ".$calendar->getNextWorkingDay($date)."\n";
```


По-умолчанию выходными считаются суббота и воскресенье.
Это можно изменить указав выходные дни при вызове методов.

В этом случае выходными будут считаться только указанные дни недели.
```php
$my_weekends = [0]; // выходной только воскресенье, суббота рабочий день
$calendar->checkWorkingDay($date, $my_weekends);
```


**Дополнительно**

Дополнительно можно получить список дат выходного или праздничного периода.


Получение списка дат выходного периода (списка последовательных выходных)
```php
// полный список
print_r($calendar->getWeekendDateArray($date);

// только даты больше чем $date
$weekends = [0,6];
print_r($calendar->getWeekendDateArray($date, $weekends, false);
```


Получение списка дат праздничного периода (списка последовательных праздничных дней)
```php
// полный список
print_r($calendar->getHolidayDateArray($date);

// только даты больше чем $date
print_r($calendar->getHolidayDateArray($date, false);
```







