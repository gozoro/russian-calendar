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
	 * Названия праздников по русски.
	 * Данная константа устарела, вместо данной константы используйте COUNTRY_RU.
	 *
	 * @deprecated since v1.0.0
	 */
	const LOCALE_RU = 'ru';

	/**
	 * Названия праздников по английски.
	 * Данная константа устарела, вместо данной константы используйте COUNTRY_RU_EN.
	 *
	 * @deprecated since v1.0.0
	 */
	const LOCALE_EN = 'en';


	/**
	 * Календарь Республики Беларусь
	 */
	const COUNTRY_BY = 'by';

	/**
	 * Календарь Республики Казахстан
	 */
	const COUNTRY_KZ = 'kz';

	/**
	 * Календарь России (на русском языке)
	 */
	const COUNTRY_RU = 'ru';

	/**
	 * Календарь России (на английском языке)
	 */
	const COUNTRY_RU_EN = 'ru:en';

	/**
	 * Календарь Украины
	 */
	const COUNTRY_UA = 'ua';

	/**
	 * Календарь Узбекистана
	 */
	const COUNTRY_UZ = 'uz';


	/**
	 * Хранит загруженные данные производственных календарей по годам.
	 * @var array
	 */
	private $data;

	/**
	 * Хранит даты переноса выходных дней
	 * @var array
	 */
	private $weekendTo;

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
	 * Хранит страну календаря
	 * @var string
	 */
	protected $country;

	/**
	 * Хранит перевод календаря
	 * @var string
	 */
	protected $locale;

	/**
	 * Класс производственного календаря.
	 * @param string $country страна
	 * @param string|null $cacheFolder путь к директории для локального кэша xml-файлов календаря
	 * @param int $cacheDuration время кэширования xml-файла в секундах, по-умолчанию 60*60*24
	 */
	public function __construct($country = self::COUNTRY_RU, $cacheFolder = null, $cacheDuration = 86400)
	{
		$parts = explode(':', $country);
		if(\count($parts) == 1)
		{
			$this->country = $this->locale = \strtolower($parts[0]);

			if($this->country == self::LOCALE_EN)
			{
				$this->country = self::COUNTRY_RU;
			}
		}
		elseif(\count($parts) == 2)
		{
			$this->country = \strtolower($parts[0]);
			$this->locale  = \strtolower($parts[1]);
		}
		else
		{
			$this->throwException("Invalid country.");
		}

		if($cacheFolder)
		{
			$this->cacheDuration = (int)$cacheDuration;
			$this->cacheFolder = $cacheFolder;
		}

		$this->weekendTo = array();
	}

	/**
	 * Возвращает страну календаря
	 * @return string
	 */
	public function getCountry()
	{
		return $this->country;
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
	 * Возвращает время хранения кэша в секундах
	 * @return int
	 */
	public function getCacheDuration()
	{
		return (int)$this->cacheDuration;
	}

	/**
	 * Возвращает ссылку-источик, где опубликованы файлы с календарями
	 * @return string
	 */
	public function getSourcePublicUrl()
	{
		return 'https://raw.githubusercontent.com/xmlcalendar/data/refs/heads/master';
	}

	/**
	 * Возвращает ссылку на xml-файл производственного календаря,
	 * который расположен в публичном репозитории сайта xmlcalendar.ru, с учетом страны и локали.
	 *
	 * @param string $year год календаря, который нужно получить
	 * @return string
	 */
	protected function getCalendarXml($year)
	{
		$year = (int)$year;
		$country = $this->country;
		$locale = $this->locale;

		if($country == $locale)
		{
			$url = $this->getSourcePublicUrl().'/'.$country.'/'.$year.'/calendar.xml';
		}
		else
		{
			$url = $this->getSourcePublicUrl().'/'.$country.'/'.$year.'/calendar.'.$locale.'.xml';
		}

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
			.'calendar-'.$year.'-'.$this->country.'.'.$this->locale.'.cache';
	}


	/**
	 * Переводит дату в строке в timestamp.
	 * В случае ошибки выбрасывает Exception.
	 *
	 * @param string $date дата
	 * @return int|false
	 */
	protected function strtotime($date)
	{
		$ts = \strtotime($date);
		if($ts === false)
			$this->throwException("Fails date parse.");
		else
			return $ts;
	}


	/**
	 * Ковертирует входящую дату в timestamp.
	 *
	 * @param int|string|\DateTime $date дата
	 * @return int
	 */
	protected function parseDate($date)
	{
		if(is_string($date))
			return $this->strtotime($date);
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
		$duration  = $this->getCacheDuration();
		$cacheFile = $this->getCacheFile($year);

		if($duration and file_exists($cacheFile))
		{
			$cache = file_get_contents($cacheFile);

			if($cache === false)
			{
				$this->throwException('Fails read cache file.');
			}
			elseif(empty($cache))
			{
				$this->throwException('Cache is empty.');
			}
			else
			{
				list($createTime, $xmlContent) = unserialize($cache);

				if(time() < ($createTime + $duration))
				{
					// кэш валиден, можно использовать его
					return $this->parse($xmlContent, $year);
				}
			}
		}


		$serverFile = $this->getCalendarXml($year);
		$xmlContent = $this->getXmlContent($serverFile);
		$data = $this->parse($xmlContent, $year);

		if($duration)
			$this->createCache($cacheFile, $xmlContent);

		return $data;
	}


	/**
	 * Метод выполняет чтение xml-файла для получения контента.
	 * @param string $filename
	 * @return string
	 */
	protected function getXmlContent($filename)
	{
		$xmlContent = file_get_contents($filename);

		if($xmlContent == false)
		{
			$this->throwException("Fails get content $filename");
		}
		elseif(empty($xmlContent))
		{
			$this->throwException("Content of $filename is empty");
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
	 * @return array('t', h', 'f')
	 */
	protected function parse($xmlContent, $year)
	{
		if(empty($xmlContent))
		{
			$this->throwException("Failed to get a calendar xml content.");
		}

		if(empty($year))
		{
			$this->throwException("Failed to get a calendar year.");
		}

		$xml= new \DOMDocument();
		if($xml->loadXML($xmlContent))
		{
			$rootName = $xml->documentElement->nodeName;

			if($rootName != 'calendar')
				$this->throwException('Bad root element.');

			$xmlYear    = $xml->documentElement->getAttribute('year');

			if($xmlYear != $year)
				$this->throwException("Bad calendar year: $xmlYear. Expected: $year.");

			$calendarNodeList = $xml->getElementsByTagName('calendar');

			if($calendarNodeList->length != 1)
			{
				$this->throwException("Fails calendar xml.");
			}
			unset($calendarNodeList);

			$calendarNode = $xml->documentElement;

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
				$d = $day->getAttribute('d'); // дата mm.dd
				$t = $day->getAttribute('t'); // тип (см. константы)
				$h = $day->getAttribute('h'); // идентификатор праздника
				$f = $day->getAttribute('f'); // дата mm.dd откуда перенесен выходной день, соответственно mm.dd будет рабочим днем
				$date = $year.'-'.str_replace('.','-',$d);

				if($f)
				{
					$weekendTo = $year.'-'.str_replace('.','-', $f);
					$this->weekendTo[$weekendTo] = $date;
				}

				if(isset($holidays[$h]))
					$holidayName = $holidays[$h];
				else
					$holidayName = '';



				$data[$date] = array(
					't' => $t,
					'h'	=> $holidayName,
					'f' => $f,
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
	 * Возвращает дату, с которой осуществляется перенос выходного дня на дату $date.
	 * В общем случае дата $date становится выходным днем, а возвращаемая дата - рабочим.
	 * Если переноса нет, то метод вернут NULL.
	 *
	 * @param int|string|DateTime $date дата, на которую осуществляется перенос выходного дня
	 * @param string $format формат возвращаемой даты
	 * @return string
	 */
	public function getWeekendFrom($date, $format='Y-m-d')
	{
		$ts = $this->parseDate($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);
		$calendar = $this->getCalendarData($year);


		if(!empty($calendar[$date]['f']))
		{
			return date($format, $this->strtotime( $year.'-'.str_replace('.', '-', $calendar[$date]['f']) ));
		}
		return null;
	}
	/**
	 * Возвращает дату, на которую осуществляется перенос выходного дня с даты $date.
	 * В общем случае дата $date становится рабочим днем, а возвращаемая дата - выходным.
	 *
	 * @param int|string|DateTime $date дата, с которой осуществляется перенос выходного дня
	 * @param string $format формат возвращаемой даты
	 * @return string
	 */
	public function getWeekendTo($date, $format='Y-m-d')
	{
		$ts = $this->parseDate($date);
		$this->getCalendarData(date('Y', $ts));

		$weekendTo = date('Y-m-d', $ts);
		if(isset($this->weekendTo[$weekendTo]))
		{
			return date($format, $this->strtotime( $this->weekendTo[$weekendTo] ));
		}
		return null;
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
		$ts = $this->parseDate($date);
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
	 * Возвращает TRUE, если $date праздник. Праздник это всегда выходной день.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return bool
	 */
	public function checkHoliday($date)
	{
		$ts = $this->parseDate($date);
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
	 * Возращает TRUE, если $date предпраздничный (короткий) РАБОЧИЙ день.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return bool
	 */
	public function checkShortWorkingDay($date)
	{
		$ts = $this->parseDate($date);
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
	 * Возращает TRUE, если $date выходной день (т.е. суббота, воскресенье или праздник,
	 * выпавший на будний день или субботу или воскресенье).
	 * Выходной день может не являться праздником.
	 * @param int|string|DateTime $date проверяемая дата
	 * @param array $weekends массив с днями недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 * @return bool
	 */
	public function checkWeekend($date, $weekends = array(0,6))
	{
		$ts = $this->parseDate($date);
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
		$ts = $this->parseDate($date);
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
	public function getWeekendDates($date, $weekends = array(0,6), $fullArray = true, $format = 'Y-m-d')
	{
		if($this->checkWeekend($date))
		{
			$ts = $this->parseDate($date);

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
	public function getHolidayDates($date, $fullArray = true, $format = 'Y-m-d')
	{
		if($this->checkHoliday($date))
		{
			$ts = $this->parseDate($date);

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
	 * Возвращает название праздника по дате, или пустую строку, если праздника нет.
	 * @param int|string|DateTime $date проверяемая дата
	 * @return string
	 */
	public function getHolidayName($date)
	{
		$ts = $this->parseDate($date);
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