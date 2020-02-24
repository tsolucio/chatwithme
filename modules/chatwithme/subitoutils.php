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
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'modules/cbMap/cbMap.php';
require_once 'modules/cbMap/processmap/processMap.php';

function getProjectIDToRelateWith($channel) {
	global $adb, $current_user;
	list($brand, $prjtype) = explode('-', $channel);
	$qg = new QueryGenerator('Project', $current_user);
	$qg->setFields(array('id'));
	$qg->addCondition('projecttype', $prjtype, 'e', $qg::$AND);
	$qg->addCondition('brand', $brand, 'e', $qg::$AND);
	$qry = $qg->getQuery();
	$rs = $adb->query($qry);
	if ($adb->num_rows($rs)==0) {
		// create new record
		$rs = $adb->pquery(
			'select accountid
				from vtiger_account
				inner join vtiger_crmentity on crmid=accountid
				where deleted=0 and accountname=?',
			array($brand)
		);
		if ($adb->num_rows($rs)>0) {
			$accid = vtws_getEntityId('Accounts').'x'.$rs->fields['accountid'];
		} else {
			$accid = '';
		}
		$usrwsid = vtws_getEntityId('Users').'x'.$current_user->id;
		$rec =  array(
			'projectname' => $channel,
			'projectstatus' => 'in progress',
			'projecttype' => $prjtype,
			'linktoaccountscontacts' => $accid,
			'brand' => $brand,
			'assigned_user_id' => $usrwsid,
		);
		$prj = vtws_create('Project', $rec, $current_user);
		$record = $prj['id'];
	} else {
		$record = vtws_getEntityId('Project').'x'.$rs->fields['projectid'];
	}
	return $record;
}

function getProjectTaskIDToRelateWith($prjid, $ptask) {
	global $adb;
	list($wsid, $prjid) = explode('x', $prjid);
	$rstasks = $adb->pquery(
		'select projecttaskid
			from vtiger_projecttask
			inner join vtiger_crmentity on crmid=projecttaskid
			inner join vtiger_project on vtiger_project.projectid=vtiger_projecttask.projectid
			where deleted=0 and vtiger_project.projectid=? and (projecttaskname=? or projecttaskid=?) limit 1',
		array($prjid, $ptask, $ptask)
	);
	if ($rstasks && $adb->num_rows($rstasks)>0) {
		return vtws_getEntityId('ProjectTask').'x'.$rstasks->fields['projecttaskid'];
	} else {
		return '';
	}
}

function getProjectSubTaskIDToRelateWith($ptask, $psubtask) {
	global $adb;
	if (strpos($ptask, 'x')) {
		list($wsid, $ptask) = explode('x', $ptask);
	}
	$rstasks = $adb->pquery(
		'select projectsubtaskid
			from vtiger_projectsubtask
			inner join vtiger_crmentity on crmid=projectsubtaskid
			inner join vtiger_projecttask on vtiger_projecttask.projecttaskid=vtiger_projectsubtask.projecttaskid
			where deleted=0 and projecttaskid=? and projectsubtaskname=? limit 1',
		array($ptask, $psubtask)
	);
	if ($rstasks && $adb->num_rows($rstasks)>0) {
		return vtws_getEntityId('ProjectSubTask').'x'.$rstasks->fields['projectsubtaskid'];
	} else {
		return '';
	}
}

function sbProjectTaskExist($projectbrand, $prjtsk) {
	global $adb;
	$rstasks = $adb->pquery(
		'select 1
			from vtiger_projecttask
			inner join vtiger_crmentity on crmid=projecttaskid
			inner join vtiger_project on vtiger_project.projectid=vtiger_projecttask.projectid
			where deleted=0 and vtiger_project.projectname=? and projecttaskname=? limit 1',
		array($projectbrand, $prjtsk)
	);
	return ($rstasks && $adb->num_rows($rstasks)>0);
}

function sbgetPrjTaskStatus($status) {
	global $adb;
	$rstasks = $adb->pquery('select 1 from vtiger_projecttaskstatus where projecttaskstatus=?', array($status));
	return ($rstasks && $adb->num_rows($rstasks)>0);
}

function sbProjectSubTaskExist($projectbrand, $prjtsk, $prjsubtsk) {
	global $adb;
	$rstasks = $adb->pquery(
		'select 1
			from vtiger_projectsubtask
			inner join vtiger_crmentity on crmid=projectsubtaskid
			inner join vtiger_projecttask on vtiger_projecttask.projecttaskid=vtiger_projectsubtask.projecttaskid
			inner join vtiger_project on vtiger_project.projectid=vtiger_projecttask.projectid
			where deleted=0 and vtiger_project.projectname=? and projecttaskname=? and projectsubtaskname=? limit 1',
		array($projectbrand, $prjtsk, $prjsubtsk)
	);
	return ($rstasks && $adb->num_rows($rstasks)>0);
}

function sbgetPrjSubTaskStatus($status) {
	global $adb;
	$rstasks = $adb->pquery('select 1 from vtiger_projectsubtaskprogress where projectsubtaskprogress=?', array($status));
	return ($rstasks && $adb->num_rows($rstasks)>0);
}

function changePrjSubTaskStatus($projectbrand, $prjtsk, $prjsubtsk, $tskstatus) {
	global $adb, $current_user;
	$rstasks = $adb->pquery(
		'select projectsubtaskid
			from vtiger_projectsubtask
			inner join vtiger_crmentity on crmid=projectsubtaskid
			inner join vtiger_projecttask on vtiger_projecttask.projecttaskid=vtiger_projectsubtask.projecttaskid
			inner join vtiger_project on vtiger_project.projectid=vtiger_projecttask.projectid
			where deleted=0 and vtiger_project.projectname=? and projecttaskname=? and projectsubtaskname=? limit 1',
		array($projectbrand, $prjtsk, $prjsubtsk)
	);
	if ($rstasks && $adb->num_rows($rstasks)>0) {
		$element = array(
			'id' => vtws_getEntityId('ProjectSubTask').'x'.$rstasks->fields['projectsubtaskid'],
			'projectsubtaskprogress' => $tskstatus,
		);
		return vtws_revise($element, $current_user);
	}
	return false;
}

function changePrjTaskStatus($projectbrand, $prjtsk, $tskstatus) {
	global $adb, $current_user;
	$rstasks = $adb->pquery(
		'select projecttaskid
			from vtiger_projecttask
			inner join vtiger_crmentity on crmid=projecttaskid
			inner join vtiger_project on vtiger_project.projectid=vtiger_projecttask.projectid
			where deleted=0 and vtiger_project.projectname=? and projecttaskname=? limit 1',
		array($projectbrand, $prjtsk)
	);
	if ($rstasks && $adb->num_rows($rstasks)>0) {
		$element = array(
			'id' => vtws_getEntityId('ProjectTask').'x'.$rstasks->fields['projecttaskid'],
			'projecttaskstatus' => $tskstatus,
		);
		return vtws_revise($element, $current_user);
	}
	return false;
}

function sbgetAllProjectTasks($projectbrandname, $returnname = true) {
	global $adb;
	$rs = $adb->pquery(
		"select projectid
			from vtiger_project
			inner join vtiger_crmentity on crmid=projectid
			where deleted=0 and projectname=? and projectstatus not in ('Completed','Archived')
			order by createdtime asc",
		array($projectbrandname)
	);
	if ($rs && $adb->num_rows($rs)>0) {
		$projectid = $rs->fields['projectid'];
	} else {
		return array();
	}
	$rstasks = $adb->pquery(
		'select projecttaskid,projecttaskname
			from vtiger_projecttask
			inner join vtiger_crmentity on crmid=projecttaskid
			where deleted=0 and projectid=?',
		array($projectid)
	);
	$tasksArray = array();
	if ($rstasks) {
		while ($ptsk = $adb->fetch_array($rstasks)) {
			$tasksArray[] = $ptsk[($returnname ? 'projecttaskname' : 'projecttaskid')];
		}
	}
	return $tasksArray;
}

function sbTypeOfWorkMapExist($projectbrand) {
	global $adb;
	$req = getMMRequest();
	$cn = explode('-', $projectbrand);
	$projectbrand = $cn[0].'-'.$cn[1];
	$rs = $adb->pquery(
		'select cbmapid
			from vtiger_cbmap
			inner join vtiger_crmentity on crmid=cbmapid
			where deleted=0 and mapname=?',
		array($projectbrand.'-'.$req['team_dname'])
	);
	if ($rs && $adb->num_rows($rs)==1) {
		return true;
	} else {
		$rs = $adb->pquery(
			'select cbmapid
				from vtiger_cbmap
				inner join vtiger_crmentity on crmid=cbmapid
				where deleted=0 and mapname=?',
			array($projectbrand)
		);
		if ($rs && $adb->num_rows($rs)==1) {
			return true;
		} else {
			return false;
		}
	}
}

function sbgetAllTypeOfWork($projectbrand) {
	global $adb;
	$req = getMMRequest();
	$cn = explode('-', $projectbrand);
	$projectbrand = $cn[0].'-'.$cn[1];
	$rs = $adb->pquery(
		'select cbmapid
			from vtiger_cbmap
			inner join vtiger_crmentity on crmid=cbmapid
			where deleted=0 and mapname=?',
		array($projectbrand.'-'.$req['team_dname'])
	);
	if ($rs && $adb->num_rows($rs)==1) {
		$mapid = $rs->fields['cbmapid'];
	} else {
		$rs = $adb->pquery(
			'select cbmapid
				from vtiger_cbmap
				inner join vtiger_crmentity on crmid=cbmapid
				where deleted=0 and mapname=?',
			array($projectbrand)
		);
		if ($rs && $adb->num_rows($rs)==1) {
			$mapid = $rs->fields['cbmapid'];
		} else {
			return array();
		}
	}
	$focus = new cbMap();
	$focus->id = $mapid;
	$focus->mode = '';
	$focus->retrieve_entity_info($mapid, 'cbMap');
	$contentok = processcbMap::isXML(html_entity_decode($focus->column_fields['content'], ENT_QUOTES, 'UTF-8'));
	if ($contentok !== true) {
		return array();
	}
	$mapinfo = $focus->FieldInfo();
	if (!isset($mapinfo['fields']) || !isset($mapinfo['fields']['typeofwork'])) {
		return array();
	}
	$tow = array();
	foreach ($mapinfo['fields']['typeofwork'] as $typeofwork => $typeofworkid) {
		$tow[$typeofworkid] = $typeofwork;
	}
	return $tow;
}

function sbgetTypeOfWork($projectbrand, $typeofworkid) {
	global $adb;
	$req = getMMRequest();
	$cn = explode('-', $projectbrand);
	$projectbrand = $cn[0].(isset($cn[1]) ? '-'.$cn[1] : '');
	$rs = $adb->pquery(
		'select cbmapid
			from vtiger_cbmap
			inner join vtiger_crmentity on crmid=cbmapid
			where deleted=0 and mapname=?',
		array($projectbrand.'-'.$req['team_dname'])
	);
	if ($rs && $adb->num_rows($rs)==1) {
		$mapid = $rs->fields['cbmapid'];
	} else {
		$rs = $adb->pquery(
			'select cbmapid
				from vtiger_cbmap
				inner join vtiger_crmentity on crmid=cbmapid
				where deleted=0 and mapname=?',
			array($projectbrand)
		);
		if ($rs && $adb->num_rows($rs)==1) {
			$mapid = $rs->fields['cbmapid'];
		} else {
			return false;
		}
	}

	$focus = new cbMap();
	$focus->id = $mapid;
	$focus->mode = '';
	$focus->retrieve_entity_info($mapid, 'cbMap');
	$contentok = processcbMap::isXML(html_entity_decode($focus->column_fields['content'], ENT_QUOTES, 'UTF-8'));
	if ($contentok !== true) {
		return false;
	}
	$mapinfo = $focus->FieldInfo();
	if (!isset($mapinfo['fields']) || !isset($mapinfo['fields']['typeofwork'])) {
		return false;
	}
	if (is_numeric($typeofworkid)) {
		$tow = array_search($typeofworkid, $mapinfo['fields']['typeofwork']);
		if ($tow !== false) {
			return $tow;
		}
	} else {
		if (isset($mapinfo['fields']['typeofwork'][$typeofworkid])) {
			return $mapinfo['fields']['typeofwork'][$typeofworkid];
		}
	}
	return false;
}

function stoptimerDoUpdateTC($tcid, $brand, $prjtype, $title, $type, $units, $team, $ptask, $psubtask) {
	global $current_user, $adb;
	$prjid = getProjectIDToRelateWith($brand.'-'.$prjtype);
	switch ($current_user->date_format) {
		case 'dd-mm-yyyy':
			$current_date = date('d-m-Y');
			break;
		case 'mm-dd-yyyy':
			$current_date = date('m-d-Y');
			break;
		case 'yyyy-mm-dd':
		default:
			$current_date = date('Y-m-d');
			break;
	}
	$time_end = date('H:i:s');
	$data = array(
		'id' => vtws_getEntityId('Timecontrol').'x'.$tcid,
		'date_end' => $current_date,
		'time_end' => $time_end,
		'brand' => $brand,
		'team' => $team,
		'title' => $title,
		'relconcept' => $prjtype,
		'tcunits' => $units,
		'typeofwork' => $type,
		'relatedto' => $prjid,
	);
	if (!empty($ptask)) {
		$data['prjtask'] = $ptask;
	}
	if (!empty($psubtask)) {
		$data['prjsubtask'] = $psubtask;
	}
	return vtws_revise($data, $current_user);
}

function stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $team, $ptask, $psubtask) {
	global $current_user, $adb;
	$prjid = getProjectIDToRelateWith($brand.'-'.$prjtype);
	$ptask = getProjectTaskIDToRelateWith($prjid, $ptask);
	$psubtask = getProjectSubTaskIDToRelateWith($ptask, $psubtask);
	switch ($current_user->date_format) {
		case 'dd-mm-yyyy':
			$current_date = date('d-m-Y', $date);
			break;
		case 'mm-dd-yyyy':
			$current_date = date('m-d-Y', $date);
			break;
		case 'yyyy-mm-dd':
		default:
			$current_date = date('Y-m-d', $date);
			break;
	}
	list($h, $i) = explode(':', $time);
	list($hn, $in) = explode(':', date('H:i'));
	$time_start = date('H:i:s', mktime($hn-$h, $in-$i, 0, 1, 10, 2020));
	$time_end = date('H:i:s', mktime($hn, $in, 0, 1, 10, 2020));
	$data = array(
		'date_start' => $current_date,
		'time_start' => $time_start,
		'date_end' => $current_date,
		'time_end' => $time_end,
		'totaltime' => $time,
		'brand' => $brand,
		'team' => $team,
		'title' => $title,
		'relconcept' => $prjtype,
		'tcunits' => $units,
		'typeofwork' => $type,
		'relatedto' => $prjid,
		'prjtask' => $ptask,
		'prjsubtask' => $psubtask,
		'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
	);
	return vtws_create('Timecontrol', $data, $current_user);
}
?>
