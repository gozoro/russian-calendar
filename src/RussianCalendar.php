<?php

namespace calendar;


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


	protected $locale;

	/**
	 * Хранит загруженные данные производственных календарей по годам.
	 * @var array
	 */
	private $data;

	private $cacheFolder;
	private $cacheEnable = false;



	public function __construct($locale = self::LOCALE_RU, $cacheFolder = null)
	{
		$this->locale = strtolower(substr($locale, 0, 2));
		$this->cacheFolder = $cacheFolder;
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
		if($this->locale == self::LOCALE_RU)
			$url = 'http://xmlcalendar.ru/data/ru/'.$year.'/calendar.xml';
		elseif($this->locale == self::LOCALE_EN)
			$url = 'http://xmlcalendar.ru/data/ru/'.$year.'/calendar.en.xml';
		else
			$this->throwException("Invalid russian calendar locale.");
	}

	/**
	 * Возвращает имя локального файла для каледаря с годом $year
	 * для текущего момента времени.
	 * Новый файл будет для каждой недели.
	 * Соответственно код будет получать обновленные данные календаря,
	 * каждую неделю.
	 *
	 * @param string $year год календаря
	 * @return string
	 */
	protected function getLocalXml($year)
	{
		$cacheFolder = $this->getCacheFolder();

		return 'calendar-'.$year.'-loaded'

			.date('Y.m-').'w'.date('W') // это можно вынести в настройки - как часто обновлять кэш

			.'.'.$this->locale.'.xml';
	}



	/**
	 * Включает кэширование. Файл полученный на xmlcalendar.ru
	 * копируется в локальную директорию.
	 * @return $this
	 */
	public function cache()
	{
		$cacheFolder = $this->getCacheFolder();

		if($cacheFolder)
		{
			$this->cacheEnable = true;
		}
		return $this;
	}




	/**
	 * Выбрасывает исключение
	 * @param string $message
	 * @throws \Exception
	 */
	public function throwException($message)
	{
		throw new \RussianCalendarException($message);
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


	protected function loadCalendarData($year)
	{
		if($this->cacheEnable)
		{
			$localFile = $this->getLocalXml($year);
			$cache = false;

			if(file_exists($localFile))
			{
				$xmlfile = $localFile;
			}
			else
			{
				$remoteFile = $this->getCalendarXml($year);
				$cache = true;
			}
		}
		else
		{
			$xmlfile = $this->getCalendarXml($year);
		}





		$xml = file_get_contents($xmlfile);

		if(empty($xml))
		{
			$this->throwException("Fails get content $xmlfile.");
		}
		else
		{
			if($cache)
			{
				file_put_contents($localFile, $xml);
			}

			return $this->parse($xml, $year);
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
			$xml_calendars = $xml->getElementsByTagName('calendar');
			$yearFromContent = $xml_calendars[0]->getAttribute('year');


			if($year != $yearFromContent)
			{
				$this->throwException("Year does not match year in XML content.");
			}

			if(empty($year))
			{
				$this->throwException("Failed to get a calendar year.");
			}

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

			$data = array();
			$xml_days = $xml->getElementsByTagName('day');

			foreach ($xml_days as $day)
			{
				$d = $day->getAttribute('d'); // дата 01.01
				$t = $day->getAttribute('t'); // тип (см. константы)
				$h = $day->getAttribute('h'); // идентификатор праздника
				$date = str_replace('.','-', $year.'.'.$d);

				$holiday = null;
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


			$this->data[$year] = $data;

		}
		else
		{
			$this->throwException("Fails load xml content.");
		}
	}


	/**
	 * Возвращает TRUE, если $date праздник. Праздник это всегда выходной день.
	 * @param int|string|DateTime $date дата в формате YYYY-MM-DD или DD.MM.YYYY
	 * @return bool
	 */
	public function checkHoliday($date)
	{
		$ts = $this->convertTimestamp($date);
		$date = date('Y-m-d', $ts);
		$year = date('Y', $ts);


		$this->loadCalendarData($year);




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

}

class RussianCalendarException extends \Exception
{

}

