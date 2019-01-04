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
        return array('messageTitle','messageBody','messageColor','button_title1',
        'button_url1','button_title2','button_url2','button_title3','button_url3','ephemeral');
    }
    public function doTask(&$entity) {
        global $current_user, $log;
        global $configmm;
        $focus = new Users();
        $focus->retrieve_entity_info($current_user->id, 'Users');
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
        if (empty($title)) {
            $textdata = array(
                'response_type' => 'in_channel',
                'text' => strip_tags($body)
            );
        }else {
            $textdata = array(
               'response_type' => 'in_channel',
               'attachments' => array(array(
                   'title' => $title,
                   'text' => strip_tags($body),
               ))
            );
            if (!empty($this->messageColor)) {
                $textdata['attachments'][0]['color'] = getMMMsgColor($this->messageColor);
            }
            if (!empty($this->button_title1) && !empty($this->button_url1)) { 
                $textdata['attachments'][0]['actions'] = array(
                    array(
                    'name' => $this->button_title1,
                    'integration'=> array(
                        'url'=> $this->button_url2.'&token='.$configmm['token'][0].'&user_id='.$focus->column_fields['mmuserid'].'&record='.$crmid[1],
                    ))
                );
            }
            if (!empty($this->button_title2) && !empty($this->button_url2)) {
                $button2 = array(
                    array(
                    'name' => $this->button_title2,
                    'integration'=> array(
                        'url'=> $this->button_url2.'&token='.$configmm['token'][0].'&user_id='.$focus->column_fields['mmuserid'].'&record='.$crmid[1],
                    ))
                );
                $textdata['attachments'][0]['actions'] = array_merge($textdata['attachments'][0]['actions'], $button2);
            }
            if (!empty($this->button_title3) && !empty($this->button_url3)) { 
                $button3 = array(
                    array(
                    'name' => $this->button_title3,
                    'integration'=> array(
                        'url'=> $this->button_url3.'&token='.$configmm['token'][0].'&user_id='.$focus->column_fields['mmuserid'].'&record='.$crmid[1],
                    ))
                );
                $textdata['attachments'][0]['actions'] = array_merge($textdata['attachments'][0]['actions'], $button3);
            }
        }
        sendMMMsg($textdata, false);        
    }
}
?>
