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
		global $adb;
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$adb->query("INSERT INTO vtiger_notificationdrivers (type,path,functionname) VALUES ('CWM','modules/chatwithme/cwmapi.php','__cwmDoNothing')");
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
			$field = Vtiger_Field::getInstance('mmpkey', $module);
			if ($field) {
				$this->ExecuteQuery('update vtiger_field set presence=2 where fieldid=?', array($field->id));
			} else {
				$block = Vtiger_Block::getInstance('LBL_USER_ADV_OPTIONS', $module);
				$fieldInstance = new Vtiger_Field();
				$fieldInstance->name = 'mmpkey';
				$fieldInstance->label = 'mmpkey';
				$fieldInstance->columntype = 'varchar(150)';
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
			// workflows
			$workflowManager = new VTWorkflowManager($adb);
			$taskManager = new VTTaskManager($adb);
			// Send MM Reminder workflow
			$calendarWorkflow = $workflowManager->newWorkFlow('cbCalendar');
			$calendarWorkflow->test = '[{"fieldname":"activitytype","operation":"is","value":"MMRemindMe","valuetype":"rawtext","joincondition":"and","groupid":"0"},{"fieldname":"eventstatus","operation":"is not","value":"Completed","valuetype":"rawtext","joincondition":"and","groupid":"0"},{"fieldname":"dtstart","operation":"less than hours before","value":"72","valuetype":"expression","joincondition":"and","groupid":"0"}]';
			$calendarWorkflow->description = 'Send MM Reminder';
			$calendarWorkflow->executionCondition = VTWorkflowManager::$ON_SCHEDULE;
			$calendarWorkflow->defaultworkflow = 0;
			$calendarWorkflow->schtypeid = 8;
			$calendarWorkflow->schminuteinterval = 5;
			$calendarWorkflow->purpose = 'Send RemindMe message to mattermost';
			$workflowManager->save($calendarWorkflow);
			$task = $taskManager->createTask('CBSendMMMSGTask', $calendarWorkflow->id);
			$task->active = true;
			$task->summary = 'Send MM Reminder';
			$task->executeImmediately = true;
			$task->messageTitle = '$subject';
			$task->messageBody = '$description';
			$task->messageColor = 'blue';
			$task->button_title1 = 'Postpone';
			$task->button_url1 = '&text=remindmeaction&event=postpone';
			$task->button_title2 = 'Discard';
			$task->button_url2 = '&text=remindmeaction&event=discard';
			$task->button_title3 = '';
			$task->button_url3 = '';
			$task->ephemeral = '';
			$task->reevaluate = 0;
			$taskManager->saveTask($task);
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
			$adb->query("DELETE FROM vtiger_notificationdrivers WHERE type='CWM' and path='modules/chatwithme/cwmapi.php' and functionname='__cwmDoNothing'");
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
			$adb->query("INSERT INTO vtiger_notificationdrivers (type,path,functionname) VALUES ('CWM','modules/chatwithme/cwmapi.php','__cwmDoNothing')");
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			$adb->query("DELETE FROM vtiger_notificationdrivers WHERE type='CWM' and path='modules/chatwithme/cwmapi.php' and functionname='__cwmDoNothing'");
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
}
?>
