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
require_once 'include/Webservices/ValidateInformation.php';

class cbmmActionupdate extends chatactionclass {
	const STATUS_FOUND = 1;
	const STATUS_NOT_FOUND = 2;
	const BAD_FORMAT = 3;
	const INVALID_FIELD = 4;
	private $status;

	public function getHelp() {
		return ' - '.getTranslatedString('update_command', 'chatwithme');
	}

	public function process() {
		global $current_user;
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		$recordsinfo = implode(' ', array_slice($prm, 2, count($prm)));
		$records= explode('=', $recordsinfo);
		$module = getSalesEntityType($prm[1]);
		$ent = CRMEntity::getInstance($module);
		if (isset($prm[1]) && isset($prm[2]) && $records[1] != '') {
			if (array_key_exists(vtlib_purify($records[0]), $ent->column_fields)) {
				$crmid = vtlib_purify($prm[1]);
				$fieldname = vtlib_purify($records[0]);
				$fieldvalue = vtlib_purify($records[1]);
				$recid = vtws_getEntityId(getSalesEntityType($crmid)).'x'.$crmid;
				$data = array(
					'id' =>$recid,
					$fieldname => $fieldvalue
				);
				$context = array(
					'record' => $crmid,
					'module' =>getSalesEntityType($crmid),
					 $fieldname => $fieldvalue
				);
				$validation = cbwsValidateInformation(json_encode($context), $current_user);
				if ($validation === true) {
					vtws_revise($data, $current_user);
					$this->status = self::STATUS_FOUND;
				} else {
					$this->status = self::STATUS_NOT_FOUND;
				}
			} else {
				$this->status = self::INVALID_FIELD;
			}
		} else {
			$this->status = self::BAD_FORMAT;
		}
		return true;
	}

	public function getResponse() {
		$ret = array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'color' => getMMMsgColor('red'),
				'text' => getTranslatedString('CallError', 'chatwithme'),
			)),
		);
		switch ($this->status) {
			case self::STATUS_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('green'),
						'text' => getTranslatedString('RecordUpdated', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_NOT_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('yellow'),
						'text' => getTranslatedString('RecordNotUpdated', 'chatwithme'),
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
			case self::INVALID_FIELD:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'text' =>  getTranslatedString('InvalidField', 'chatwithme'),
					)),
				);
				break;
		}
		return $ret;
	}
}
?>