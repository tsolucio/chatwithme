<?php
/*************************************************************************************************
 * Copyright 2019 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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

class cbmmActionsbsavetime extends chatactionclass {
	const STATUS_FOUND_OPEN_TIMER = 1;
	const STATUS_NO_OPEN_TIMER = 2;
	const BAD_CALL = 3;
	const STATUS_MISSINGTYPE = 4;
	const STATUS_MISSINGPRJTASK = 5;
	private $open_timer_status;
	private $project;
	private $ptask;
	private $subject;
	private $typeofwork;
	private $stoped_at;

	public function process() {
		global $adb, $current_user;
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		$req = getMMRequest();
		if (empty($_REQUEST['tc'])) {
			$this->open_timer_status = self::BAD_CALL;
			return true;
		}
		$tcinfo = coreBOS_Settings::getSetting($_REQUEST['tc'], '{}');
		coreBOS_Settings::delSetting($_REQUEST['tc']);
		$tcinfo = json_decode($tcinfo, true);
		if ($tcinfo==array() || json_last_error() !== JSON_ERROR_NONE) {
			return true;
		}
		$_REQUEST['team_dname'] = empty($tcinfo['team']) ? '' : $tcinfo['team'];
		if (!empty($tcinfo['recid'])) {
			$tcid = vtlib_purify($tcinfo['recid']);
			$res = $adb->pquery('select timecontrolid from vtiger_timecontrol where timecontrolid=?', array($tcid));
			if ($adb->num_rows($res) > 0) {
				$title = vtlib_purify($tcinfo['title']);
				$units = vtlib_purify($tcinfo['units']);
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (isset($_REQUEST['wt'])) {
					$typeofworkid = vtlib_purify($_REQUEST['wt']);
					$tcinfo['typeofwork'] = $typeofworkid;
					if ($prjtsk && (!isset($tcinfo['projecttask']) || $tcinfo['projecttask']=='')) {
						coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
						$this->open_timer_status = self::STATUS_MISSINGPRJTASK;
						return true;
					}
				} elseif (isset($_REQUEST['pt'])) {
					$ptask = vtlib_purify($_REQUEST['pt']);
					$tcinfo['projecttask'] = $ptask;
					if (empty($tcinfo['typeofwork'])) {
						coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
						$this->open_timer_status = self::STATUS_MISSINGTYPE;
						return true;
					}
				} else {
					$picklistvalue = vtlib_purify($_REQUEST['context']['selected_option']);
					if ($_REQUEST['pl']=='p') {
						$tcinfo['projecttask'] = $picklistvalue;
						if (empty($tcinfo['typeofwork'])) {
							coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
							$this->open_timer_status = self::STATUS_MISSINGTYPE;
							return true;
						}
					} else {
						$tcinfo['typeofwork'] = $picklistvalue;
						$typeofworkid = $picklistvalue;
						if (!isset($tcinfo['projecttask']) || $tcinfo['projecttask']=='') {
							coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
							$this->open_timer_status = self::STATUS_MISSINGPRJTASK;
							return true;
						}
					}
				}
				$tow = sbgetTypeOfWork($req['channel_dname'], $typeofworkid);
				$ptaskname = '';
				if ($prjtsk && (!isset($tcinfo['projecttask']) || $tcinfo['projecttask']=='')) {
					$projecttasks = sbgetAllProjectTasks($req['channel_dname'], false);
					$projecttask = $projecttasks[$tcinfo['projecttask']];
					$projecttasks = sbgetAllProjectTasks($req['channel_dname'], true);
					$ptaskname = $projecttasks[$tcinfo['projecttask']];
				}
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $title, $tow, $units, $tcinfo['team'], $projecttask, $tcinfo['projectsubtask']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->project = $result['relatedname'];
				$this->ptask = $ptaskname;
				$this->subject = $result['title'];
				$this->typeofwork = $tow;
				$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
			} else {
				$this->open_timer_status = self::STATUS_NO_OPEN_TIMER;
			}
		} else {
			if (!empty($tcinfo['datestart'])) {
				list($y, $m, $d) = explode('-', $tcinfo['datestart']);
				$date = mktime(0, 0, 0, $m, $d, $y);
			} else {
				$date = time();
			}
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			if (isset($_REQUEST['wt'])) {
				$typeofworkid = vtlib_purify($_REQUEST['wt']);
				$tcinfo['typeofwork'] = $typeofworkid;
				if ($prjtsk && (!isset($tcinfo['projecttask']) || $tcinfo['projecttask']=='')) {
					coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
					$this->open_timer_status = self::STATUS_MISSINGPRJTASK;
					return true;
				}
			} elseif (isset($_REQUEST['pt'])) {
				$ptask = vtlib_purify($_REQUEST['pt']);
				$tcinfo['projecttask'] = $ptask;
				if (empty($tcinfo['typeofwork'])) {
					coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
					$this->open_timer_status = self::STATUS_MISSINGTYPE;
					return true;
				}
			} else {
				$picklistvalue = vtlib_purify($_REQUEST['context']['selected_option']);
				if ($_REQUEST['pl']=='p') {
					$tcinfo['projecttask'] = $picklistvalue;
					if (empty($tcinfo['typeofwork'])) {
						coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
						$this->open_timer_status = self::STATUS_MISSINGTYPE;
						return true;
					}
				} else {
					$tcinfo['typeofwork'] = $picklistvalue;
					$typeofworkid = $picklistvalue;
					if ($prjtsk && (!isset($tcinfo['projecttask']) || $tcinfo['projecttask']=='')) {
						coreBOS_Settings::setSetting($_REQUEST['tc'], json_encode($tcinfo));
						$this->open_timer_status = self::STATUS_MISSINGPRJTASK;
						return true;
					}
				}
			}
			$tow = sbgetTypeOfWork($req['channel_dname'], $typeofworkid);
			if ($prjtsk) {
				$projecttasks = sbgetAllProjectTasks($req['channel_dname'], false);
				$projecttask = $projecttasks[$tcinfo['projecttask']];
				$projecttasks = sbgetAllProjectTasks($req['channel_dname'], true);
				$ptaskname = $projecttasks[$tcinfo['projecttask']];
			}
			$tcinfo['units'] = empty($tcinfo['units']) ? 1 : $tcinfo['units'];
			$tcinfo['projectsubtask'] = empty($tcinfo['projectsubtask']) ? '' : $tcinfo['projectsubtask'];
			$result = stoptimerDoCreateTC($date, $tcinfo['time'], $brand, $prjtype, $tcinfo['title'], $tow, $tcinfo['units'], $tcinfo['team'], $projecttask, $tcinfo['projectsubtask']);
			$time_array = explode(':', $result['totaltime']);
			$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
			$this->project = $result['relatedname'];
			$this->ptask = $ptaskname;
			$this->subject = $result['title'];
			$this->typeofwork = $tow;
			$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
		}
		return true;
	}

	public function getResponse() {
		global $site_URL;
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		$ret = array(
			'response_type' => 'in_channel',
			'text' => getTranslatedString('CallError', 'chatwithme'),
		);
		if ($this->open_timer_status == self::STATUS_FOUND_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->stoped_at.' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->subject.'"'
					.($prjtsk ? getTranslatedString('UpdateFeedback4', 'chatwithme').' "'.$this->ptask.'"' : '')
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->typeofwork.'"',
			);
		} elseif ($this->open_timer_status == self::STATUS_MISSINGTYPE) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = $_REQUEST['tc'];
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
						'url' => $baseurl.'&wt='.$plid.'&pl=t',
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
					'name' => getTranslatedString('SelectTOW', 'chatwithme'),
					'integration' => array(
						'url' => $baseurl.'&pl=t',
					),
					'type' => 'select',
					'options' => $fieldsArray
				));
			}
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedTOW', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_MISSINGPRJTASK) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = $_REQUEST['tc'];
			$chnlsep = '::';
			$chid = (isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : (isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : ''));
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = $site_URL.'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
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
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedPRT', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_NO_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('NoOpenTimer', 'chatwithme'),
			);
		}
		return $ret;
	}
}
?>
