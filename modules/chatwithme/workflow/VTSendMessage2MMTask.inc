<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'modules/com_vtiger_workflow/VTEntityCache.inc';
require_once 'modules/com_vtiger_workflow/VTWorkflowUtils.php';
require_once 'modules/com_vtiger_workflow/VTSimpleTemplate.inc';
require_once 'modules/chatwithme/cbmmbotutils.php';

class VTSendMessage2MMTask extends VTTask {
	public $executeImmediately = true;

	public function getFieldNames() {
		return array('messageTitle','messageBody','messageColor','button_title1','button_url1','button_title2','button_url2','button_title3','button_url3','ephemeral');
	}

	public function doTask(&$entity) {
		global $current_user, $logbg, $adb, $configmm, $site_URL;
		$data = $entity->getData();
		$usrid = explode('x', $data['assigned_user_id']);
		$focus = new Users();
		$focus->retrieve_entity_info($usrid[1], 'Users');
		$mmuidrs = $adb->pquery('select mmuserid from vtiger_users where id=?', array($focus->id));
		$mmuserid = $adb->query_result($mmuidrs, 0, 'mmuserid');
		if (empty($configmm)) {
			$configmm = getMMSettings();
		}
		$util = new VTWorkflowUtils();
		$ws_id = $entity->getId();
		$entityCache = new VTEntityCache($current_user);
		$pt = new VTSimpleTemplate($this->messageTitle);
		$title = $pt->render($entityCache, $ws_id);
		$pt1 = new VTSimpleTemplate($this->messageBody);
		$body = $pt1->render($entityCache, $ws_id);
		$crmid = explode('x', $ws_id);
		$textdata = array(
			'response_type' => 'in_channel',
			'user_id' => $mmuserid,
		);
		if (isset($entity->WorkflowContext['CWMWorkflowChannelName'])) {
			$textdata['channel'] = $entity->WorkflowContext['CWMWorkflowChannelName'];
			$textdata['channel_id'] = $entity->WorkflowContext['CWMWorkflowChannelID'];
		}
		if (empty($title)) {
			$textdata['text'] = strip_tags($body);
		} else {
			$attachments = array(
				'title' => $title,
				'text' => strip_tags($body),
			);
			if (!empty($this->messageColor)) {
				$attachments['color'] = getMMMsgColor($this->messageColor);
			}
			$baseurl = $site_URL.'/notifications.php?type=CWM&token='.$configmm['token'][0].'&record='.$crmid[1].'&user_id='.$mmuserid;
			if (!empty($this->button_title1) && !empty($this->button_url1)) {
				$attachments['actions'] = array(
					array(
						'name' => getTranslatedString($this->button_title1, 'chatwithme'),
						'integration'=> array(
							'url'=> $baseurl.$this->button_url1,
						)
					)
				);
			}
			if (!empty($this->button_title2) && !empty($this->button_url2)) {
				$button2 = array(
					array(
						'name' => getTranslatedString($this->button_title2, 'chatwithme'),
						'integration'=> array(
							'url'=> $baseurl.$this->button_url2,
						)
					)
				);
				$attachments['actions'] = array_merge($attachments['actions'], $button2);
			}
			if (!empty($this->button_title3) && !empty($this->button_url3)) {
				$button3 = array(
					array(
						'name' => getTranslatedString($this->button_title3, 'chatwithme'),
						'integration'=> array(
							'url'=> $baseurl.$this->button_url3,
						)
					)
				);
				$attachments['actions'] = array_merge($attachments['actions'], $button3);
			}
			$textdata['attachments'] = array($attachments);
		}
		$logbg->debug('Mattermost msg sent:');
		$logbg->debug($textdata);
		sendMMMsg($textdata, false);
	}
}
?>
