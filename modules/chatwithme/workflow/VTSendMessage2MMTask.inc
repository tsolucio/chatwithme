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
        return array('messageTitle','messageBody');
    }
    public function doTask(&$entity) {
        global $current_user;
        $util = new VTWorkflowUtils();
        $ws_id = $entity->getId();
        $entityCache = new VTEntityCache($current_user);
        $pt = new VTSimpleTemplate($this->messageTitle);
        $title = $pt->render($entityCache, $ws_id);
        $pt1 = new VTSimpleTemplate($this->messageBody);
        $body = $pt1->render($entityCache, $ws_id);
        $textdata = array(
            'response_type' => 'in_channel',
            'text' => strip_tags($body)
        );
        global $configmm;
        if (empty($configmm)) {
            $configmm = getMMSettings();
        }
        sendMMMsg($textdata, false);        
    }
}
?> 
