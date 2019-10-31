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
	private $open_timer_status;
	private $project;
	private $subject;
	private $typeofwork;
	private $stoped_at;

	public function process() {
		global $adb, $current_user;
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
				} else {
					$typeofworkid = vtlib_purify($_REQUEST['context']['selected_option']);
				}
				$tow = sbgetTypeOfWork($req['channel_dname'], $typeofworkid);
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $title, $tow, $units, $tcinfo['team']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->project = $result['relatedname'];
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
			} else {
				$typeofworkid = vtlib_purify($_REQUEST['context']['selected_option']);
			}
			$tow = sbgetTypeOfWork($req['channel_dname'], $typeofworkid);
			$result = stoptimerDoCreateTC($date, $tcinfo['time'], $brand, $prjtype, $tcinfo['title'], $tow, $tcinfo['units'], $tcinfo['team']);
			$time_array = explode(':', $result['totaltime']);
			$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
			$this->project = $result['relatedname'];
			$this->subject = $result['title'];
			$this->typeofwork = $tow;
			$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
		}
		return true;
	}

	public function getResponse() {
		$ret = array(
			'response_type' => 'in_channel',
			'text' => getTranslatedString('CallError', 'chatwithme'),
		);
		if ($this->open_timer_status == self::STATUS_FOUND_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->stoped_at.' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->subject.'"'
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->typeofwork.'"',
			);
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
