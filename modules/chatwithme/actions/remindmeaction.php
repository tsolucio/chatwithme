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
require 'include/Webservices/Revise.php';

class cbmmActionremindmeaction extends chatactionclass {

	public function addDefault() {
		return false;
	}

	public function getResponse() {
		global $current_user, $adb;
		$ret = array(
			'update' => array(
				'color' => getMMMsgColor('red'),
				'message' => getTranslatedString('Reminder Command Not Recognized', 'chatwithme'),
			),
		);
		if (isset($_REQUEST['record']) && isset($_REQUEST['event'])) {
			$record = vtlib_purify($_REQUEST['record']);
			$wsrecord = vtws_getEntityId('cbCalendar').'x'.$record;
			$event = vtlib_purify($_REQUEST['event']);
			if ($event == 'postpone') {
				$rs = $adb->pquery('select dtstart, dtend from vtiger_activity where activityid=?', array($record));
				$dtst = explode(' ', $adb->query_result($rs, 0, 'dtstart'));
				list($sy, $sm, $sd) = explode('-', $dtst[0]);
				list($sh, $si, $ss) = explode(':', $dtst[1]);
				$dted = explode(' ', $adb->query_result($rs, 0, 'dtend'));
				list($ey, $em, $ed) = explode('-', $dted[0]);
				list($eh, $ei, $es) = explode(':', $dted[1]);
				$duration = GlobalVariable::getVariable('Calendar_call_default_duration', 10, 'chatwithme');
				switch ($current_user->date_format) {
					case 'dd-mm-yyyy':
						$dtstart=date('d-m-Y H:i:s', mktime($sh, $si+$duration, 0, $sm, $sd, $sy));
						$dtend=date('d-m-Y H:i:s', mktime($eh, $ei+$duration+1, 0, $em, $ed, $ey));
						break;
					case 'mm-dd-yyyy':
						$dtstart=date('m-d-Y H:i:s', mktime($sh, $si+$duration, 0, $sm, $sd, $sy));
						$dtend=date('m-d-Y H:i:s', mktime($eh, $ei+$duration+1, 0, $em, $ed, $ey));
						break;
					case 'yyyy-mm-dd':
					default:
						$dtstart=date('Y-m-d H:i:s', mktime($sh, $si+$duration, 0, $sm, $sd, $sy));
						$dtend=date('Y-m-d H:i:s', mktime($eh, $ei+$duration+1, 0, $em, $ed, $ey));
						break;
				}
				vtws_revise(array('id'=>$wsrecord, 'dtstart'=>$dtstart, 'dtend'=>$dtend), $current_user);
				$ret = array(
					'update' => array(
						'color' => getMMMsgColor('blue'),
						'message' => getTranslatedString('Reminder Postponed', 'chatwithme'),
					),
				);
			} elseif ($event == 'discard') {
				vtws_revise(array('id'=>$wsrecord, 'eventstatus'=>'Completed'), $current_user);
				$ret = array(
					'update' => array(
						'color' => getMMMsgColor('yellow'),
						'message' => getTranslatedString('Reminder Discarded', 'chatwithme'),
					),
				);
			}
		}
		return $ret;
	}
}
?>