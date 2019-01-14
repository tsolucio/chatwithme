<?php
/*************************************************************************************************
 * Copyright 2018 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
 * Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
 * file except in compliance with the License. You can redistribute it and/or modify it
 * under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
 * granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
 * applicable law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing
 * permissions and limitations under the License. You may obtain a copy of the License
 * at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
 *************************************************************************************************/
require_once 'include/Webservices/Create.php';

class cbmmActionRemindme extends chatactionclass {
	const ACTIVITYTYPE = 'MMRemindMe';
	private $status;
	const STATUS_FOUNDAT = 1;
	const STATUS_FOUNDIN = 2;
	const STATUS_BADFORMAT = 3;

	public function getHelp() {
		global $current_user;
		$msg = str_replace('$1', $current_user->date_format, getTranslatedString('remindme_command1', 'chatwithme'));
		return ' - '.$msg."\n".' - '.getTranslatedString('remindme_command2', 'chatwithme');
	}

	public function process() {
		global $current_user;
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		if (in_array('[at]', $prm)) {
			$atpos = array_search('[at]', $prm);
			$desc = array_slice($prm, 1, -(count($prm)-$atpos));
			$description = implode(' ', $desc);
			$subject = substr($description, 0, 60);
			$timespec = array_slice($prm, $atpos+1);
			if ($this->isValidDate($timespec)) {
				for ($tsp=0; $tsp<count($timespec); $tsp++) {
					if (strlen($timespec[$tsp])<3) {
						$timespec[$tsp] = '0'.$timespec[$tsp];
					}
				}
				list($h, $i) = explode(':', $timespec[1]);
				switch ($current_user->date_format) {
					case 'dd-mm-yyyy':
						list($d, $m, $y) = explode('-', $timespec[0]);
						$dtstart=date('d-m-Y H:i', mktime($h, $i, 0, $m, $d, $y));
						$dtend=date('d-m-Y H:i', mktime($h, $i+1, 0, $m, $d, $y));
						break;
					case 'mm-dd-yyyy':
						list($m, $d, $y) = explode('-', $timespec[0]);
						$dtstart=date('m-d-Y H:i', mktime($h, $i, 0, $m, $d, $y));
						$dtend=date('m-d-Y H:i', mktime($h, $i+1, 0, $m, $d, $y));
						break;
					case 'yyyy-mm-dd':
					default:
						list($y, $m, $d) = explode('-', $timespec[0]);
						$dtstart=date('Y-m-d H:i', mktime($h, $i, 0, $m, $d, $y));
						$dtend=date('Y-m-d H:i', mktime($h, $i+1, 0, $m, $d, $y));
						break;
				}
				$this->status = self::STATUS_FOUNDAT;
			} else {
				$this->status = self::STATUS_BADFORMAT;
			}
		} elseif (in_array('[in]', $prm)) {
			$inpos = array_search('[in]', $prm);
			$desc = array_slice($prm, 1, -(count($prm)-$inpos));
			$description = implode(' ', $desc);
			$subject = substr($description, 0, 60);
			$timespec = array_slice($prm, $inpos+1);
			$incmin = $this->convertToMinutes($timespec);
			$dtstart = date('Y-m-d H:i:s', strtotime('+'.$incmin.'minutes'));
			$dtend = date('Y-m-d H:i:s', strtotime('+'.($incmin+5).'minutes'));
			$strdatetime = new DateTimeField($dtstart);
			$dtstart = $strdatetime->getDisplayDateTimeValue();
			$enddatetime = new DateTimeField($dtend);
			$dtend = $enddatetime->getDisplayDateTimeValue();
			$this->status = self::STATUS_FOUNDIN;
		} else {
			$this->status = self::STATUS_BADFORMAT;
		}
		if ($this->status != self::STATUS_BADFORMAT) {
			$data = array(
				'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
				'subject' => $subject,
				'description' => $description,
				'activitytype' => self::ACTIVITYTYPE,
				'eventstatus' => 'Planned',
				'dtstart' => $dtstart,
				'dtend' => $dtend
			);
			vtws_create('cbCalendar', $data, $current_user);
		}
		return true;
	}

	public function getResponse() {
		global $current_user;
		$req = getMMRequest();
		switch ($this->status) {
			case self::STATUS_FOUNDIN:
			case self::STATUS_FOUNDAT:
				$ret = array(
					'response_type' => 'in_channel',
					'text' => getTranslatedString('OkWillDo', 'chatwithme'),
				);
				break;
			case self::STATUS_BADFORMAT:
			default:
				$ret = getMMDoNotUnderstandMessage($this->getHelp());
				break;
		}
		return $ret;
	}

	private function isValidDate($timespec) {
		global $current_user;
		switch ($current_user->date_format) {
			case 'dd-mm-yyyy':
				if (preg_match('/^(0?[1-9]|1[0-2])-(0?[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/', $timespec[0])) {
					list($d, $m, $y) = explode('-', $timespec[0]);
				} else {
					return false;
				}
				break;
			case 'mm-dd-yyyy':
				if (preg_match('/^(0?[1-9]|[1-2][0-9]|3[0-1])-(0?[1-9]|1[0-2])-[0-9]{4}$/', $timespec[0])) {
					list($m, $d, $y) = explode('-', $timespec[0]);
				} else {
					return false;
				}
				break;
			case 'yyyy-mm-dd':
			default:
				if (preg_match('/^[0-9]{4}-(0?[1-9]|1[0-2])-(0?[1-9]|[1-2][0-9]|3[0-1])$/', $timespec[0])) {
					list($y, $m, $d) = explode('-', $timespec[0]);
				} else {
					return false;
				}
				break;
		}
		if (checkdate($m, $d, $y)) {
			if (preg_match('/^([0-1]?[1-9]|2[0-3]):([0-5]?[1-9])$/', $timespec[1])) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function convertToMinutes($timespec) {
		for ($tsp=0; $tsp<count($timespec); $tsp++) {
			if (strlen($timespec[$tsp])<3) {
				$timespec[$tsp] = '0'.$timespec[$tsp];
			}
			$timespec[$tsp] = strtolower($timespec[$tsp]);
		}
		if (substr($timespec[0], -1)=='d') {
			$days = array_shift($timespec);
			$d = (int)substr($days, 0, strlen($days)-1)*1440;
		} else {
			$d = 0;
		}
		$timespec = implode(' ', $timespec);
		$ts = DateTime::createFromFormat('H\h i\m', $timespec);
		if ($ts) {
			$ts = $ts->format('H:i:s');
		} else {
			$ts = DateTime::createFromFormat('i\m', $timespec);
			if ($ts) {
				$ts = $ts->format('H:i:s');
			} else {
				$ts = DateTime::createFromFormat('i\m s\s', $timespec);
				if ($ts) {
					$ts = $ts->format('H:i:s');
				} else {
					$ts = DateTime::createFromFormat('H\h i\m s\s', $timespec);
					if ($ts) {
						$ts = $ts->format('H:i:s');
					} else {
						$ts = DateTime::createFromFormat('H\h', $timespec);
						if ($ts) {
							$ts = $ts->format('H:i:s');
						} else {
							$ts = DateTime::createFromFormat('H\h s\s', $timespec);
							if ($ts) {
								$ts = $ts->format('H:i:s');
							} else {
								$ts = '0:0:0'; // 's\s'
							}
						}
					}
				}
			}
		}
		list($h, $m, $i) = explode(':', $ts);
		return ($d+$h*60+$m);
	}
}
?>