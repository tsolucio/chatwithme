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
include_once 'include/Webservices/Delete.php';

class cbmmActiondeleteaction extends chatactionclass {

	public function addDefault() {
		return false;
	}

	public function getResponse() {
		global $current_user;
		$crmid = vtlib_purify($_REQUEST['record']);
		$module = getSalesEntityType($crmid);
		$en = getEntityName($module, $crmid);
		if ($_REQUEST['delete']==1 && isPermitted($module, 'Delete', $crmid) == 'yes') {
			try {
				vtws_delete(vtws_getEntityId($module).'x'.$crmid, $current_user);
				$ret = array(
					'color' => getMMMsgColor('blue'),
					'ephemeral_text' => getTranslatedString('record', 'chatwithme')." ($crmid) ".$en[$crmid].' **'.getTranslatedString('deleted', 'chatwithme').'!**',
				);
			} catch (Exception $e) {
				$ret = array(
					'color' => getMMMsgColor('blue'),
					'ephemeral_text' => getTranslatedString('record', 'chatwithme')." ($crmid) ".$en[$crmid].' **'.getTranslatedString('notdeleted', 'chatwithme').'!**',
				);
			}
		} else {
			$ret = array(
				'update' => array(
					'color' => getMMMsgColor('blue'),
					'message' => getTranslatedString('record', 'chatwithme')." ($crmid) ".$en[$crmid].' **'.getTranslatedString('notdeleted', 'chatwithme').'!**',
				),
			);
		}
		return $ret;
	}
}
?>