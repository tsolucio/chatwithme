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
require_once 'include/Webservices/Revise.php';
require_once 'modules/chatwithme/subitoutils.php';

class cbmmActionstoptimer extends chatactionclass {
	const TITLE = 'open timer';
	const STATUS_FOUND_OPEN_TIMER = 1;
	const STATUS_NO_OPEN_TIMER = 2;
	const STATUS_NO_DESCRIPTION = 3;
	const STATUS_BADFORMAT = 4;
	const STATUS_TYPE_NOTFOUND = 5;
	const STATUS_TIMER_CLOSED = 6;
	const STATUS_MISSINGTYPE = 7;
	const STATUS_MISSINGPRJTASK = 8;

	const NOOP = -1;
	const STATUS_DATEFORMAT = 4;
	const STATUS_TIMEFORMAT = 5;
	const STATUS_TIMEOVER8TOTAL = 7;
	const STATUS_TIMEOVER8 = 8;
	const STATUS_PRJTASK_NOTFOUND = 10;
	const STATUS_PRJSUBTASK_NOTFOUND = 11;
	const STATUS_PRJTASKSTATUS_NOTFOUND = 12;
	const STATUS_PRJSUBTASKSTATUS_NOTFOUND = 13;
	const STATUS_TIMER_CLOSED_STATUSNOTCHANGED=14;

	public $timeinfo = array();
	private $open_timer_status;
	private $recid;
	private $units = 1;
	private $title = '';
	private $typeofwork = '';
	private $stoped_at;
	private $ptask;

	public function getHelp() {
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		if ($prjsubtsk) {
			return ' - '.getTranslatedString('stoptimer_commandTskSubTsk', 'chatwithme');
		} elseif ($prjtsk && !$prjsubtsk) {
			return ' - '.getTranslatedString('stoptimer_commandTsk', 'chatwithme');
		} else {
			return ' - '.getTranslatedString('stoptimer_command', 'chatwithme');
		}
	}

	public function process() {
		global $current_user, $adb;
		$res = $adb->pquery(
			'select timecontrolid
				from vtiger_timecontrol
				inner join vtiger_crmentity on crmid=timecontrolid
				where deleted=0 and title=? and smownerid=? limit 1',
			array(self::TITLE, $current_user->id)
		);
		if ($adb->num_rows($res) == 0) {
			$this->open_timer_status = self::STATUS_NO_OPEN_TIMER;
			return true;
		}
		$tcid = $res->fields['timecontrolid'];
		$this->recid  = $tcid;
		$req = getMMRequest();
		$this->timeinfo['team'] = $req['team_dname'];
		$this->timeinfo['user_id'] = $req['user_id'];
		$prm = parseMMMsgWithQuotes($req['text']);
		if (count($prm)<2) {
			$this->open_timer_status = self::STATUS_NO_DESCRIPTION;
			return true;
		}
		$this->title = $this->timeinfo['title'] = $prm[1];
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		if ($prjsubtsk) {
			$paramoffset = 2;
		} else {
			$paramoffset = 0;
		}
		// validate project task and project subtask
		if ($prjsubtsk) {
			if (count($prm)<4) {
				$this->open_timer_status = self::STATUS_BADFORMAT;
				return true;
			}
			if (!sbProjectTaskExist($req['channel_dname'], $prm[2])) {
				$this->open_timer_status = self::STATUS_PRJTASK_NOTFOUND;
				return true;
			}
			$this->timeinfo['projecttask'] = $prm[2];
			if (!sbProjectSubTaskExist($req['channel_dname'], $prm[2], $prm[3])) {
				$this->open_timer_status = self::STATUS_PRJSUBTASK_NOTFOUND;
				return true;
			}
			$this->timeinfo['projectsubtask'] = $prm[3];
		}
		if (count($prm)==2+$paramoffset) {
			// ask for type of request
			$this->open_timer_status = self::STATUS_MISSINGTYPE;
			return true;
		}
		if (count($prm)==3+$paramoffset) {
			$param = $prm[2+$paramoffset];
			if (is_numeric($param)) {
				// we have units > ask for type of request
				$this->open_timer_status = self::STATUS_MISSINGTYPE;
				$this->units = $this->timeinfo['units'] = $param;
				return true;
			} else {
				$this->units = $this->timeinfo['units'] = 1;
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$tow = sbgetTypeOfWork($req['channel_dname'], $param);
				if ($tow) {
					if ($prjtsk && !$prjsubtsk) {
						$this->open_timer_status = self::STATUS_MISSINGPRJTASK;
						$this->typeofwork = $this->timeinfo['typeofwork'] = $tow;
						return true;
					}
					// we have all we need, units=1
					$this->timeinfo['projecttask'] = empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask'];
					$this->timeinfo['projectsubtask'] = empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask'];
					$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $param, 1, $req['team_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask']);
					$time_array = explode(':', $result['totaltime']);
					$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
					$this->typeofwork = $param;
					$this->open_timer_status = self::STATUS_TIMER_CLOSED;
					return true;
				} else {
					if ($prjtsk) {
						if ($prjsubtsk) {
							$sttsk = sbgetPrjSubTaskStatus($param);
						} else {
							$sttsk = sbgetPrjTaskStatus($param);
						}
						if ($sttsk) {
							// we have status > ask for type of request
							$this->time_status = self::STATUS_MISSINGTYPE;
							$this->timeinfo['taskstatus'] = $param;
							return true;
						} elseif ($prjsubtsk) {
							$this->time_status = self::STATUS_TYPE_NOTFOUND;
							return true;
						} else {
							if (sbProjectTaskExist($req['channel_dname'], $param)) {
								// we have task > ask for type of request
								$this->time_status = self::STATUS_MISSINGTYPE;
								$this->timeinfo['projecttask'] = $param;
								return true;
							}
							$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
							return true;
						}
					}
					$this->open_timer_status = self::STATUS_TYPE_NOTFOUND;
					return true;
				}
			}
		}
		if (count($prm)==4+$paramoffset) {
			$param2 = $prm[2+$paramoffset];
			$param3 = $prm[3+$paramoffset];
			$decide = $units = null;
			if (is_numeric($param2)) {
				$units = $param2;
				$this->units = $this->timeinfo['units'] = $param2;
				$decide = $param3;
			} elseif (is_numeric($param3)) {
				$units = $param3;
				$this->units = $this->timeinfo['units'] = $param3;
				$decide = $param2;
			}
			if ($decide==null && !$prjtsk) {
				$this->open_timer_status = self::STATUS_BADFORMAT;
				return true;
			}
			if ($units == null) {
				$units = 1;
				$this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process2UnkownTextField($param2, $param3, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} else {
				$this->time_status = self::NOOP;
				$this->processUnkownTextField($decide, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			}
			if (!empty($this->timeinfo['typeofwork'])
				&& (!$prjtsk || !empty($this->timeinfo['projecttask']))
				&& (!$prjsubtsk || !empty($this->timeinfo['projectsubtask']))
			) {
				$this->typeofwork = $this->timeinfo['typeofwork'];
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$this->timeinfo['projecttask'] = empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask'];
				$this->timeinfo['projectsubtask'] = empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask'];
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $this->typeofwork, $units, $req['team_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->open_timer_status = self::STATUS_TIMER_CLOSED;
				return true;
			} else {
				$this->open_timer_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		if ($prjtsk && count($prm)==5+$paramoffset) {
			$param2 = $prm[2+$paramoffset];
			$param3 = $prm[3+$paramoffset];
			$param4 = $prm[4+$paramoffset];
			$units = null;
			if (is_numeric($param2)) {
				$units = $param2;
				$decide1 = $param3;
				$decide2 = $param4;
				$this->units = $this->timeinfo['units'] = $param2;
			} elseif (is_numeric($param3)) {
				$units = $param3;
				$decide1 = $param2;
				$decide2 = $param4;
				$this->units = $this->timeinfo['units'] = $param3;
			} elseif (is_numeric($param4)) {
				$units = $param4;
				$decide1 = $param2;
				$decide2 = $param3;
				$this->units = $this->timeinfo['units'] = $param4;
			}
			if ($units==null && $prjsubtsk) {
				$this->open_timer_status = self::STATUS_BADFORMAT;
				return true;
			}
			if ($units == null) { // project task > no subtask
				$units = 1;
				$this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process3UnkownTextField($param2, $param3, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} else {
				$this->time_status = self::NOOP;
				$this->process2UnkownTextField($decide1, $decide2, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			}
			if (!empty($this->timeinfo['typeofwork'])
				&& (!$prjtsk || !empty($this->timeinfo['projecttask']))
				&& (!$prjsubtsk || !empty($this->timeinfo['projectsubtask']))
			) {
				$this->typeofwork = $this->timeinfo['typeofwork'];
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$this->timeinfo['projecttask'] = empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask'];
				$this->timeinfo['projectsubtask'] = empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask'];
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $this->typeofwork, $units, $req['team_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->open_timer_status = self::STATUS_TIMER_CLOSED;
				return true;
			} else {
				$this->open_timer_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		if ($prjtsk && !$prjsubtsk && count($prm)==6) {
			$param2 = $prm[2+$paramoffset];
			$param3 = $prm[3+$paramoffset];
			$param4 = $prm[4+$paramoffset];
			$param5 = $prm[5+$paramoffset];
			$units = null;
			if (is_numeric($param2)) {
				$units = $param2;
				$this->units = $this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process3UnkownTextField($param3, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} elseif (is_numeric($param3)) {
				$units = $param3;
				$this->units = $this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process3UnkownTextField($param2, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} elseif (is_numeric($param4)) {
				$units = $param4;
				$this->units = $this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process3UnkownTextField($param2, $param3, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} elseif (is_numeric($param5)) {
				$units = $param5;
				$this->units = $this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process3UnkownTextField($param2, $param3, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} else {
				$this->open_timer_status = self::STATUS_BADFORMAT;
				return true;
			}
			if (!empty($this->timeinfo['typeofwork'])
				&& (!$prjtsk || !empty($this->timeinfo['projecttask']))
				&& (!$prjsubtsk || !empty($this->timeinfo['projectsubtask']))
			) {
				$this->typeofwork = $this->timeinfo['typeofwork'];
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$this->timeinfo['projecttask'] = empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask'];
				$this->timeinfo['projectsubtask'] = empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask'];
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $this->typeofwork, $units, $req['team_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->open_timer_status = self::STATUS_TIMER_CLOSED;
				return true;
			} else {
				$this->open_timer_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		return true;
	}

	public function getResponse() {
		global $site_URL;
		if (empty($this->open_timer_status)) {
			$this->open_timer_status = $this->time_status;
		}
		if ($this->open_timer_status == self::STATUS_NO_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('NoOpenTimer', 'chatwithme'),
				)),
			);
		} elseif ($this->open_timer_status == self::STATUS_NO_DESCRIPTION) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('NoTimeDescription', 'chatwithme')."\n".getTranslatedString('stoptimer_command', 'chatwithme'),
				)),
			);
		} elseif ($this->open_timer_status == self::STATUS_TYPE_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('WorkTypeNotFound', 'chatwithme')."\n".getTranslatedString('stoptimer_command', 'chatwithme'),
				)),
			);
		} elseif ($this->open_timer_status == self::STATUS_MISSINGTYPE) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			$this->timeinfo['team']  = $req['team_dname'];
			$this->timeinfo['user_id'] = $req['user_id'];
			$this->timeinfo['units'] = $this->units;
			$this->timeinfo['title'] = $this->title;
			$this->timeinfo['recid'] = $this->recid;
			coreBOS_Settings::setSetting($tcinfoid, json_encode($this->timeinfo));
			$chnlsep = '::';
			$chnlid = isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : '';
			$chid = isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : $chnlid;
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = trim($site_URL, '/').'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			//$baseurl = 'http://198.199.127.108/stc/n.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			$plvals = sbgetAllTypeOfWork($req['channel_dname']);
			asort($plvals);
			foreach ($plvals as $plid => $value) {
				$action_data = array(
					'name' => textlength_check(decode_html($value)),
					'integration' => array(
						'url' => $baseurl.'&wt='.$plid.'&pl=t',
					));
				array_push($fieldsArray, $action_data);
			}
			$msglen = 200+strlen(json_encode($fieldsArray));
			if ($msglen>6460) {
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
				'ephemeral_text' => getTranslatedString('LBL_SELECT_BUTTON_LABEL'),
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedTOW', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
		} elseif ($this->open_timer_status == self::STATUS_MISSINGPRJTASK) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			$this->timeinfo['team']  = $req['team_dname'];
			$this->timeinfo['user_id'] = $req['user_id'];
			$this->timeinfo['units'] = $this->units;
			$this->timeinfo['title'] = $this->title;
			$this->timeinfo['recid'] = $this->recid;
			coreBOS_Settings::setSetting($tcinfoid, json_encode($this->timeinfo));
			$chnlsep = '::';
			$chnlid = isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : '';
			$chid = isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : $chnlid;
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = trim($site_URL, '/').'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			$plvals = sbgetAllProjectTasks($req['channel_dname']);
			asort($plvals);
			foreach ($plvals as $plid => $value) {
				$action_data = array(
					'name' => textlength_check(decode_html($value)),
					'integration' => array(
						'url' => $baseurl.'&pt='.$plid.'&pl=p',
					));
				array_push($fieldsArray, $action_data);
			}
			$msglen = 200+strlen(json_encode($fieldsArray));
			if ($msglen>6460) {
				$fieldsArray = array();
				foreach ($plvals as $plid => $value) {
					$opdata = array(
						'text' => textlength_check(decode_html($value)),
						'value' => (string)$plid,
					);
					$fieldsArray[] =$opdata;
				}
				$fieldsArray = array(array(
					'name' => getTranslatedString('SelectPRJ', 'chatwithme'),
					'integration' => array(
						'url' => $baseurl.'&pl=p',
					),
					'type' => 'select',
					'options' => $fieldsArray
				));
			}
			$ret = array(
				'response_type' => 'in_channel',
				'ephemeral_text' => getTranslatedString('LBL_SELECT_BUTTON_LABEL'),
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedPRT', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
		} elseif ($this->open_timer_status == self::STATUS_TIMER_CLOSED) {
			$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
			$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
			if ($prjsubtsk && !$prjtsk) {
				$prjtsk = 1;
			}
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->stoped_at.' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->title.'"'
					.($prjtsk ? getTranslatedString('UpdateFeedback4', 'chatwithme').' "'.$this->ptask.'"' : '')
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->typeofwork.'"',
			);
		} else { //$this->open_timer_status == self::STATUS_BADFORMAT or anything else
			$helpcommand = substr($this->getHelp(), 3);
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('IncorrectFormat', 'chatwithme')."\n".$helpcommand,
				)),
			);
		}
		return $ret;
	}

	private function processUnkownTextField($unknownfield, $chname, $prjtsk, $prjsubtsk) {
		$tow = sbgetTypeOfWork($chname, $unknownfield);
		if ($tow) {
			$this->timeinfo['typeofwork'] = $unknownfield;
			$this->timeinfo['taskstatus'] = '';
			if ($prjtsk && !$prjsubtsk) {
				$this->time_status = self::STATUS_MISSINGPRJTASK;
				return true;
			}
		} else {
			$workwith = $unknownfield;
			if ($prjsubtsk) {
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > ask for type of request
					$this->time_status = self::STATUS_MISSINGTYPE;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk && !$prjsubtsk) {
				$sttsk = sbgetPrjTaskStatus($workwith);
				if (!$sttsk) {
					if (sbProjectTaskExist($chname, $workwith)) {
						// we have task > ask for type of request
						$this->time_status = self::STATUS_MISSINGTYPE;
						$this->timeinfo['projecttask'] = $workwith;
						return true;
					}
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				} else {
					// we have status > ask for project task
					$this->time_status = self::STATUS_MISSINGPRJTASK;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				}
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
	}

	private function process2UnkownTextField($unknownfield1, $unknownfield2, $chname, $prjtsk, $prjsubtsk) {
		$tow = sbgetTypeOfWork($chname, $unknownfield1);
		if ($tow) {
			$this->timeinfo['typeofwork'] = $unknownfield1;
			$workwith = $unknownfield2;
			if ($prjsubtsk) {
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk) {
				$sttsk = sbgetPrjTaskStatus($workwith);
				if ($sttsk) {
					// we have status > ask for project task
					$this->time_status = self::STATUS_MISSINGPRJTASK;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					if (sbProjectTaskExist($chname, $workwith)) {
						// we have task > we have all we need
						$this->time_status = self::NOOP;
						$this->timeinfo['projecttask'] = $workwith;
						return true;
					}
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			}
			$this->timeinfo['taskstatus'] = '';
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		} else {
			$workwith = $unknownfield1;
			if ($prjsubtsk) {
				$this->timeinfo['typeofwork'] = $unknownfield2;
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk) {
				if (sbgetPrjTaskStatus($workwith)) {
					$this->timeinfo['taskstatus'] = $unknownfield1;
					$workwith = $unknownfield2;
				} else {
					if (sbgetPrjTaskStatus($unknownfield2)) {
						$this->timeinfo['taskstatus'] = $unknownfield2;
					} elseif (sbgetTypeOfWork($chname, $unknownfield2)) {
						$this->timeinfo['typeofwork'] = $unknownfield2;
					} else {
						$this->time_status = self::STATUS_BADFORMAT;
						return true;
					}
					$workwith = $unknownfield1;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					$this->timeinfo['projecttask'] = $workwith;
					if (!empty($this->timeinfo['typeofwork'])) {
						// we have all we need
						$this->time_status = self::NOOP;
						return true;
					}
					// we have task > ask for type of request
					$this->time_status = self::STATUS_MISSINGTYPE;
					return true;
				}
				if (sbgetTypeOfWork($chname, $workwith)) {
					$this->timeinfo['typeofwork'] = $workwith;
				}
				$this->time_status = self::STATUS_MISSINGPRJTASK;
				return true;
			} else {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
		}
	}

	private function process3UnkownTextField($unknownfield1, $unknownfield2, $unknownfield3, $chname, $prjtsk, $prjsubtsk) {
		if (sbgetTypeOfWork($chname, $unknownfield1)) {
			$this->timeinfo['typeofwork'] = $unknownfield1;
			$workwith = $unknownfield2;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield3)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield3;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield3)) {
					$this->timeinfo['taskstatus'] = $unknownfield3;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} elseif (sbgetTypeOfWork($chname, $unknownfield2)) {
			$this->timeinfo['typeofwork'] = $unknownfield2;
			$workwith = $unknownfield1;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield3)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield3;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield3)) {
					$this->timeinfo['taskstatus'] = $unknownfield3;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} elseif (sbgetTypeOfWork($chname, $unknownfield3)) {
			$this->timeinfo['typeofwork'] = $unknownfield3;
			$workwith = $unknownfield1;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield2)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield2;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield2)) {
					$this->timeinfo['taskstatus'] = $unknownfield2;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} else {
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		}
	}
}
?>
