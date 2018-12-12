<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include 'modules/chatwithme/cbmmbotutils.php';

$smarty = new vtigerCRM_Smarty();

$isadmin = is_admin($current_user);

if ($isadmin && isset($_REQUEST['_op']) && $_REQUEST['_op']=='setmmconfig') {
	$isActive = ((empty($_REQUEST['mm_active']) || $_REQUEST['mm_active']!='on') ? '0' : '1');
	$cmdlang = (empty($_REQUEST['mm_cmdlang']) ? '' : vtlib_purify($_REQUEST['mm_cmdlang']));
	$username = (empty($_REQUEST['mm_username']) ? '' : vtlib_purify($_REQUEST['mm_username']));
	$iconurl = (empty($_REQUEST['mm_icon_url']) ? '' : vtlib_purify($_REQUEST['mm_icon_url']));
	$posturl = (empty($_REQUEST['mm_posturl']) ? '' : vtlib_purify($_REQUEST['mm_posturl']));
	$tokens = (empty($_REQUEST['mm_tokens']) ? '' : vtlib_purify($_REQUEST['mm_tokens']));
	saveMMSettings($isActive, $cmdlang, $username, $iconurl, $posturl, $tokens);
}
$cmdlangs = array();
foreach (glob('modules/chatwithme/language/*.commands.php') as $cmdfilename) {
	$cmdlangs[] = basename($cmdfilename, '.commands.php');
}
$smarty->assign('TITLE_MESSAGE', getTranslatedString('SINGLE_chatwithme', $currentModule));
$mmsettings = getMMSettings();
$smarty->assign('isActive', isMMActive());
$smarty->assign('command_language', $mmsettings['command_language']);
$smarty->assign('cmdlangs', $cmdlangs);
$smarty->assign('username', $mmsettings['username']);
$smarty->assign('icon_url', $mmsettings['icon_url']);
$smarty->assign('posturl', $mmsettings['posturl']);
$smarty->assign('tokens', implode(',', $mmsettings['token']));
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('THEME', $theme);
include 'modules/cbupdater/forcedButtons.php';
$smarty->assign('CHECK', $tool_buttons);
$smarty->assign('ISADMIN', $isadmin);
$smarty->display('modules/chatwithme/settings.tpl');
?>