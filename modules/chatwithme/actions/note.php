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

class cbmmActionnote extends chatactionclass {
	const STATUS_FOUND = 1;
	const STATUS_NOT_FOUND = 2;
	const BAD_FORMAT = 3;
	const USER_NOT_FOUND = 4;
	private $status;
	public function getHelp() {
		return ' - '.getTranslatedString('note_command', 'chatwithme');
	}

	public function process() {
		global $current_user, $adb;
		$req = getMMRequest();
		$prm = parseMMMsgWithQuotes($req['text']);
		if (isset($prm[1]) && isset($prm[2])) {
			$module = Vtiger_Module::getInstance('cbEmployee');
			$module2 = Vtiger_Module::getInstance('Contacts');
			$related_to = '';
			if (preg_match('/@/', $prm[1])) {
				$nameEmail = explode('@', $prm[1]);
				if (!empty($nameEmail[0]) && !empty($nameEmail[1])) {
					$email = $prm[1];
					if ($module) {
						$res = $adb->pquery('select cbemployeeid from vtiger_cbemployee where work_email=?', array($email));
						if ($res && $adb->num_rows($res)>0) {
							$related_to = $adb->query_result($res, 0, 'cbemployeeid');
						}
					}
					if ($related_to == '' && $module2) {
						$res = $adb->pquery('select contactid from vtiger_contactdetails where email=?', array($email));
						if ($res && $adb->num_rows($res)>0) {
							$related_to = $adb->query_result($res, 0, 'contactid');
						}
					}
				} else {
					$name = explode('.', $nameEmail[1]);
					$fname = ucwords($name[0]);
					$lname = ucwords($name[1]);
					$fulN = $fname. ' ' .$lname;
					$focus=CRMEntity::getInstance("Users");
					$focus->retrieve_entity_info($current_user->id, 'Users');
					$gtalkid=$focus->column_fields['mmuserid'];
					if ($module) {
						$field1 = Vtiger_Field::getInstance('gtalkid', $module);
						if ($field1) {
							$res = $adb->pquery('select cbemployeeid from vtiger_cbemployee where gtalkid=?', array($gtalkid));
						} else {
							$res = $adb->pquery("select cbemployeeid from vtiger_cbemployee where nombre=?", array($fulN));
						}
						if ($res && $adb->num_rows($res)>0) {
							$related_to = $adb->query_result($res, 0, 'cbemployeeid');
						}
					}
					if ($related_to == '' && $module2) {
						$field2 = Vtiger_Field::getInstance('gtalkid', $module2);
						if ($field2) {
							$res = $adb->pquery('select contactid from vtiger_contactdetails where gtalkid=?', array($gtalkid));
						} else {
							$res = $adb->pquery('select contactid from vtiger_contactdetails where firstname=? and lastname=?', array($fname, $lname));
						}
						if ($res && $adb->num_rows($res)>0) {
							$related_to = $adb->query_result($res, 0, 'contactid');
						}
					}
				}
			} else {
				$name = explode(' ', $prm[1]);
				$fname = ucwords($name[0]);
				$lname = ucwords($name[1]);
				$fulN = $fname. ' ' .$lname;
				if ($module) {
					$res = $adb->pquery("select cbemployeeid from vtiger_cbemployee where nombre=?", array($fulN));
					if ($res && $adb->num_rows($res)>0) {
						$related_to = $adb->query_result($res, 0, 'cbemployeeid');
					}
				}
				if ($related_to == '' && $module2) {
					$res = $adb->pquery('select contactid from vtiger_contactdetails where firstname=? and lastname=?', array($fname, $lname));
					if ($res && $adb->num_rows($res)>0) {
						$related_to = $adb->query_result($res, 0, 'contactid');
					}
				}
			}
			$description = $prm[2];
			if (isset($prm[3]) && is_numeric($prm[2]) && $prm[2] > 0 && $prm[2] < 6) {
				$description = $prm[3];
				$mapname = 'cbSurveyDone_FieldInfo';
				$cbMapid = GlobalVariable::getVariable('BusinessMapping_'.$mapname, cbMap::getMapIdByName($mapname));
				if ($cbMapid) {
					$cbMap = cbMap::getMapByID($cbMapid);
					$cbMapFInfo = $cbMap->FieldInfo();
					if (in_array($prm[2], array_keys($cbMapFInfo['fields']['taskrating']))) {
						$rating = $cbMapFInfo['fields']['taskrating'][$prm[2]];
					}
				}
			}
			if ($related_to != '') {
				$data = array(
					'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
					'cbsq_completed' => 1,
					'description' => $description,
					'relatewith' => $related_to,
					'taskrating' => $rating,
				);
				try {
					vtws_create('cbSurveyDone', $data, $current_user);
					$this->status = self::STATUS_FOUND;
				} catch (\Throwable $err) {
					$this->status = self::STATUS_NOT_FOUND;
				}
			} else {
				$this->status = self::USER_NOT_FOUND;
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
						'text' => getTranslatedString('NewRecordAdded', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_NOT_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('yellow'),
						'text' => getTranslatedString('NewRecordNotAdded', 'chatwithme'),
					)),
				);
			case self::USER_NOT_FOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'text' => getTranslatedString('UserNotFound', 'chatwithme'),
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