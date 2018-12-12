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
include_once 'include/Webservices/WebserviceField.php';
include_once 'include/Webservices/Delete.php';

class cbmmActiondelete extends chatactionclass {
	private $crmid;
	private $status;
	private const STATUS_DELETEITNOW = 1;
	private const STATUS_DELETEIT = 2;
	private const STATUS_NOTFOUND = 3;
	private const STATUS_NOPERMISSION = 4;
	private const STATUS_BADFORMAT = 5;

	public function getHelp() {
		return ' - '.getTranslatedString('delete_command', 'chatwithme');
	}

	public function process() {
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		if (isset($prm[2]) && $prm[2]==getTranslatedString('yes')) {
			$delnow = true;
		} else {
			$delnow = false;
		}
		unset($prm[2]);
		$this->crmid = 0;
		if (count($prm)!=2 || empty($prm[1]) || !is_numeric($prm[1])) {
			$this->status = self::STATUS_BADFORMAT;
		} elseif (!isRecordExists($prm[1])) {
			$this->status = self::STATUS_NOTFOUND;
		} elseif (isPermitted(getSalesEntityType($prm[1]), 'Delete', $prm[1]) != 'yes') {
			$this->status = self::STATUS_NOPERMISSION;
		} else {
			$this->status = $delnow ? self::STATUS_DELETEITNOW : self::STATUS_DELETEIT;
			$this->crmid = $prm[1];
		}
		return true;
	}

	public function getResponse() {
		global $current_user;
		$req = getMMRequest();
		switch ($this->status) {
			case self::STATUS_DELETEITNOW:
				$ret = $this->getDeleteConfirmationMsg();
				unset($ret['attachments'][0]['actions']);
				$ret['attachments'][0]['title'] .= ' **'.getTranslatedString('deleted', 'chatwithme').'!**';
				$ret['attachments'][0]['color'] = getMMMsgColor('blue');
				vtws_delete(vtws_getEntityId(getSalesEntityType($this->crmid)).'x'.$this->crmid, $current_user);
				break;
			case self::STATUS_DELETEIT:
				$ret = $this->getDeleteConfirmationMsg();
				break;
			case self::STATUS_NOPERMISSION:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_PERMISSION'),
					)),
				);
				break;
			case self::STATUS_NOTFOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_RECORD_NOT_FOUND', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_BADFORMAT:
			default:
				$ret = getMMDoNotUnderstandMessage($this->getHelp());
				break;
		}
		return $ret;
	}

	private function getDeleteConfirmationMsg() {
		global $current_user, $site_URL, $adb;
		$req = getMMRequest();
		$module = getSalesEntityType($this->crmid);
		$modid = getTabid($module);
		$focus = CRMEntity::getInstance($module);
		$focus->retrieve_entity_info($this->crmid, $module);
		$bmapname = $module.'_ListColumns';
		$cbMapid = GlobalVariable::getVariable('BusinessMapping_'.$bmapname, cbMap::getMapIdByName($bmapname));
		if ($cbMapid) {
			$cbMap = cbMap::getMapByID($cbMapid);
			$cbMapLC = $cbMap->ListColumns();
			$focus->list_fields = $cbMapLC->getListFieldsFor($module);
			$focus->list_fields_name = $cbMapLC->getListFieldsNameFor($module);
			$focus->list_link_field = $cbMapLC->getListLinkFor($module);
		}
		$focus->filterInactiveFields($module);
		$fields = array();
		foreach ($focus->list_fields_name as $label => $field) {
			if (getFieldVisibilityPermission($module, $current_user->id, $field)=='0') {
				$fld = WebserviceField::fromFieldId($adb, getFieldid($modid, $field));
				if ($fld->isReferenceField() && !empty($focus->column_fields[$field])) {
					$fvalue = getEntityName(getSalesEntityType($focus->column_fields[$field]), $focus->column_fields[$field]);
					$fvalue = $fvalue[$focus->column_fields[$field]];
				} elseif ($fld->isOwnerField() && !empty($focus->column_fields[$field])) {
						$fvalue = getUserFullName($focus->column_fields[$field]);
				} else {
					$fvalue = $focus->column_fields[$field];
				}
				$fields[] = array(
					'short'=>true,
					'title'=>getTranslatedString($label, $module),
					'value'=>$fvalue,
				);
			}
		}
		return array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'color' => getMMMsgColor('yellow'),
				'title' => $focus->column_fields[$focus->list_link_field],
				'fields'=> $fields,
				'actions' => array(
					array(
					'name' => getTranslatedString('LBL_DELETE_BUTTON_LABEL'),
					'integration'=> array(
						'url'=> $site_URL.'/chatwithme.php?text=deleteaction&delete=1&token='.$req['token'].'&record='.$this->crmid,
					)),
					array(
					'name'=> getTranslatedString('LBL_CANCEL_BUTTON_LABEL'),
					'integration'=> array(
						'url'=> $site_URL.'/chatwithme.php?text=deleteaction&delete=0&token='.$req['token'].'&record='.$this->crmid,
					)),
				)
			)),
		);
	}
}
?>