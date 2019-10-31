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
	private $open_timer_status;
	private $recid;
	private $units = 1;
	private $title = '';
	private $typeofwork = '';
	private $stoped_at;

	public function getHelp() {
		return ' - '.getTranslatedString('stoptimer_command', 'chatwithme');
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
		$prm = parseMMMsgWithQuotes($req['text']);
		if (count($prm)<2) {
			$this->open_timer_status = self::STATUS_NO_DESCRIPTION;
			return true;
		}
		$this->title = $prm[1];
		if (count($prm)==2) {
			// ask for type of request
			$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
			return true;
		}
		if (count($prm)==3) {
			$param = $prm[2];
			if (is_numeric($param)) {
				// we have units > ask for type of request
				$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
				$this->units = $param;
				return true;
			} else {
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$tow = sbgetTypeOfWork($req['channel_dname'], $param);
				if ($tow) {
					// we have all we need, units=1
					$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $param, 1, $req['team_dname']);
					$time_array = explode(':', $result['totaltime']);
					$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
					$this->typeofwork = $param;
					$this->open_timer_status = self::STATUS_TIMER_CLOSED;
					return true;
				} else {
					$this->open_timer_status = self::STATUS_TYPE_NOTFOUND;
					return true;
				}
			}
		}
		if (count($prm)==4) {
			if (is_numeric($prm[2])) {
				$units = $prm[2];
				$type = $prm[3];
			} else {
				$units = $prm[3];
				$type = $prm[2];
			}
			if (!is_numeric($units)) {
				$this->open_timer_status = self::STATUS_BADFORMAT;
				return true;
			}
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				$result = stoptimerDoUpdateTC($tcid, $brand, $prjtype, $prm[1], $type, $units, $req['team_dname']);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->typeofwork = $type;
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
		global $adb, $current_user, $site_URL;
		if ($this->open_timer_status == self::STATUS_NO_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('NoOpenTimer', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_NO_DESCRIPTION) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('NoTimeDescription', 'chatwithme')."\n".getTranslatedString('stoptimer_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_TYPE_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('WorkTypeNotFound', 'chatwithme')."\n".getTranslatedString('stoptimer_command', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_FOUND_OPEN_TIMER) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			$tcinfo = array(
				'team' => $req['team_dname'],
				'units' => $this->units,
				'title' => $this->title,
				'recid' => $this->recid,
			);
			coreBOS_Settings::setSetting($tcinfoid, json_encode($tcinfo));
			$chnlsep = '::';
			$chid = (isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : (isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : ''));
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = $site_URL.'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			//$baseurl = 'http://198.199.127.108/stc/n.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
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
		} elseif ($this->open_timer_status == self::STATUS_TIMER_CLOSED) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->stoped_at.' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->title.'"'
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->typeofwork.'"',
			);
			return $ret;
		}
	}
}
?>
