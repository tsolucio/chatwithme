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

	public function getResponse() {
		global $current_user;
		global $adb;
		global $site_URL;
		$fieldsArray = array();
		$project_status = 'completed';
		$token = vtlib_purify($_REQUEST['token']);
		$res = $adb->pquery('select * from vtiger_project where projectstatus!=?', array($project_status));
		$baseurl = $site_URL.'/chatwithme.php?text=project&token='.$token.'&user_id='.$current_user->column_fields['mmuserid'];
		$g = 0;
		while ($data_array=$adb->fetch_array($res)) {
			if ($g == 15) {
				break;
			} else {
				$project_id =$data_array['projectid'];
				$project_name =$data_array['projectname'];
				$action_data = array(
					'name' => $project_name,
					'integration' => array(
						'url' => $baseurl.'&proj_id='.$project_id,
					));
				array_push($fieldsArray, $action_data);
				$g++;
			}
		}
		$ret = array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'color' => getMMMsgColor('yellow'),
				'title' => getTranslatedString('TimerStoped1', 'chatwithme').' Time '.getTranslatedString('TimerStoped2', 'chatwithme'),
				'actions' => $fieldsArray
			)),
		);
		sendMMMsg($ret, false);
	}
}
?>