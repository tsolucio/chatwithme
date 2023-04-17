<?php
/*************************************************************************************************
 * Copyright 2020 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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

class cbmmActionvideo extends chatactionclass {
	private $name;

	public function getHelp() {
		return ' - '.getTranslatedString('video_command', 'chatwithme');
	}

	public function echoResponse() {
		return true;
	}

	public function process() {
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		if (count($prm)>1) {
			$this->name = str_replace(' ', '_', $prm[1]);
		} elseif (!empty($req['channel_dname'])) {
			$this->name = str_replace(' ', '_', $req['channel_dname']);
		} elseif (!empty($req['user_name'])) {
			$this->name = str_replace(' ', '_', $req['user_name']);
		} else {
			global $adb;
			$u = $adb->pquery('select first_name,last_name from vtiger_users where mmuserid=?', array($req['user_id']));
			$this->name = str_replace(' ', '_', $u->fields['first_name'].'_'.$u->fields['last_name']);
		}
		return true;
	}

	public function getResponse() {
		global $configmm;
		if (empty($configmm)) {
			$configmm = getMMSettings();
		}
		return array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'author_name' => 'meeting',
				'author_icon' => $configmm['icon_url'],
				'title' => getTranslatedString('JoinMeeting', 'chatwithme'),
				'title_link' => 'https://meet.jit.si/'.$this->name,
				'text' => getTranslatedString('RoomCreated', 'chatwithme'),
				'color' => '#FF7656',
			)),
		);
	}
}
?>
