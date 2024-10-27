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
include_once 'include/Webservices/CustomerPortalWS.php';
include_once 'include/integrations/zendesk/Zendesk.php';

class cbmmActionfind extends chatactionclass {
	private $status;
	private $condition;
	private $supportedComparators = array('=','>','>=','<','<=','%');
	const STATUS_FINDITGLOBAL = 1;
	const STATUS_FINDITGLOBALMODULE = 2;
	const STATUS_FINDITFIELDMODULE = 6;
	const STATUS_MODULENOTFOUND = 3;
	const STATUS_FIELDNOTFOUND = 4;
	const STATUS_BADFORMAT = 5;

	public function getHelp() {
		return ' - '.getTranslatedString('find_command1', 'chatwithme')."\n".' - '.getTranslatedString('find_command2', 'chatwithme');
	}

	public function process() {
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		$numprms = count($prm);
		if ($numprms==1) {
			$this->status = self::STATUS_BADFORMAT;
		} elseif ($numprms==2) {
			$this->status = self::STATUS_FINDITGLOBAL;
			$term = '';
			for ($t=1; $t<$numprms; $t++) {
				$term .= ' '.$prm[$t];
			}
			$this->condition = $term;
		} else {
			$modules = getPermittedModuleNames();
			$modulesi18n = array();
			foreach ($modules as $modname) {
				$modulesi18n[$modname] = getTranslatedString($modname, $modname);
			}
			$zd = new corebos_zendesk();
			if ($zd->isActive()) {
				$modulesi18n['Zendesk'] = 'Zendesk';
				$modules['Zendesk'] = 'Zendesk';
			}
			if (in_array($prm[1], $modulesi18n)) {
				$prm[1] = array_search($prm[1], $modulesi18n);
			}
			if (in_array($prm[1], $modules)) {
				if (preg_match('/\>=|\<=|[\%=\>\<]/', $prm[2], $fieldcomp)) {
					$comp = $fieldcomp[0];
					list ($field, $value) = explode($comp, $prm[2]);
					$mobj = Vtiger_Module::getInstance($prm[1]);
					$fobj = Vtiger_Field::getInstance($field, $mobj);
					if ($fobj || $prm[1]=='Zendesk') {
						$this->status = self::STATUS_FINDITFIELDMODULE;
						for ($t=3; $t<$numprms; $t++) {
							$value = $value.' '.$prm[$t];
						}
						$this->condition = array(
							'module' => $prm[1],
							'term' => array(
								'field' => $field,
								'comp' => $comp,
								'term' => trim($value),
							),
						);
					} else {
						$this->status = self::STATUS_FIELDNOTFOUND;
					}
				} else {
					$this->status = self::STATUS_FINDITGLOBALMODULE;
					$term = '';
					for ($t=2; $t<$numprms; $t++) {
						$term .= ' '.$prm[$t];
					}
					$this->condition = array(
						'module' => $prm[1],
						'term' => trim($term),
					);
				}
			} else {
				$this->status = self::STATUS_FINDITGLOBAL;
				$term = '';
				for ($t=1; $t<$numprms; $t++) {
					$term .= ' '.$prm[$t];
				}
				$this->condition = trim($term);
			}
		}
		return true;
	}

	public function getResponse() {
		global $current_user;
		$req = getMMRequest();
		switch ($this->status) {
			case self::STATUS_FINDITGLOBAL:
				$rdo = unserialize(vtws_getSearchResults(
					$this->condition,
					'',
					array('userId'=>vtws_getEntityId('Users').'x'.$current_user->id, 'accountId' => '0x0', 'contactId' => '0x0'),
					$current_user
				));
				$ret = $this->getSearchResultMsg($rdo);
				break;
			case self::STATUS_FINDITFIELDMODULE:
				if ($this->condition['module']=='Zendesk') {
					$rdo = $this->getZendeskResults();
					$ret = $this->getSearchResultMsg($rdo);
				} else {
					$ret = $this->getFieldQueryMsg();
				}
				break;
			case self::STATUS_FINDITGLOBALMODULE:
				if ($this->condition['module']=='Zendesk') {
					$rdo = $this->getZendeskResults();
				} else {
					$rdo = unserialize(vtws_getSearchResults(
						$this->condition['term'],
						$this->condition['module'],
						array('userId'=>vtws_getEntityId('Users').'x'.$current_user->id, 'accountId' => '0x0', 'contactId' => '0x0'),
						$current_user
					));
				}
				$ret = $this->getSearchResultMsg($rdo);
				break;
			case self::STATUS_FIELDNOTFOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_FIELD_NOT_FOUND', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_MODULENOTFOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_MODULE_NOT_FOUND', 'chatwithme'),
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

	private function getFieldQueryMsg() {
		require_once 'include/QueryGenerator/QueryGenerator.php';
		global $current_user, $adb;
		$compmap = array(
			'>=' => 'h',
			'<=' => 'm',
			'%' => 'c',
			'=' => 'e',
			'>' => 'g',
			'<' => 'l',
		);
		$module = $this->condition['module'];
		if (!vtlib_isModuleActive($module) || !vtlib_isEntityModule($module)) {
			return array(
				'color' => getMMMsgColor('yellow'),
				'title' => getTranslatedString('LBL_NOSEARCHRESULTS', 'chatwithme'),
			);
		}
		$focus = CRMEntity::getInstance($module);
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
				$fields[$label] = $field;
			}
		}
		$fields['id'] = 'id';
		$queryGenerator = new QueryGenerator($module, $current_user);
		$queryGenerator->setFields($fields);
		$queryGenerator->addCondition($this->condition['term']['field'], $this->condition['term']['term'], $compmap[$this->condition['term']['comp']]);
		$query = $queryGenerator->getQuery();
		$rs = $adb->query($query);
		if ($rs && $adb->num_rows($rs)>0) {
			$fields['search_module_name'] = $module;
			$md = $this->getSearchResultHeader($fields);
			$numrows = 0;
			while ($row = $adb->fetch_array($rs)) {
				$md .= '| ';
				foreach ($row as $key => $fvalue) {
					if (is_numeric($key)) {
						continue;
					}
					$md .= convertFieldValue2Markdown($fvalue).' | ';
				}
				$md = trim($md)."\n";
				$numrows++;
			}
			$ret = array(
				'color' => getMMMsgColor('blue'),
				'title' => getTranslatedString('LBL_SEARCHRESULTS', 'chatwithme'),
				'text' => $md,
			);
		} else {
			$ret = array(
				'color' => getMMMsgColor('yellow'),
				'title' => getTranslatedString('LBL_NOSEARCHRESULTS', 'chatwithme'),
			);
		}
		return array(
			'response_type' => 'in_channel',
			'attachments' => array($ret),
		);
	}

	private function getZendeskResults() {
		$zd = new corebos_zendesk();
		if ($zd->isActive()) {
			$ret = $zd->searchTickets($this->condition['term']['field'].$this->condition['term']['comp'].$this->condition['term']['term']);
			$ret = array_map(
				function ($row) {
					$row['search_module_name'] = 'Zendesk';
					$row['id'] = '0x'.$row['id'];
					return $row;
				},
				$ret
			);
		} else {
			$ret = array();
		}
		return $ret;
	}

	private function getSearchResultMsg($result) {
		if (empty($result)) {
			$ret = array(
				'color' => getMMMsgColor('yellow'),
				'title' => getTranslatedString('LBL_NOSEARCHRESULTS', 'chatwithme'),
			);
		} else {
			$md = '';
			$numrows = 0;
			$modrdo = '';
			foreach ($result as $rdorow) {
				if ($rdorow['search_module_name']!=$modrdo) {
					if ($md != '') {
						$md .= "\n\n";
					}
					$md .= $this->getSearchResultHeader($rdorow);
				}
				$md .= '| ';
				foreach ($rdorow as $flabel => $fvalue) {
					if ($flabel=='search_module_name') {
						continue;
					}
					if ($flabel == 'id') {
						list($wsid, $crmid) = explode('x', $fvalue);
						$md .= $crmid.' | ';
					} else {
						$md .= convertFieldValue2Markdown($fvalue).' | ';
					}
				}
				$md = trim($md)."\n";
				$numrows++;
				$modrdo = $rdorow['search_module_name'];
			}
			$ret = array(
				'color' => getMMMsgColor('blue'),
				'title' => getTranslatedString('LBL_SEARCHRESULTS', 'chatwithme'),
				'text'=> $md,
			);
		}
		return array(
			'response_type' => 'in_channel',
			'attachments' => array($ret),
		);
	}

	private function getSearchResultHeader($result) {
		$module = $result['search_module_name'];
		$md = '**'.getTranslatedString($module, $module)."**\n\n| ";
		$dashes = '|';
		foreach ($result as $flabel => $fvalue) {
			if ($flabel=='search_module_name') {
				continue;
			}
			if ($flabel == 'id') {
				$md .= getTranslatedString($module.' ID', $module).' | ';
			} else {
				$md .= getTranslatedString($flabel, $module).' | ';
			}
			$dashes .= '----|';
		}
		return trim($md)."\n".$dashes."\n";
	}
}
?>
