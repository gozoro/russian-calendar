<?php

namespace russian\calendar;

/**
 * Класс производственного календаря,
 * для получения данных о праздниках, рабочих
 * и не рабочих днях.
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



	const LOCALE_RU = 'ru';
	const LOCALE_EN = 'en';




	private $calendarFolder = '.';
	private $year;
	private $locale;
	private $days;
	private $localXml;



	// $calendar = new RussianCalendar('2019');

	// идея: $calendar->cache()->checkWorkDay($date);
	// getCacheFolder()
	// getCacheFileName()

	public function __construct($date, $locale = self::LOCALE_RU)
	{
		$ts = \strtotime($date);


		$locales = [self::LOCALE_RU, self::LOCALE_EN];

		if(in_array($locale, $locales))
		{

			$this->year = date('Y', $ts);
			$this->locale = strtolower($locale);
			$this->localXml = $this->getCalendarFolder().'/'.$this->getLocalFileName();

			if(!$this->calendarExist())
			{
				$this->calendarCopyToLocal();
			}

			$this->calendarInit();
		}
		else
		{
			$this->throwException('Invalid russian calendar locale ['.$locale.'], use only ['.implode(', ', $locales).'].');
		}
	}

	private function __clone()
	{

	}

	/**
	 * Возвращает производственный календарь
	 * @param string $date
	 * @return static
	 */
	public static function getInstance($date, $locale)
	{
		static $calendars;

		$ts = \strtotime($date);
		$year = date('Y', $ts);

		if(!isset($calendars[$year][$locale]))
		{
			$calendars[$year][$locale] = new static($year, $locale);
		}
		return $calendars[$year][$locale];
	}


	/**
	 * Устанавливает путь к директории, в которую будут
	 * складываться xml-файлы производственного календаря.
	 */
	public function setCalendarFolder($folderPath)
	{
		$this->calendarFolder = $folderPath;
	}

	public function getCalendarFolder()
	{
		return $this->calendarFolder;
	}


	/**
	 * Возвращает шаблон имени локальных файлов
	 * @return string
	 */
	protected function getLocalFileName()
	{
		return 'calendar-'.date('Y.m.01-W').'.'.$this->locale.'.xml';
	}


	/**
	 * Проверяет существование производственного календаря
	 * @return bool
	 */
	private function calendarExist()
	{
		return file_exists($this->localXml);
	}


	protected function createCalendarUrl()
	{
		if($this->locale == self::LOCALE_RU)
			$url = 'http://xmlcalendar.ru/data/ru/'.$this->year.'/calendar.xml';
		elseif($this->locale == self::LOCALE_EN)
			$url = 'http://xmlcalendar.ru/data/ru/'.$this->year.'/calendar.en.xml';
		else
			$this->throwException("Нет такой локали"); //???

	}



	/**
	 * Копирует календарь с сайта xmlcalendar.ru  в локальную директорию
	 */
	private function calendarCopyToLocal()
	{
		$url = 'http://xmlcalendar.ru/data/ru/'.$this->year.'/calendar.xml';
		$xml = file_get_contents($url);
		if($xml != '')
		{
			file_put_contents($this->localXml, $xml);
		}
	}



	/**
	 * Загружает календарь
	 */
	private function calendarInit()
	{
		$xml= new \DOMDocument();
		if($xml->load($this->localXml))
		{
			$holidays= array();
			$xml_holidays = $xml->getElementsByTagName('holiday');
			foreach ($xml_holidays as $holiday)
			{
				$id = $holiday->getAttribute('id');
				$title  = $holiday->getAttribute('title');

				$holidays[$id] = array(
					'id' => $id,
					'title' => $title,
				);
			}

			$this->days= array();
			$xml_days = $xml->getElementsByTagName('day');

			foreach ($xml_days as $day)
			{
				$d = $day->getAttribute('d'); // дата
				$t = $day->getAttribute('t'); // тип (см. константы)
				$h = $day->getAttribute('h'); // идентификатор праздника
				$date = str_replace('.','-', $this->year.'.'.$d);

				$holiday = null;
				if(isset($holidays[$h]))
					$holidayName = $holidays[$h];
				else
					$holidayName = '';

				$this->days[$date] = array(
					'd' => $date,
					't' => $t,
					'h'	=> $holidayName,
				);
			}
		}
	}


	/**
	 * Выбрасывает исключение
	 * @param string $message
	 * @throws \Exception
	 */
	public function throwException($message)
	{
		throw new \InvalidArgumentException($message);
	}



	/**
	 * Список дней (выходной, короткий, рабочий)
	 * @return array
	 */
	public function getDays()
	{
		return $this->days;
	}

	/**
	 * Возвращает название праздника по дате, или пустую строку, если праздника нет.
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @return string
	 */
	public function getHolidayName($date)
	{
		$ts = \strtotime($date);
		$date = date('Y-m-d', $ts);

		if(isset($this->days[$date]))
		{
			return $this->days[$date]['h']['title'];
		}
		else
		{
			return '';
		}
	}

	/**
	 * Возвращает TRUE, если $date праздник. Праздник это всегда выходной день.
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @return bool
	 */
	public function checkHoliday($date)
	{
		$ts = \strtotime($date);
		$date = date('Y-m-d', $ts);

		if(isset($this->days[$date]))
		{
			if($this->days[$date]['t']==self::WEEKEND and $this->days[$date]['h'] != '')
				return true;
			else
				return false;
		}
		else
			return false;
	}


	/**
	 * Возращает TRUE, если $date выходной день (т.е. суббота, воскресенье или праздник, выпавший на будний день или сб или вск).
	 * Выходной день может не являться праздником.
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @param array $weekends массив с днями недели, которые являются выходными (по умолчанию [0,6] - СБ, ВС)
	 * @return bool
	 */
	public function checkWeekend($date, $weekends = array(0,6))
	{
		$ts = \strtotime($date);
		$date = date('Y-m-d', $ts);

		if(isset($this->days[$date]))
		{
			if($this->days[$date]['t']==self::WEEKEND)
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
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @return bool
	 */
	public function checkShortWorkingDay($date)
	{
		$ts = \strtotime($date);
		$date = date('Y-m-d', $ts);

		if(isset($this->days[$date]))
		{
			if($this->days[$date]['t']==self::SHORT_DAY)
				return true;
		}
		return false;
	}

	/**
	 * Возращает TRUE, если $date ПОЛНЫЙ РАБОЧИЙ день
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 * @return bool
	 */
	public function checkFullWorkingDay($date, $weekends = array(0,6))
	{
		$ts = \strtotime($date);
		$date = date('Y-m-d', $ts);

		if(isset($this->days[$date]))
		{
			if($this->days[$date]['t']==self::WORKING_DAY)
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
	 * @param string $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @param array $weekends массив с номерами дней недели, которые являются выходными (по умолчанию [0,6] - воскресенье, суббота)
	 */
	public function checkWorkingDay($date, $weekends = array(0,6))
	{
		return ($this->checkShortWorkingDay($date) or $this->checkFullWorkingDay($date, $weekends));
	}
}