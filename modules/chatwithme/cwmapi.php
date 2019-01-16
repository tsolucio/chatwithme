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
// Turn on debugging level
$Vtiger_Utils_Log = true;
include_once 'vtlib/Vtiger/Module.php';
include 'modules/chatwithme/cbmmbotutils.php';
include 'modules/chatwithme/chatactionclass.php';

$configmm = getMMSettings();

if (isMMActive() && isset($_REQUEST) && array_key_exists('text', $_REQUEST) && array_key_exists('token', $_REQUEST) && in_array($_REQUEST['token'], $configmm['token'])) {
	$usr = new Users();
	$usrrs = $adb->pquery('select id from vtiger_users where mmuserid=? and status=? limit 1', array(vtlib_purify($_REQUEST['user_id']), 'Active'));
	if ($usrrs && $adb->num_rows($usrrs)==1) {
		$current_user = new Users();
		$current_user->retrieveCurrentUserInfoFromFile($adb->query_result($usrrs, 0, 'id'));

		$current_language = $current_user->language;
		$app_strings = return_application_language($current_language);
		$mod_strings = return_module_language($current_language, 'chatwithme');

		if (isset($_REQUEST['trigger_word'])) {
			$slashcommand = false;
			$op = trim(vtlib_purify($_REQUEST['trigger_word']), '#');
		} else {
			$slashcommand = true;
			$command = parseMMMsg($_REQUEST['text']);
			$op = $command[0];
		}
		if (!empty($configmm['command_language'])) {
			include 'modules/chatwithme/language/'.$configmm['command_language'].'.commands.php';
			if (isset($cwmcommands[$op])) {
				$op = $cwmcommands[$op];
			}
		}
		if (file_exists('modules/chatwithme/actions/'.$op.'.php')) {
			logMMCommand(vtlib_purify($_REQUEST['user_id']), $op, vtlib_purify($_REQUEST['text']), 1);
			include 'modules/chatwithme/actions/'.$op.'.php';
			$actionname = 'cbmmAction'.$op;
			$action = new $actionname();
			$echoResponse = (method_exists($action, 'echoResponse') ? $action->echoResponse() : $slashcommand);
			if ($action->process()) {
				sendMMMsg($action->getResponse(), $echoResponse, $action->addDefault());
			} else {
				sendMMMsg(array('text'=>getTranslatedString('ErrProcessingAction', 'chatwithme')), $echoResponse);
			}
		} else {
			logMMCommand(vtlib_purify($_REQUEST['user_id']), $op, vtlib_purify($_REQUEST['text']), 0);
			$text = getTranslatedString('HelpTitle', 'chatwithme')."\n";
			foreach (glob('modules/chatwithme/actions/*.php') as $actionfilename) {
				include $actionfilename;
				$actionname = 'cbmmAction'.basename($actionfilename, '.php');
				$action = new $actionname();
				$text .= $action->getHelp() . "\n";
			}
			$response = array(
				'response_type' => 'in_channel',
				'text' => $text,
			);
			sendMMMsg($response, $slashcommand);
		}
	} else {
		logMMCommand(vtlib_purify($_REQUEST['user_id']), 'MMInvalidUser', vtlib_purify($_REQUEST['text']), 0);
		$response = array(
			'response_type' => 'in_channel',
			'text' => getTranslatedString('MMInvalidUser', 'chatwithme'),
		);
		sendMMMsg($response, true);
	}
}
?>
