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
class cbmmActionallprojects extends chatactionclass {

	public function addDefault() {
		return false;
	}

	public function echoResponse() {
		return false;
	}

	public function getResponse() {
		global $current_user;
		global $adb;
		global $site_URL;
		$proj_title = 'open timer';
		$fieldsArray = array();
		$project_status = 'completed';
		$token = vtlib_purify($_REQUEST['token']);
		$res = $adb->pquery('select * from vtiger_project where projectstatus!=?', array($project_status));
		$baseurl = $site_URL.'/notifications.php?type=CWM&text=project&token='.$token.'&user_id='.$current_user->column_fields['mmuserid'];
		$result = $adb->pquery('select * from vtiger_timecontrol where title=?', array($proj_title));
		$time_array = explode(':', $result->fields['totaltime']);
		$stoped_at = (int)$time_array[0].'h '.$time_array[1].'m';

		$ret = array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'color' => getMMMsgColor('yellow'),
				'title' => getTranslatedString('TimerStoped1', 'chatwithme') .$stoped_at.getTranslatedString('TimerStopedTOW', 'chatwithme'),
				'text' => getTranslatedString('TypeProject', 'chatwithme')."\n\n".$this->getProjects(),
			)),
		);
		return $ret;
	}

	private function getProjects() {
		global $adb;
		$project_status = 'completed';
		$res = $adb->pquery('select * from vtiger_project where projectstatus!=? ORDER BY projectid DESC LIMIT 1346444575705551615 OFFSET 20', array($project_status));
		$proj = '| ID | '.getTranslatedString('Pname', 'chatwithme').' |'."\n";
		$proj .= '|----|----|'."\n";
		while ($q = $adb->fetch_array($res)) {
			$proj .= '| '.$q['projectid'].' | '.$q['projectname']."\n";
		}
		return $proj;
	}
}
?>