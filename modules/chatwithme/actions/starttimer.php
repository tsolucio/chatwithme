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
require 'include/Webservices/Create.php';
class cbmmActionstarttimer extends chatactionclass {
	private const TITLE = 'open timer';
	private const STATUS_FOUND_OPEN_TIMER = 1;
	private const STATUS_NO_OPEN_TIMER = 2;
	private $open_timer_status;
	public function getHelp() {
		return getTranslatedString('starttimer_command', 'chatwithme');
	}
	public function process() {
		global $current_user, $adb;
		$res = $adb->pquery('select * from vtiger_timecontrol where title=?', array(self::TITLE));
		if ($adb->num_rows($res) > 0) {
			$this->open_timer_status = self::STATUS_FOUND_OPEN_TIMER;
		} else {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					$start_date = date('Y-m-d');
					break;
				case 'mm-dd-yyyy':
					$start_date = date('m-d-Y');
					break;
				case 'yyyy-mm-dd':
					$start_date = date('Y-m-d');
					break;
				default:
					$start_date = date('Y-m-d');
					break;
			}
			$start_time = date('H:i:s');
			$data = array(
				'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
				'title' => self::TITLE,
				'date_start' => $start_date,
				'time_start' => $start_time
			);
			vtws_create('Timecontrol', $data, $current_user);
			$this->open_timer_status = self::STATUS_NO_OPEN_TIMER;
		}
		return true;
	}
	public function getResponse() {
		if ($this->open_timer_status == self::STATUS_FOUND_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('ThereIsOpenTimer', 'chatwithme'),
				)),
			);
			return $ret;
		} if ($this->open_timer_status == self::STATUS_NO_OPEN_TIMER) {
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('StartedNewTimer', 'chatwithme'),
			);
			return $ret;
		}
	}
}
?>