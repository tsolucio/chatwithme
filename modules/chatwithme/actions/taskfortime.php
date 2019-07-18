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

class cbmmActiontaskfortime extends chatactionclass {
	const TITLE = 'open timer';
	const STATUS_FOUND_OPEN_TIMER = 1;
	const STATUS_NO_OPEN_TIMER = 2;
	const BAD_CALL = 3;
	private $open_timer_status;
	private $project;
	private $subject;
	private $stoped_at;

	public function process() {
		global $current_user, $adb;
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		$this->subject = implode(' ', array_slice($prm, 1, count($prm)));
		if ($this->subject != '') {
			$res = $adb->pquery(
				'select timecontrolid
					from vtiger_timecontrol
					inner join vtiger_crmentity on crmid=timecontrolid
					where deleted=0 and title=? and smownerid=? limit 1',
				array(self::TITLE, $current_user->id)
			);
			if ($adb->num_rows($res) > 0) {
				$record_id = vtlib_purify($res->fields['timecontrolid']);
				$data = array(
					'id' =>vtws_getEntityId('Timecontrol').'x'.$record_id,
					'title' => $this->subject,
				);
				$result = vtws_revise($data, $current_user);
				$time_array = explode(':', $result['totaltime']);
				$this->stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->project = $result['relatedname'];
				$this->subject = $result['title'];
				$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
			} else {
				$this->open_timer_status = self::STATUS_NO_OPEN_TIMER;
			}
		} else {
			$this->open_timer_status = self::BAD_CALL;
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
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->stoped_at.' '.getTranslatedString('For', 'chatwithme').' '.
				$this->project.getTranslatedString('UpdateFeedback2', 'chatwithme').' '.$this->subject,
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
