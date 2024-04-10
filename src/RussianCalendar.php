<?php

namespace gozoro\russian_calendar;

/**
 * Класс производственного календаря РФ.
 * Класс работает с календарем с сайта xmlcalendar.ru.
 *
 * @author gozoro <gozoro@yandex.ru>
 */
class RussianCalendar
{
	/**
	 * 1 - выходной день
	 */
	const WEEKEND = 1;

	/**
	 * 2 - короткий день
	 */
	const SHORT_DAY = 2;
	/**
	 * 3 - рабочий день (суббота/воскресенье)
	 */
	const WORKING_DAY = 3;


	/**
	 * Названия праздников по русски
	 */
	const LOCALE_RU = 'ru';

	/**
	 * Названия праздников по английски
	 */
	const LOCALE_EN = 'en';

	/**
	 * Локаль календаря. От нее зависит названия праздников, которые могут
	 * выводиться на русском или английском языке.
	 * @var string
	 */
	protected $locale;

	/**
	 * Хранит загруженные данные производственных календарей по годам.
	 * @var array
	 */
	private $data;

	/**
	 * Хранит путь к директории для кэша xml-файлов
	 * @var string
	 */
	private $cacheFolder;

	/**
	 * Время хранения кэша в секундах
	 * @var int
	 */
	private $cacheDuration = 0;


	/**
	 * Значение для установки прав на файл кэша.
	 * По умолчанию NULL - права будут заданы операционной системой.
	 * В случае установки собственного значения, оно должно иметь ведущий ноль.
	 * @var int
	 */
	public $fileMode;

	/**
	 * Значение для прав при создании новой директории для кэша.
	 * Значение должно иметь ведущий ноль.
	 * @var int
	 */
	public $dirMode = 0775;


	/**
	 * Класс производственного календаря РФ.
	 * @param string $locale локаль календаря
	 * @param string|null $cacheFolder путь к директории для локального кэша xml-файлов календаря
	 * @param int $cacheDuration время кэширования xml-файла в секундах, по-умолчанию 60*60*24
	 */
	public function __construct($locale = self::LOCALE_RU, $cacheFolder = null, $cacheDuration = 86400)
	{
		$this->locale = strtolower(substr($locale, 0, 2));

		if($this->locale != self::LOCALE_RU)
			$this->locale = self::LOCALE_EN;


		if($cacheFolder)
		{
			$this->cacheDuration = (int)$cacheDuration;
			$this->cacheFolder = $cacheFolder;
		}
	}

	/**
	 * Возвращает локаль календаря
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * Возвращает путь к директории для кэширования xml-файлов.
	 * @return string
	 */
	public function getCacheFolder()
	{
		if($this->cacheFolder)
			return $this->cacheFolder;
		else
			$this->throwException("Cache folder undefined.");
	}

	/**
	 * Возвращает ссылку на xml-файл производственного календаря,
	 * который расположен на сайте xmlcalendar.ru, с учетом локали.
	 *
	 * @param string $year год календаря, который нужно получить
	 * @return string
	 */
	protected function getCalendarXml($year)
	{
		$year = (int)$year;
		if($this->locale == self::LOCALE_RU)
			$url = 'https://raw.githubusercontent.com/xmlcalendar/xmlcalendar.github.io/main/data/ru/'.$year.'/calendar.xml';
		else
			$url = 'https://raw.githubusercontent.com/xmlcalendar/xmlcalendar.github.io/main/data/ru/'.$year.'/calendar.en.xml';

		return $url;
	}

	/**
	 * Возвращает путь к файлу с кэшем
	 * @param string $year
	 * @return string
	 */
	protected function getCacheFile($year)
	{
		$year = (int)$year;
		$cacheFolder = $this->getCacheFolder();

		return $cacheFolder
			.DIRECTORY_SEPARATOR
			.'calendar-'.$year.'-'.$this->locale.'.cache';
	}

	/**
	 * Возвращает время хранения кэша в секундах
	 * @return int
	 */
	public function getCacheDuration()
	{
		return (int)$this->cacheDuration;
	}

	/**
	 * Ковертирует входящую дату в timestamp.
	 *
	 * @param int|string|\DateTime $date дата
	 * @return int
	 */
	protected function convertTimestamp($date)
	{
		if(is_string($date))
			return \strtotime($date);
		elseif(is_integer($date))
			return $date;
		elseif($date instanceof \DateTime)
			return $date->getTimestamp();
		else
			$this->throwException("Invalid date.");
	}


	/**
	 * Выплняет загрузку данных календаря
	 * @param string $year
	 * @return array
	 */
	protected function loadCalendarData($year)
	{
		if($this->getCacheDuration())
		{
			$cacheFile = $this->getCacheFile($year);
			$duration  = $this->getCacheDuration();

			if(file_exists($cacheFile))
			{
				// есть файл кэша
				// надо открыть его
				$cache = file_get_contents($cacheFile);

				if(empty($cache))
				{
					$this->throwException('Fails read cache file.');
				}
				else
				{
					list($createTime, $xmlContent) = unserialize($cache);

					if(time() > ($createTime + $duration))
					{
						// кэш устарел
						// надо загрузить xml с сервера и сделать кэш
						$serverFile = $this->getCalendarXml($year);
						$xmlContent = $this->getXmlContent($serverFile);
						$this->createCache($cacheFile, $xmlContent);
						return $this->parse($xmlContent, $year);
					}
					else
					{
						// кэш валиден, можно использовать его
						return $this->parse($xmlContent, $year);
					}
				}
			}
			else
			{
				// нет файла каша
				// надо загрузить xml с сервера и сделать кэш
				$serverFile = $this->getCalendarXml($year);
				$xmlContent = $this->getXmlContent($serverFile);
				$this->createCache($cacheFile, $xmlContent);
				return $this->parse($xmlContent, $year);
			}
		}
		else
		{
			// кэш не используется
			// надо загрузить xml с сервера и сразу распарсить
			$serverFile = $this->getCalendarXml($year);
			$xmlContent = $this->getXmlContent($serverFile);
			return $this->parse($xmlContent, $year);
		}
	}

	/**
	 * Метод выполняет чтение xml-файла для получения контента.
	 * @param string $filename
	 * @return string
	 */
	protected function getXmlContent($filename)
	{
		$xmlContent = file_get_contents($filename);

		if(empty($xmlContent))
		{
			$this->throwException("Fails get content $filename");
		}
		else
		{
			return $xmlContent;
		}
	}


	/**
	 * Метод выполняет сохранение файла кэша
	 * @param string $cacheFile путь к файлу, в который будет записан кэш
	 * @param string $xmlContent контент из xml-файла
	 * @return TRUE вслучае успеха, иначе выбрасывает исключение
	 */
	protected function createCache($cacheFile, $xmlContent)
	{
		$cache = serialize(array(
			time(),
			$xmlContent
		));


		$cacheFolder = dirname($cacheFile);


		if(!file_exists($cacheFolder))
		{
			if(!mkdir($cacheFolder, $this->dirMode, true))
			{
				$this->throwException("Failed to create directory $cacheFolder.");
			}
		}


		if(@file_put_contents($cacheFile, $cache, LOCK_EX) != false)
		{
			if ($this->fileMode !== null) {
				@chmod($cacheFile, $this->fileMode);
			}

			return true;
		}
		else
		{
			$this->throwException("Fails to create cache file $cacheFile.");
		}
	}

	/**
	 * Выполняет разбор XML-файла.
	 *
	 * @param string $xmlContent контент xml-файла
	 * @param string $year год календаря
	 */
	protected function parse($xmlContent, $year)
	{
		$xml= new \DOMDocument();
		if($xml->loadXML($xmlContent))
		{
			/** @var DOMNodeList */
			$calendarNodeList = $xml->getElementsByTagName('calendar');

			if($calendarNodeList->length != 1)
			{
				$this->throwException("Fails calendar xml.");
			}

			/** @var DOMElement */
			$calendarNode = $calendarNodeList->item(0);
			$yearFromContent = $calendarNode->getAttribute('year');

			if($year != $yearFromContent)
			{
				$this->throwException("Year does not match year in XML content.");
			}

			if(empty($year))
			{
				$this->throwException("Failed to get a calendar year.");
			}

			$holidays = array();
			$holidayNodeList = $calendarNode->getElementsByTagName('holiday');

			foreach ($holidayNodeList as $holiday)
			{
				$id = $holiday->getAttribute('id');
				$title  = $holiday->getAttribute('title');

				$holidays[$id] = $title;
			}

			$data = array();
			$dayNodeList = $calendarNode->getElementsByTagName('day');

			foreach ($dayNodeList as $day)
			{
				$d = $day->getAttribute('d'); // дата 01.01
				$t = $day->getAttribute('t'); // тип (см. константы)
				$h = $day->getAttribute('h'); // идентификатор праздника
				$date = str_replace('.','-', $year.'.'.$d);

				if(isset($holidays[$h]))
					$holidayName = $holidays[$h];
				else
					$holidayName = '';

				$data[$date] = array(
					'd' => $date,
					't' => $t,
					'h'	=> $holidayName,
				);
			}

			if($data)
			{
				$this->data[$year] = $data;
				return $data;
			}
			else
			{
				$this->throwException("Fails to get data.");
			}
		}
		else
		{
			$this->throwException("Fails to load xml content.");
		}
	}

	/**
	 * Возвращает данные календаря в виде массива
	 * @param string $year
	 * @return array
	 */
	protected function getCalendarData($year)
	{
		if(!isset($this->data[$year]))
		{
			$this->loadCalendarData($year);
		}

		if(isset($this->data[$year]))
			return $this->data[$year];
		else
			$this->throwException("Fails to load data.");
	}

	/**
	 * Возвращает TRUE, если $date праздник. Праздник это всегда выходной день.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return bool
	 */
	public function checkHoliday($date)
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);

		if(isset($calendar[$date]))
		{
			if($calendar[$date]['t']==self::WEEKEND and $calendar[$date]['h'] != '')
				return true;
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * Возвращает название праздника по дате, или пустую строку, если праздника нет.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return string
	 */
	public function getHolidayName($date)
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);

		if(isset($calendar[$date]))
		{
			return $calendar[$date]['h'];
		}
		else
		{
			return '';
		}
	}

	/**
	 * Возращает TRUE, если $date выходной день (т.е. суббота, воскресенье или праздник,
	 * выпавший на будний день или субботу или воскресенье).
	 * Выходной день может не являться праздником.
	 * @param int|string|DateTime $date проверяемая дата
	 * @param array $weekends массив с днями недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 * @return bool
	 */
	public function checkWeekend($date, $weekends = array(0,6))
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);

		if(isset($calendar[$date]))
		{
			if($calendar[$date]['t']==self::WEEKEND)
				return true;
			else
				return false;
		}
		else
		{
			$w = date('w', $ts);

			if(in_array($w, $weekends))
				return true;
			else
				return false;
		}
	}

	/**
	 * Возращает TRUE, если $date предпраздничный (короткий) РАБОЧИЙ день.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return bool
	 */
	public function checkShortWorkingDay($date)
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);

		if(isset($calendar[$date]))
		{
			if($calendar[$date]['t'] == self::SHORT_DAY)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Возращает TRUE, если $date ПОЛНЫЙ РАБОЧИЙ день.
	 * Если день рабочий, но короткий, метод вернет FALSE.
	 *
	 * Для простой проверки рабочего дня (рабочий/не рабочий) используйте метод checkWorkingDay($date).
	 *
	 * @param int|string|DateTime $date проверяемая дата
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 * @return bool
	 */
	public function checkFullWorkingDay($date, $weekends = array(0,6))
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);

		if(isset($calendar[$date]))
		{
			if($calendar[$date]['t'] == self::WORKING_DAY)
				return true;
			else
				return false;
		}
		else
		{
			$w = date('w', $ts);

			if(in_array($w, $weekends))
				return false;
			else
				return true;
		}
	}

	/**
	 * Возвращает TRUE, если $date РАБОЧИЙ день (ПОЛНЫЙ или КОРОТКИЙ).
	 *
	 * @param int|string|DateTime $date проверяемая дата
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 * @return bool
	 */
	public function checkWorkingDay($date, $weekends = array(0,6))
	{
		return ($this->checkFullWorkingDay($date, $weekends) or $this->checkShortWorkingDay($date));
	}

	/**
	 * Возвращает дату следующего рабочего дня.
	 * @param int|string|DateTime $date дата относительно которой проверяем следующий рабочий день
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота).
	 * @param string $format формат даты возвращаемого значения
	 * @return string Дата в формате YYYY-MM-DD
	 */
	public function getNextWorkingDay($date, $weekends = array(0,6), $format='Y-m-d')
	{
		$ts = $this->convertTimestamp($date);
		do
		{
			$ts += 60*60*24;
			$nextDate = date($format, $ts);
		}
		while(!$this->checkWorkingDay($nextDate, $weekends));

		return $nextDate;
	}

	/**
	 * Возвращает массив дат последовательных выходных дней, в который входит $date.
	 * Если $date не выходной день, то метод вернет пустой массив.
	 *
	 * @param int|string|DateTime $date дата относительно которой проверяем следующий рабочий день
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота).
	 * @param bool $fullArray если TRUE, то массив будет включать все даты, в том числ предшествующие и равную $date,
	 *						  иначе только даты после $date.
	 * @param string $format формат дат в возвращаемом массиве
	 * @return array of string
	 */
	public function getWeekendDateArray($date, $weekends = array(0,6), $fullArray = true, $format = 'Y-m-d')
	{
		if($this->checkWeekend($date))
		{
			$ts = $this->convertTimestamp($date);

			if($fullArray)
			while($this->checkWeekend($ts, $weekends))
			{
				$ts -= 60*60*24;
			}

			$weekendDates = array();
			do
			{
				$ts += 60*60*24;
				$weekendDates[] = date($format, $ts);
			}
			while($this->checkWeekend($ts, $weekends));

			unset($weekendDates[count($weekendDates)-1]);

			return $weekendDates;
		}
		else
			return array();
	}

	/**
	 * Возвращает массив дат последовательных праздников, в который входит $date.
	 * Если $date не праздник, то метод вернет пустой массив (даже если $date выходной день).
	 *
	 * @param int|string|DateTime $date дата относительно которой проверяем следующий рабочий день
	 * @param bool $fullArray если TRUE, то массив будет включать все даты, в том числ предшествующие и равную $date,
	 *						  иначе только даты после $date.
	 * @param string $format формат дат в возвращаемом массиве
	 * @return array of string
	 */
	public function getHolidayDateArray($date, $fullArray = true, $format = 'Y-m-d')
	{
		if($this->checkHoliday($date))
		{
			$ts = $this->convertTimestamp($date);

			if($fullArray)
			while($this->checkHoliday($ts))
			{
				$ts -= 60*60*24;
			}

			$holidayDates = array();
			do
			{
				$ts += 60*60*24;

				$holidayDates[] = date($format, $ts);
			}
			while($this->checkHoliday($ts));

			unset($holidayDates[count($holidayDates)-1]);
			return $holidayDates;
		}
		else
			return array();
	}

	/**
	 * Выбрасывает исключение
	 * @param string $message
	 * @throws \RussianCalendarException
	 */
	public function throwException($message)
	{
		throw new RussianCalendarException($message);
	}
}

class RussianCalendarException extends \Exception
{

}