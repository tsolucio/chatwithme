<?php
/*************************************************************************************************
 * Copyright 2019 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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
require_once 'modules/chatwithme/cbmmbotutils.php';
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

global $adb, $current_user;
define('INVALIDMMTEAM', 'MM_TEAM_ERROR');
if (!is_admin($current_user)) {
	echo '<br><br>';
	$smarty = new vtigerCRM_Smarty();
	$smarty->assign('ERROR_MESSAGE_CLASS', 'cb-alert-danger');
	$smarty->assign('ERROR_MESSAGE', getTranslatedString('LBL_PERMISSION'));
	$smarty->display('applicationmessage.tpl');
	exit;
}

$return_module=vtlib_purify($_REQUEST['return_module']);
$usrid=vtlib_purify($_REQUEST['usrid']);
if ($return_module!='Users' || !isRecordExists(vtws_getEntityId('Users').'x'.$usrid)) {
	echo '<br><br>';
	$smarty = new vtigerCRM_Smarty();
	$smarty->assign('ERROR_MESSAGE_CLASS', 'cb-alert-danger');
	$smarty->assign('ERROR_MESSAGE', getTranslatedString('Incorrect parameters', 'chatwithme'));
	$smarty->display('applicationmessage.tpl');
	exit;
}
$moduser = Vtiger_Module::getInstance('Users');
$block = Vtiger_Block::getInstance('LBL_MORE_INFORMATION', $moduser);
$field = Vtiger_Field::getInstance('mmteam', $moduser);
if ($block && !$field) {
	$fieldInstance = new Vtiger_Field();
	$fieldInstance->name = 'mmteam';
	$fieldInstance->label = 'MM Team';
	$fieldInstance->columntype = 'varchar(200)';
	$fieldInstance->uitype = 1;
	$fieldInstance->displaytype = 1;
	$fieldInstance->typeofdata = 'V~O';
	$block->addField($fieldInstance);
}

/**
	Username  string `json:'username'`
	Password  string `json:'password,omitempty'`
	Email     string `json:'email'`
	FirstName string `json:'first_name'`
	LastName  string `json:'last_name'`
	Position  string `json:'position'`
	Roles     string `json:'roles'`
*/
function cbmmSendUserData($usrid) {
	global $adb;
	$rs = $adb->pquery('SELECT user_name,first_name,last_name,email1,mmteam FROM vtiger_users where id=? AND deleted=0', array($usrid));
	if (empty($rs->fields['mmteam'])) {
		return INVALIDMMTEAM;
	}
	$response = array(
		'Username'  => $rs->fields['user_name'],
		'Password'  => coreBOS_Settings::getSetting('cbmm_userpasswd', 'My1stPass!'),
		'Email'     => $rs->fields['email1'],
		'FirstName' => $rs->fields['first_name'],
		'LastName'  => $rs->fields['last_name'],
		'Position'  => '',
		'Roles'     => 'system_user',
		'TeamNames' => $rs->fields['mmteam'],
	);
	$posturl = coreBOS_Settings::getSetting('cbmm_posturl', '');
	if (empty($posturl)) {
		return '';
	}
	$posturl .= '/plugins/com.corebos.server/syncuser';
	$client = new Client();
	try {
		$response = $client->post(
			$posturl,
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
				],
				'json' => $response,
			]
		);
		$body = $response->getBody()->getContents();
		$resp = json_decode($body, true);
	} catch (RequestException $e) {
		$resp = ['id' => 0];
	}
	return $resp['id'];
}

$mmuserid = cbmmSendUserData($usrid);
if (!empty($mmuserid) && $mmuserid != 'MM_TEAM_ERROR') {
	$adb->pquery('UPDATE vtiger_users set mmuserid=? where id=?', array($mmuserid, $usrid));
	$msg = getTranslatedString('MMUserIDUpdated', 'chatwithme');
	$msgc = '&error_msgclass=cb-alert-success';
} elseif ($mmuserid == 'MM_TEAM_ERROR') {
	$msg = getTranslatedString('MMUserTEAMError', 'chatwithme');
	$msgc = '';
} else {
	$msg = getTranslatedString('MMUserIDError', 'chatwithme');
	$msgc = '';
}
header('Location: index.php?module=Users&action=DetailView&modechk=prefview&record='.$usrid.'&error_string='.urlencode($msg).$msgc);
?>
