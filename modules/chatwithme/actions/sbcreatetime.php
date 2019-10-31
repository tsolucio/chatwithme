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
require_once 'modules/chatwithme/subitoutils.php';
require_once 'modules/Timecontrol/Timecontrol.php';

class cbmmActionsbcreatetime extends chatactionclass {
	const STATUS_TIMER_CLOSED = 1;
	const STATUS_MISSINGTYPE = 2;
	const STATUS_BADFORMAT = 3;
	const STATUS_DATEFORMAT = 4;
	const STATUS_TIMEFORMAT = 5;
	const STATUS_TYPE_NOTFOUND = 6;
	const STATUS_TIMEOVER8TOTAL = 7;
	const STATUS_TIMEOVER8 = 8;
	private $time_status;
	private $timeinfo = array();

	public function getHelp() {
		return ' - '.getTranslatedString('sbcreatetime_command', 'chatwithme');
	}

	public function process() {
		global $current_user;
		$req = getMMRequest();
		$this->timeinfo['team'] = $req['team_dname'];
		$prm = parseMMMsgWithQuotes($req['text']);
		if (count($prm)<3) {
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		}
		$time = $this->processTimeField($prm[1]);
		if (!preg_match('/\d\d:\d\d/', $time)) {
			$this->time_status = self::STATUS_TIMEFORMAT;
			return true;
		}
		list($h, $m) = explode(':', $time);
		if (((int)$h*60+(int)$m)>8*60) {
			$this->time_status = self::STATUS_TIMEOVER8;
			return true;
		}
		$this->timeinfo['time'] = $time;
		$title = $prm[2];
		$this->timeinfo['title'] = $title;
		if (count($prm)==3) {
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			if (!sbTypeOfWorkMapExist($req['channel_dname'])) {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
			$this->time_status = self::STATUS_MISSINGTYPE;
			$this->timeinfo['units'] = 1;
			$this->timeinfo['datestart'] = date('Y-m-d');
			return true;
		}
		if (count($prm)==4) {
			$param = $prm[3];
			if (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $param) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $param)
				|| preg_match('/\d\d\-\d\d\-\d\d/', $param) || preg_match('/\d\d\-\d\d/', $param)
			) {
				$this->processDateField($param);
				return true;
			} elseif (is_numeric($param)) {
				// we have units > ask for type of request
				$this->time_status = self::STATUS_MISSINGTYPE;
				$units = $param;
				$this->timeinfo['units'] = $param;
				return true;
			} else {
				$tow = sbgetTypeOfWork($req['channel_dname'], $param);
				if ($tow) {
					// we have all we need, units=1, date=today
					$cn = explode('-', $req['channel_dname']);
					$brand = $cn[0];
					$prjtype = $cn[1];
					if (Timecontrol::userTotalTime(date('Y-m-d'), $current_user->id)>8*60) {
						$this->time_status = self::STATUS_TIMEOVER8TOTAL;
						return true;
					}
					$result = stoptimerDoCreateTC(time(), $time, $brand, $prjtype, $title, $param, 1, $req['team_dname']);
					$time_array = explode(':', $result['totaltime']);
					$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
					$this->timeinfo['title'] = $title;
					$this->timeinfo['typeofwork'] = $param;
					$this->time_status = self::STATUS_TIMER_CLOSED;
					return true;
				} else {
					$this->time_status = self::STATUS_TYPE_NOTFOUND;
					return true;
				}
			}
		}
		if (count($prm)==5) {
			if (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $prm[3]) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $prm[3])
				|| preg_match('/\d\d\-\d\d\-\d\d/', $prm[3]) || preg_match('/\d\d\-\d\d/', $prm[3])
			) {
				$date = $prm[3];
				if (is_numeric($prm[4])) {
					$units = $prm[4];
					$type = '';
				} else {
					$units = 1;
					$type = $prm[4];
				}
			} elseif (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $prm[4]) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $prm[4])
				|| preg_match('/\d\d\-\d\d\-\d\d/', $prm[4]) || preg_match('/\d\d\-\d\d/', $prm[4])
			) {
				$date = $prm[4];
				if (is_numeric($prm[3])) {
					$units = $prm[3];
					$type = '';
				} else {
					$units = 1;
					$type = $prm[3];
				}
			} elseif (is_numeric($prm[3])) {
				$units = $prm[3];
				$type = $prm[4];
				$date = '';
			} elseif (is_numeric($prm[4])) {
				$units = $prm[4];
				$type = $prm[3];
				$date = '';
			} else {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			if ($date != '') {
				$this->processDateField($date);
				if ($this->time_status == self::STATUS_DATEFORMAT) {
					return true;
				}
				list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
				$date = mktime(0, 0, 0, $m, $d, $y);
			} else {
				$date = time();
			}
			$this->timeinfo['units'] = $units;
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname']);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				return true;
			} elseif ($type != '') {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			} else {
				$this->time_status = self::STATUS_MISSINGTYPE;
				return true;
			}
		}
		if (count($prm)==6) {
			if (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $prm[3]) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $prm[3])
				|| preg_match('/\d\d\-\d\d\-\d\d/', $prm[3]) || preg_match('/\d\d\-\d\d/', $prm[3])
			) {
				$date = $prm[3];
				if (is_numeric($prm[4])) {
					$units = $prm[4];
					$type = $prm[5];
				} else {
					$units = $prm[5];
					$type = $prm[4];
				}
			} elseif (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $prm[4]) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $prm[4])
				|| preg_match('/\d\d\-\d\d\-\d\d/', $prm[4]) || preg_match('/\d\d\-\d\d/', $prm[4])
			) {
				$date = $prm[4];
				if (is_numeric($prm[3])) {
					$units = $prm[3];
					$type = $prm[5];
				} else {
					$units = $prm[5];
					$type = $prm[3];
				}
			} elseif (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $prm[5]) || preg_match('/\d\d\-\d\d\-\d\d\d\d/', $prm[5])
				|| preg_match('/\d\d\-\d\d\-\d\d/', $prm[5]) || preg_match('/\d\d\-\d\d/', $prm[5])
			) {
				$date = $prm[5];
				if (is_numeric($prm[3])) {
					$units = $prm[3];
					$type = $prm[4];
				} else {
					$units = $prm[4];
					$type = $prm[3];
				}
			} else {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			$this->processDateField($date);
			if ($this->time_status == self::STATUS_DATEFORMAT) {
				return true;
			}
			list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
			$date = mktime(0, 0, 0, $m, $d, $y);
			$this->timeinfo['units'] = $units;
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname']);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				return true;
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		$this->time_status = self::STATUS_BADFORMAT;
		return true;
	}

	private function processTimeField($timevalue) {
		if (preg_match('/^[0-9:]+$/', $timevalue)) {
			if (!preg_match('/\d\d:\d\d/', $timevalue)) {
				if (strpos($timevalue, ':')!==false) {
					list($h, $m) = explode(':', $timevalue);
					$h = substr('00'.$h, -2);
					$m = substr('00'.$m, -2);
				} else {
					$h = (int)($timevalue / 60);
					$m = ($timevalue % 60);
					$h = substr('00'.$h, -2);
					$m = substr('00'.$m, -2);
				}
				$timevalue = $h.':'.$m;
			}
		}
		return $timevalue;
	}

	private function processDateField($datevalue) {
		if (preg_match('/\d\d\d\d\-\d\d\-\d\d/', $datevalue)) {
			list($y, $m, $d) = explode('-', $datevalue);
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/\d\d\-\d\d\-\d\d\d\d/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m, $y) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d, $y) = explode('-', $datevalue);
					break;
			}
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/\d\d\-\d\d\-\d\d/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m, $y) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d, $y) = explode('-', $datevalue);
					break;
				case 'yyyy-mm-dd':
					list($y, $m, $d) = explode('-', $datevalue);
					break;
			}
			$y = '20'.$y;
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/\d\d\-\d\d/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d) = explode('-', $datevalue);
					break;
				case 'yyyy-mm-dd':
					list($m, $d) = explode('-', $datevalue);
					break;
			}
			$y = date('Y');
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} else {
			$this->time_status = self::STATUS_DATEFORMAT;
			return true;
		}
		// ask for type
		$this->time_status = self::STATUS_MISSINGTYPE;
		$this->timeinfo['datestart'] = $y.'-'.$m.'-'.$d;
		$this->timeinfo['units'] = 1;
		return true;
	}

	public function getResponse() {
		global $current_user, $site_URL;
		if ($this->time_status == self::STATUS_BADFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('IncorrectFormat', 'chatwithme')."\n".getTranslatedString('sbcreatetime_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeFormat', 'chatwithme')."\n".getTranslatedString('sbcreatetime_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEOVER8) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeOver8', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEOVER8TOTAL) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeOver8Total', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_DATEFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadDateFormat', 'chatwithme')."\n".getTranslatedString('sbcreatetime_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TYPE_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('WorkTypeNotFound', 'chatwithme')."\n".getTranslatedString('sbcreatetime_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_MISSINGTYPE) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			coreBOS_Settings::setSetting($tcinfoid, json_encode($this->timeinfo));
			$chnlsep = '::';
			$chid = (isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : (isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : ''));
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = $site_URL.'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			$plvals = sbgetAllTypeOfWork($req['channel_dname']);
			asort($plvals);
			foreach ($plvals as $plid => $value) {
				$action_data = array(
					'name' => textlength_check(decode_html($value)),
					'integration' => array(
						'url' => $baseurl.'&wt='.$plid,
					));
				array_push($fieldsArray, $action_data);
			}
			$msglen = 200+strlen(json_encode($fieldsArray));
			if ($msglen>6500) {
				$fieldsArray = array();
				foreach ($plvals as $plid => $value) {
					$opdata = array(
						'text' => textlength_check(decode_html($value)),
						'value' => (string)$plid,
					);
					$fieldsArray[] =$opdata;
				}
				$fieldsArray = array(array(
					'name' => 'Select a type of work',
					'integration' => array(
						'url' => $baseurl,
					),
					'type' => 'select',
					'options' => $fieldsArray
				));
			}
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStoped2', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMER_CLOSED) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->timeinfo['time'].' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->timeinfo['title'].'"'
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->timeinfo['typeofwork'].'"',
			);
			return $ret;
		}
	}
}
?>
