<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class chatwithme extends CRMEntity {

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			@copy('modules/chatwithme/cwmapi.php', 'chatwithme.php');
			include_once 'vtlib/Vtiger/Module.php';
			include_once 'modules/com_vtiger_workflow/VTTaskManager.inc';
			$taskTypes = array();
			$defaultModules = array('include' => array(), 'exclude'=>array());
			$taskType= array(
				"name"=>"CBSendMMMSGTask",
				"label"=>"Send Message To MM",
				"classname"=>"VTSendMessage2MMTask",
				"classpath"=>"modules/chatwithme/workflow/VTSendMessage2MMTask.inc",
				"templatepath"=>"modules/chatwithme/workflow/VTSendMessage2MMTask.tpl",
				"modules"=>$defaultModules,
				"sourcemodule"=>'',
			);
			VTTaskType::registerTaskType($taskType);
			$modname = 'Users';
			$module = Vtiger_Module::getInstance($modname);
			$field = Vtiger_Field::getInstance('mmuserid', $module);
			if ($field) {
				$this->ExecuteQuery('update vtiger_field set presence=2 where fieldid=?', array($field->id));
			} else {
				$block = Vtiger_Block::getInstance('LBL_USER_ADV_OPTIONS', $module);
				$fieldInstance = new Vtiger_Field();
				$fieldInstance->name = 'mmuserid';
				$fieldInstance->label = 'mmuserid';
				$fieldInstance->columntype = 'varchar(100)';
				$fieldInstance->uitype = 1;
				$fieldInstance->displaytype = 1;
				$fieldInstance->typeofdata = 'V~O';
				$block->addField($fieldInstance);
			}
			$modname = 'cbCalendar';
			$module = Vtiger_Module::getInstance($modname);
			$field = Vtiger_Field::getInstance('activitytype', $module);
			if ($field) {
				$field->setPicklistValues(array('MMRemindMe'));
			}
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			@unlink('chatwithme.php');
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			@copy('modules/chatwithme/cwmapi.php', 'chatwithme.php');
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			@unlink('chatwithme.php');
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
}
?>
