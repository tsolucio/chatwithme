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
require 'modules/chatwithme/vendor/autoload.php';
use League\HTMLToMarkdown\HtmlConverter;

include 'vtlib/Vtiger/Net/Client.php';

/**
 * Send message to mattermost
 * @param $text
 */
function sendMMMsg($response, $slashcommand, $addDefault = true) {
	global $configmm;
	if (empty($configmm)) {
		$configmm = getMMSettings();
	}
	$default = array();
	if ($addDefault) {
		$default['username'] = $configmm['username'];
		$default['icon_url'] = $configmm['icon_url'];
	}
	$response = array_merge($default, $response);
	if ($slashcommand) {
		sendMMResponse($response);
	} else {
		sendMMPost($response);
	}
	die;
}

/**
 * Send message to mattermost as response
 * @param array $response
 */
function sendMMResponse($response) {
	header('Content-Type: application/json');
	echo json_encode($response);
}

/**
 * Send message to mattermost incoming endpoint
 * @param array $response
 */
function sendMMPost($response) {
	global $configmm;
	$msg = json_encode($response);
	$client = new Vtiger_Net_Client($configmm['posturl']);
	$client->setHeaders(array(
		'Content-Type' => 'application/json',
		'Content-Length' => strlen($msg),
	));
	$client->doPost($msg);
}

function parseMMMsg($text) {
	global $default_charset;
	return explode(' ', html_entity_decode($text, ENT_QUOTES, $default_charset));
}

function getMMRequest() {
	/* POST PARAMS
		[channel_id] => yxqdo1yfg7bqmbxmur3nrd5jpc
		[channel_name] => town-square
		[team_domain] => corebosmm
		[team_id] => og8z8f74wirwmjjb3ct4xbg13h
		[token] => t9m89kj6y3netnsqot1h5eq7me
		[user_id] => jweziq9b5trgjjpbdj3c6ap99w
		[user_name] => joebordes
	// slash command
		[command] => /corebos
		[text] => show account
		[trigger_id] => YWFhdXFibWY0ZmdudWdtd3g3MTQ2NHdmaWE6andlemlxOWI1dHJnampwYmRqM2M2YXA5OXc6MTU0MzYwMjAzNDM0NjpNRVVDSVFDSFB1RWNkZnVoT05jek9BVGdzek5qTktKMmRwR2FCdFFsNjAzanh3SjkyUUlnRERCZVBjSDV1T1dJSzJkcTdBaUJ1OWFUR3FKNTVjUndUR1IxRDd0aGxpMD0=
		[response_url] => http://localhost:8065/hooks/commands/an3t1rincbg1jxo38dqrqx57xy
	// webhook
		[text] => #help me
		[trigger_word] => #help
		[file_ids] =>
		[post_id] => jmjh5bsotid98ma4x7xtiy7mfh
		[timestamp] => 1544209327
	*/
	$ret = array(
		'channel_id' => vtlib_purify($_REQUEST['channel_id']),
		'channel_name' => vtlib_purify($_REQUEST['channel_name']),
		'team_domain' => vtlib_purify($_REQUEST['team_domain']),
		'team_id' => vtlib_purify($_REQUEST['team_id']),
		'token' => vtlib_purify($_REQUEST['token']),
		'user_id' => vtlib_purify($_REQUEST['user_id']),
		'user_name' => vtlib_purify($_REQUEST['user_name']),
		'text' => vtlib_purify($_REQUEST['text']),
	);
	if (isset($_REQUEST['command'])) {
		$ret['command'] = vtlib_purify($_REQUEST['command']);
		$ret['trigger_word'] = '';
	} else {
		$ret['command'] = '';
		$ret['trigger_word'] = vtlib_purify($_REQUEST['trigger_word']);
		$ret['file_ids'] = vtlib_purify($_REQUEST['file_ids']);
	}
	return $ret;
}

function getMMMsgColor($color) {
	switch ($color) {
		case 'blue':
			$clr = '#3974d3';
			break;
		case 'red':
			$clr = '#D40A0A';
			break;
		case 'yellow':
			$clr = '#DDD300';
			break;
		case 'green':
		default:
			$clr = '#01B829';
			break;
	}
	return $clr;
}

function logMMCommand($mmuser, $command, $text, $found) {
	global $adb, $current_user;
	$usrid = (empty($current_user) ? 0 : $current_user->id);
	$adb->pquery('insert into chatwithme_log values (?,?,?,?,?,?)', array($usrid, $mmuser, date('Y-m-d H:i:s'), $command, $text, $found));
}

function saveMMSettings($isactive, $cmdlang, $username, $iconurl, $posturl, $tokens) {
	coreBOS_Settings::setSetting('cbmm_isactive', $isactive);
	coreBOS_Settings::setSetting('cbmm_command_language', $cmdlang);
	coreBOS_Settings::setSetting('cbmm_username', $username);
	coreBOS_Settings::setSetting('cbmm_icon_url', $iconurl);
	coreBOS_Settings::setSetting('cbmm_posturl', $posturl);
	coreBOS_Settings::setSetting('cbmm_tokens', $tokens);
}

function getMMSettings() {
	// 'command_language' => 'es_es',
	// 'username' => 'cbmmBOT',
	// 'icon_url' => $site_URL.'/modules/chatwithme/chatwithme.png',
	// 'posturl' => 'http://dockerhost:8065/hooks/48orusip1fn48x8rgoz169dbzy',
	// 'token' => array(
	// 	'poubogtbitgexx87urtjhgsear',
	// 	'sec9ojdz4typdetx4kh1tkci7h'
	// ),
	global $site_URL;
	return array(
		'command_language' => coreBOS_Settings::getSetting('cbmm_command_language', ''),
		'username' => coreBOS_Settings::getSetting('cbmm_username', 'cbmmBOT'), // username display on chat
		'icon_url' => coreBOS_Settings::getSetting('cbmm_icon_url', $site_URL.'/modules/chatwithme/chatwithme.png'), // icon display on chat
		'posturl' => coreBOS_Settings::getSetting('cbmm_posturl', ''),
		'token' => array_map('trim', explode(',', coreBOS_Settings::getSetting('cbmm_tokens', ''))),
	);
}

function isMMActive() {
	return (coreBOS_Settings::getSetting('cbmm_isactive', '0')=='1');
}

function getMMDoNotUnderstandMessage($msg) {
	return array(
		'response_type' => 'in_channel',
		'attachments' => array(array(
			'color' => getMMMsgColor('yellow'),
			'title' => getTranslatedString('IncorrectFormat', 'chatwithme'),
			'text' => getTranslatedString('ThisIsHelp', 'chatwithme')."\n".$msg,
		)),
	);
}

function convertFieldValue2Markdown($value) {
	global $site_URL, $default_charset;
	if (!empty($value)) {
		$value = html_entity_decode($value, ENT_QUOTES, $default_charset);
		$dom = new DOMDocument;
		$dom->loadHTML($value);
		$images = $dom->getElementsByTagName('img');
		foreach ($images as $image) {
			if (strpos($image->getAttribute('src'), $site_URL)===false) {
				$image->setAttribute('src', $site_URL.'/'.$image->getAttribute('src'));
			}
		}
		$links = $dom->getElementsByTagName('a');
		foreach ($links as $link) {
			if (strpos($link->getAttribute('href'), $site_URL)===false) {
				$link->setAttribute('href', $site_URL.'/'.$link->getAttribute('href'));
			}
		}
		$value = $dom->saveHTML();
		$converter = new HtmlConverter(array('remove_nodes' => 'span div'));
		$value = $converter->convert($value);
	}
	return $value;
}
?>