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

class cbmmActionproject extends chatactionclass {

	public function echoResponse() {
		return false;
	}

	public function getResponse() {
		global $current_user, $adb;
		$ret = array(
			'response_type' => 'in_channel',
			'text' => getTranslatedString('CallError', 'chatwithme')
		);
		if ((isset($_REQUEST['proj_id'])) && (isset($_REQUEST['recid'])) && isset($_REQUEST['call'])) {
			$pid = vtlib_purify($_REQUEST['proj_id']);
			$recid =vtws_getEntityId('Timecontrol').'x'.vtlib_purify($_REQUEST['recid']);
			$call = vtlib_purify($_REQUEST['call']);
			$res = $adb->pquery('select * from vtiger_project where projectid=?', array($pid));
			$data = array(
				'id' =>$recid,
				'relatedto' => vtws_getEntityId('Project').'x'.$pid
			);
			$result = vtws_revise($data, $current_user);
			if ($call == 'logtime') {
				$ret = array(
					'response_type' => 'in_channel',
					'text' => getTranslatedString('ProjectAdded1', 'chatwithme').$result['relatedname'].' '.getTranslatedString('AddLogTime', 'chatwithme'),
				);
			}
			if ($call == 'starttimer') {
				$ret = array(
					'response_type' => 'in_channel',
					'text' => getTranslatedString('ProjectAdded1', 'chatwithme').$result['relatedname'].' '.getTranslatedString('ProjectAdded2', 'chatwithme'),
				);
			}
		}
		return $ret;
	}
}
?>