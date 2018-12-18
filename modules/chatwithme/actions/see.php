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

class cbmmActionsee extends chatactionclass {

	public function getHelp() {
		return ' - '.getTranslatedString('see_command', 'chatwithme');
	}

	public function getResponse() {
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		$this->crmid = $prm[1];
		$module = getSalesEntityType($this->crmid);
		$ent = CRMEntity::getInstance($module);
		$data = $ent->retrieve_entity_info($prm[1], $module);
		$blocks = getBlocks($module, 'detail_view', '', $ent->column_fields);
		$fieldsArray = array();
		foreach ($blocks as $rows) {
			foreach ($rows as $fields) {
				foreach ($fields as $label => $field) {
					$fieldsArray[] = array(
						'short' => ((($field['ui']==20) || ($field['ui']==19)) ? false: true),
						'title' => $label,
						'value' => convertFieldValue2Markdown($field['value']),
					);
				}
			}
		}
		return array(
			'response_type' => 'in_channel',
			'attachments' => array(array(
				'color' => '#008000',
				'title' => getTranslatedString('SINGLE_'.$module, $module),
				'fields'=> $fieldsArray,
			)),
		);
	}
}
?>