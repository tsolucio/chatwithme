<?php
/*************************************************************************************************
 * Copyright 2022 Spike, JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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
require_once 'include/Webservices/Create.php';

class cbmmActioncomment extends chatactionclass {
	const STATUS_FOUND = 1;
	const STATUS_NOT_FOUND = 2;
	const BAD_FORMAT = 3;
	const RECORD_NOT_FOUND = 4;
	private $status;

	public function getHelp() {
		return ' - '.getTranslatedString('comment_command', 'chatwithme');
	}

	public function process() {
		global $current_user;
		$req = getMMRequest();
		$prm = parseMMMsgWithQuotes($req['text']);
		if (isset($prm[1]) && isset($prm[2])) {
			if (!isRecordExists($prm[1])) {
				$this->status = self::RECORD_NOT_FOUND;
			} else {
				$data = array(
					'related_to' => $prm[1],
					'commentcontent' => $prm[2],
					'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
				);
				try {
					vtws_create('ModComments', $data, $current_user);
					$this->status = self::STATUS_FOUND;
				} catch (\Throwable $err) {
					$this->status = self::STATUS_NOT_FOUND;
				}
			}
		} else {
			$this->status = self::BAD_FORMAT;
		}
		return true;
	}

	public function getResponse() {
		switch ($this->status) {
			case self::STATUS_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('green'),
						'text' => getTranslatedString('CommentAdded', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_NOT_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('yellow'),
						'text' => getTranslatedString('CommentNotAdded', 'chatwithme'),
					)),
				);
				break;
			case self::RECORD_NOT_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'text' => getTranslatedString('RecordNotFound', 'chatwithme'),
					)),
				);
				break;
			case self::BAD_FORMAT:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('yellow'),
						'text' => $this->getHelp(),
					)),
				);
				break;
		}
		return $ret;
	}
}
?>