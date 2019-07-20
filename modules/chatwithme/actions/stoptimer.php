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

class cbmmActionstoptimer extends chatactionclass {
	const TITLE = 'open timer';
	const CALL_FROM = 'starttimer';
	const STATUS_FOUND_OPEN_TIMER = 1;
	const STATUS_NO_OPEN_TIMER = 2;
	private $open_timer_status;
	private $recid;
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
		if ($adb->num_rows($res) > 0) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					$current_date = date('Y-m-d H:i:s');
					break;
				case 'mm-dd-yyyy':
					break;
				case 'yyyy-mm-dd':
					$current_date = date('Y-m-d');
					$current_date = date('Y-m-d');
					break;
				default:
					$current_date = date('Y-m-d');
					break;
			}
			$time_end = date('H:i:s');
			$record_id = vtlib_purify($res->fields['timecontrolid']);
			$this->recid  = $record_id;
			$data = array(
				'id' =>vtws_getEntityId('Timecontrol').'x'.$record_id,
				'date_end' => $current_date,
				'time_end' => $time_end
			);
			$result = vtws_revise($data, $current_user);
			$time_array = explode(':', $result['totaltime']);
			$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
			$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
		} else {
			$this->open_timer_status = self::STATUS_NO_OPEN_TIMER;
		}
		return true;
	}

	public function getResponse() {
		global $adb, $current_user, $site_URL;
		$req = getMMRequest();
		$project_status = 'completed';
		$fieldsArray = array();
		$baseurl = $site_URL.'/notifications.php?type=CWM&text=project&token='.$req['token']
			.'&user_id='.$current_user->column_fields['mmuserid'].'&recid='.$this->recid.'&call='.self::CALL_FROM
			.(isset($_REQUEST['chnl_name']) ? '&chnl_name='.$_REQUEST['chnl_name'] : '');
		$baseurl1 = $site_URL.'/notifications.php?type=CWM&text=allprojects&token='.$req['token'].'&user_id='.$current_user->column_fields['mmuserid']
			.(isset($_REQUEST['chnl_name']) ? '&chnl_name='.$_REQUEST['chnl_name'] : '');
		if ($this->open_timer_status == self::STATUS_NO_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('NoOpenTimer', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->open_timer_status == self::STATUS_FOUND_OPEN_TIMER) {
			$res = $adb->pquery('select * from vtiger_project where projectstatus!=? ORDER BY projectid DESC', array($project_status));
			$g = 0;
			while ($data_array=$adb->fetch_array($res)) {
				if ($g == 12) {
					break;
				} else {
					$project_id = $data_array['projectid'];
					$project_name = decode_html($data_array['projectname']);
					$action_data = array(
						'name' => $project_name,
						'integration' => array(
							'url' => $baseurl.'&proj_id='.$project_id,
						));
					array_push($fieldsArray, $action_data);
					$g++;
				}
			}
			$action_showall = array(
				'name' => getTranslatedString('ShowAll', 'chatwithme'),
				'integration' => array(
					'url'=> $baseurl1.'&event=showAll',
				));
			if ((count($fieldsArray) > 0) && $adb->num_rows($res)) {
				array_push($fieldsArray, $action_showall);
			}
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').$this->stoped_at.getTranslatedString('TimerStoped2', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		}
	}
}
?>
