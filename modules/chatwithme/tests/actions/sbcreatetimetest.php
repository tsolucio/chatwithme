<?php
/*************************************************************************************************
 * Copyright 2020 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Tests.
 * The MIT License (MIT)
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *************************************************************************************************/
use PHPUnit\Framework\TestCase;

include_once 'modules/chatwithme/cbmmbotutils.php';
include_once 'modules/chatwithme/chatactionclass.php';
include_once 'modules/chatwithme/actions/sbcreatetime.php';
include_once 'include/Webservices/upsert.php';
include_once 'include/Webservices/Delete.php';
//build/coreBOSTests/phpunit -c build/coreBOSTests/phpunit.xml modules/chatwithme/tests/actions/sbcreatetimetest.php --filter=testprocessP
/*
time hm desc | unit date typeofwork
time hm desc | u d t

3.- no parameter > ask for type
4.- u, d, t
5.- ud, du, ut, tu, dt, td
6.- udt, utd, dut, dtu, tud, tdu

time hm desc | projecttask unit date typeofwork status
time hm desc | p u d t s

3.- no parameter
4.- p, u, d, t, s
5.- pu, pd, pt, ps, up, ud, ut, us, dp, du, dt, ds, tp, tu, td, ts, sp, su, sd, st
6.- pud (perms), put (perms), pus (perms), pdt (perms), pds (perms), pts (perms), udt (perms), uds (perms), uts (perms), dts (perms) => 60
7.-pudt (perms), puds (perms), pdts (perms), puts (perms), udts (perms) => 120
8.- pudts (perms) => 120

time hm desc projecttask projectsubtask | unit date typeofwork status
time hm desc projecttask projectsubtask | u d t s

5.- no parameter > ask for type
6.- u, d, t, s
7.- ud, du, ut, tu, us, su, dt, td, ds, sd, ts, st
8.- udt (perms), uds (perms), uts (perms), dts (perms) => 24
9.- udts (perms) => 24

*/

class testcbmmActionsbcreatetime extends TestCase {

	public static function setUpBeforeClass() {
		global $adb, $current_user;
		$adb->pquery(
			'update vtiger_project set projectname=? where projectid=?',
			array('pname-brand', 6351)
		);
		vtws_upsert(
			'cbMap',
			array(
				'mapname' => 'pname-brand',
				'maptype' => 'Field Set Mapping',
				'targetname' => 'Timecontrol',
				'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
				'content' => '<map>
				<originmodule>
				  <originname>Timecontrol</originname>
				</originmodule>
				<fields>
				  <field>
					<fieldname>typeofwork</fieldname>
					<features>
					  <feature>
						<name>Nuovo Lead</name>
						<value>1</value>
					  </feature>
					  <feature>
						<name>Richiamo</name>
						<value>2</value>
					  </feature>
					</features>
				  </field>
				</fields>
			  </map>',
			),
			'mapname',
			'',
			$current_user
		);
	}

	/**
	 * Method processNoProjectProvidor
	 * params
	 */
	public function processNoProjectProvidor() {
		$cmn = '#time 03:10 ';
		return array(
			array('pname-brand', 'pname-brand', 'teamname',
				'',
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn,
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 1,
						'datestart' => date('Y-m-d'),
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 1,
						'datestart' => date('Y-m-d'),
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc with space"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc with space',
						'units' => 1,
						'datestart' => date('Y-m-d'),
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" TOW',
				array(
					'status' => 6,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 3',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'units' => 3,
						'typeofwork' => 'Richiamo',
						'datestart' => '',
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 2020-01-01',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 3 2020-01-01',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 2020-01-02 2',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-02 Richiamo 2',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-02 2 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 2020-01-02 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 202-41-02 Richiamo',
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time 8:01 desc 3 202-41-02 Richiamo',
				array(
					'status' => 8,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time a:b5 desc 3 202-41-02 Richiamo',
				array(
					'status' => 5,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
		);
	}

	/**
	 * Method testprocessNoProject
	 * @test
	 * @dataProvider processNoProjectProvidor
	 */
	public function testprocessNoProject($cname, $cdname, $tname, $text, $expected) {
		global $adb, $current_user;
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectSubTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$_REQUEST['channel_name'] = $cname;
		$_REQUEST['chnl_dname'] = $cdname;
		$_REQUEST['text'] = $text;
		$_REQUEST['team_name'] = $tname;
		$_REQUEST['team_dname'] = $tname;
		$tcaction = new cbmmActionsbcreatetime();
		$tcaction->process();
		$this->assertEquals($expected['status'], $tcaction->time_status, $text);
		$this->assertEquals($expected['info'], $tcaction->timeinfo, $text);
		$rs = $adb->query('select max(timecontrolid) as id from vtiger_timecontrol inner join vtiger_crmentity on crmid=timecontrolid where deleted=0');
		if ($rs && $rs->fields['id']>0) {
			vtws_delete(vtws_getEntityId('Timecontrol').'x'.$rs->fields['id'], $current_user);
		}
	}

	/**
	 * Method processProjectProvidor
	 * params
	 */
	public function processProjectProvidor() {
		global $adb, $current_user;
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectSubTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rec =  array(
			'default_check' => '1',
			'mandatory' => '0',
			'blocked' => '0',
			'module_list' => '',
			'category' => 'Application',
			'in_module_list' => '',
			'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
			'gvname' => 'CWM_TC_ProjectTask',
			'value' => 1,
		);
		vtws_create('GlobalVariable', $rec, $current_user);
		$cmn = '#time 03:10 ';
		$return = array(
			array('pname-brand', 'pname-brand', 'teamname',
				'',
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn,
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
					)
				)
			),
			/////////////  4
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" TOW',
				array(
					'status' => 10,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'projecttask' => 'Joon Moon Yuc',
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 2020-01-10',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-10',
						'units' => 1,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'taskstatus' => 'Open',
					)
				)
			),
			/////////////////  5
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 2020-01-01 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 1,
						'datestart' => '2020-01-01',
						'taskstatus' => '',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 2020-01-01 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 2020-01-01 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 2020-01-01 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 3 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 3,
						'datestart' => '',
						'taskstatus' => '',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 3 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 3 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" 3 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" "Joon Moon Yuc" Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'units' => 1,
						'datestart' => '',
						'projecttask' => 'Joon Moon Yuc',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" "Joon Moon Yuc" 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" "Joon Moon Yuc" Open',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" "Joon Moon Yuc" 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 3,
						'datestart' => '',
						'taskstatus' => '',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Open Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'units' => 1,
						'datestart' => '',
						'taskstatus' => 'Open',
						'typeofwork' => 'Richiamo',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Open 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Open "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Open 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			//////////////////////////// 6
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo "Joon Moon Yuc" 3',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo 3 "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo "Joon Moon Yuc" 2020-01-01',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo 2020-01-01 "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo "Joon Moon Yuc" Open',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Richiamo Open "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 3 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 2020-01-02 2',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 3 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => date('Y-m-d'),
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo Open 2',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => date('Y-m-d'),
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo 2020-01-01 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-01',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'"desc" Richiamo Open 2020-01-02',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			/////////////
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 3 2020-01-02',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-02',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 2020-01-02 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-02',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 3 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Richiamo 3',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 3 Open',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Open 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 2020-01-01 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Richiamo 2020-01-01',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" 2020-01-01 Open',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Open 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Richiamo Open',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc "Joon Moon Yuc" Open Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			/////////////
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 "Joon Moon Yuc" 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 2020-01-01 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 "Joon Moon Yuc" Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 Richiamo "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 "Joon Moon Yuc" Open',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 Open "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 2020-01-02 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 2020-01-02 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 Open 2020-01-02',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 Richiamo Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => date('Y-m-d'),
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 Open Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => date('Y-m-d'),
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			/////////////
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 "Joon Moon Yuc" 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 3 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 "Joon Moon Yuc" Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 Richiamo "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 "Joon Moon Yuc" Open',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 Open "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-02 Richiamo 2',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-02 2 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'datestart' => '2020-01-02',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'units' => 2,
						'taskstatus' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 3 Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 Open 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 Richiamo Open',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 2020-01-01 Open Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			/////////
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open "Joon Moon Yuc" 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 3 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open "Joon Moon Yuc" 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 2020-01-01 "Joon Moon Yuc"',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open "Joon Moon Yuc" Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open Richiamo "Joon Moon Yuc"',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 1,
						'taskstatus' => 'Open',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 3 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 2020-01-01 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-01',
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 3 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open Richiamo 3',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open 2020-01-01 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc Open Richiamo 2020-01-01',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => 'Open',
					)
				)
			),
			//////////////// ERRORS
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'desc 3 202-41-02 Richiamo',
				array(
					'status' => 9,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time 8:01 desc 3 202-41-02 Richiamo',
				array(
					'status' => 8,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time a:b5 desc 3 202-41-02 Richiamo',
				array(
					'status' => 5,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			/////////////
		);
		//////////////////////////// 7
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 3,
					'projecttask' => 'Joon Moon Yuc',
				)
			)
		);
		$this->tskPermute(array('p','u','d','t'), array(), 4, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 2,
				'info' => array(
					'team' => 'teamname',
					'time' => '03:10',
					'title' => 'desc',
					'typeofwork' => '',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => 'Open',
					'projecttask' => 'Joon Moon Yuc',
				)
			)
		);
		$this->tskPermute(array('p','u','d','s'), array(), 4, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 1,
					'taskstatus' => 'Open',
					'projecttask' => 'Joon Moon Yuc',
				)
			)
		);
		$this->tskPermute(array('p','d','t','s'), array(), 4, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => date('Y-m-d'),
					'units' => 3,
					'taskstatus' => 'Open',
					'projecttask' => 'Joon Moon Yuc',
				)
			)
		);
		$this->tskPermute(array('p','u','t','s'), array(), 4, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 9,
				'info' => array(
					'team' => 'teamname',
					'time' => '03:10',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => 'Open',
				)
			)
		);
		$this->tskPermute(array('u','d','t','s'), array(), 4, $return, $test);
		//////////////////////////// 8
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => 'Open',
					'projecttask' => 'Joon Moon Yuc',
				)
			)
		);
		$this->tskPermute(array('p', 'u','d','t','s'), array(), 5, $return, $test);
		return $return;
	}

	private function tskPermute($items, $perms = array(), $parts = 4, &$return = array(), $test = array()) {
		$pudts = array(
			'p' => '"Joon Moon Yuc"', // p
			'u' => '3', // u
			'd' => '2020-01-01', // d
			't' => 'Richiamo', // t
			's' => 'Open', // s
		);
		if (empty($items)) {
			return $perms;
		} else {
			for ($i = count($items) - 1; $i >= 0; --$i) {
				$newitems = $items;
				$newperms = $perms;
				list($foo) = array_splice($newitems, $i, 1);
				array_unshift($newperms, $foo);
				$ret = $this->tskPermute($newitems, $newperms, $parts, $return, $test);
				if (!is_null($ret)) {
					$test[3] = '#time 03:10 desc '.$pudts[$ret[0]].' '.$pudts[$ret[1]].' '.$pudts[$ret[2]].' '.$pudts[$ret[3]];
					if ($parts>4) {
						$test[3] .= ' '.$pudts[$ret[4]];
					}
					$return[] = $test;
				}
			}
		}
	}

	private function subtskPermute($items, $perms = array(), $parts = 4, &$return = array(), $test = array()) {
		$pudts = array(
			'u' => '3', // u
			'd' => '2020-01-01', // d
			't' => 'Richiamo', // t
			's' => '40%', // s
		);
		if (empty($items)) {
			return $perms;
		} else {
			for ($i = count($items) - 1; $i >= 0; --$i) {
				$newitems = $items;
				$newperms = $perms;
				list($foo) = array_splice($newitems, $i, 1);
				array_unshift($newperms, $foo);
				$ret = $this->subtskPermute($newitems, $newperms, $parts, $return, $test);
				if (!is_null($ret)) {
					$test[3] = '#time 03:10 desc "Joon Moon Yuc" PSubTask '.$pudts[$ret[0]].' '.$pudts[$ret[1]];
					if ($parts>2) {
						$test[3] .= ' '.$pudts[$ret[2]];
					}
					if ($parts>3) {
						$test[3] .= ' '.$pudts[$ret[3]];
					}
					if ($parts>4) {
						$test[3] .= ' '.$pudts[$ret[4]];
					}
					$return[] = $test;
				}
			}
		}
	}

	/**
	 * Method testprocessProject
	 * @test
	 * @dataProvider processProjectProvidor
	 */
	public function testprocessProject($cname, $cdname, $tname, $text, $expected) {
		global $adb, $current_user;
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectSubTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rec =  array(
			'default_check' => '1',
			'mandatory' => '0',
			'blocked' => '0',
			'module_list' => '',
			'category' => 'Application',
			'in_module_list' => '',
			'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
			'gvname' => 'CWM_TC_ProjectTask',
			'value' => 1,
		);
		vtws_upsert('GlobalVariable', $rec, 'gvname', implode(',', array_keys($rec)), $current_user);
		$_REQUEST['channel_name'] = $cname;
		$_REQUEST['chnl_dname'] = $cdname;
		$_REQUEST['text'] = $text;
		$_REQUEST['team_name'] = $tname;
		$_REQUEST['team_dname'] = $tname;
		$tcaction = new cbmmActionsbcreatetime();
		$tcaction->process();
		$this->assertEquals($expected['status'], $tcaction->time_status, $text);
		$this->assertEquals($expected['info'], $tcaction->timeinfo, $text);
		$rs = $adb->query('select max(timecontrolid) as id from vtiger_timecontrol inner join vtiger_crmentity on crmid=timecontrolid where deleted=0');
		if ($rs && $rs->fields['id']>0) {
			vtws_delete(vtws_getEntityId('Timecontrol').'x'.$rs->fields['id'], $current_user);
		}
	}

	/**
	 * Method processSubTaskProvidor
	 * params
	 */
	public function processSubTaskProvidor() {
		global $adb, $current_user;
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectSubTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rec =  array(
			'default_check' => '1',
			'mandatory' => '0',
			'blocked' => '0',
			'module_list' => '',
			'category' => 'Application',
			'in_module_list' => '',
			'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
			'gvname' => 'CWM_TC_ProjectSubTask',
			'value' => 1,
		);
		vtws_create('GlobalVariable', $rec, $current_user);
		$cmn = '#time 03:10 desc "Joon Moon Yuc" PSubTask ';
		$return = array(
			array('pname-brand', 'pname-brand', 'teamname',
				'',
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time 03:10 desc',
				array(
					'status' => 3,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			/////////////  5
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn,
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => '1',
						'datestart' => date('Y-m-d'),
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
			'#time 03:10 desc NotExists PSubTask ',
				array(
					'status' => 10,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
			'#time 03:10 desc "Joon Moon Yuc" NotExists',
				array(
					'status' => 11,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
					)
				)
			),
			/////////////  6
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'2020-01-02',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '2020-01-02',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'40%',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'taskstatus' => '40%',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'TOW',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			/////////////  7
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'3 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 3,
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'2020-01-01 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 3,
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'3 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '',
						'units' => 3,
						'taskstatus' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'Richiamo 3',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '',
						'units' => 3,
						'taskstatus' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'3 40%',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => '',
						'datestart' => '',
						'units' => 3,
						'taskstatus' => '40%',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'40% 3',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'datestart' => '',
						'units' => 3,
						'taskstatus' => '40%',
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'Richiamo 2020-01-01',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => '',
						'typeofwork' => 'Richiamo',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'2020-01-01 Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => '',
						'typeofwork' => 'Richiamo',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'40% 2020-01-01',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => '40%',
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'2020-01-01 40%',
				array(
					'status' => 2,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'datestart' => '2020-01-01',
						'units' => 1,
						'taskstatus' => '40%',
						'typeofwork' => '',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'Richiamo 40%',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'datestart' => '',
						'units' => 1,
						'taskstatus' => '40%',
						'typeofwork' => 'Richiamo',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'40% Richiamo',
				array(
					'status' => 1,
					'info' => array(
						'team' => 'teamname',
						'time' => '3h 10m',
						'title' => 'desc',
						'datestart' => '',
						'units' => 1,
						'taskstatus' => '40%',
						'typeofwork' => 'Richiamo',
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			//////////////// ERRORS
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'202-41-02 Richiamo',
				array(
					'status' => 13,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => '',
						'units' => 1,
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				$cmn.'3 202-41-02 Richiamo',
				array(
					'status' => 13,
					'info' => array(
						'team' => 'teamname',
						'time' => '03:10',
						'title' => 'desc',
						'typeofwork' => 'Richiamo',
						'datestart' => date('Y-m-d'),
						'units' => 3,
						'projecttask' => 'Joon Moon Yuc',
						'projectsubtask' => 'PSubTask',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time 8:01 desc "Joon Moon Yuc" PSubTask 3 202-41-02 Richiamo',
				array(
					'status' => 8,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			array('pname-brand', 'pname-brand', 'teamname',
				'#time a:b5 desc "Joon Moon Yuc" PSubTask 3 202-41-02 Richiamo',
				array(
					'status' => 5,
					'info' => array(
						'team' => 'teamname',
					)
				)
			),
			/////////////
		);
		/////////////  8
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => '',
					'projecttask' => 'Joon Moon Yuc',
					'projectsubtask' => 'PSubTask',
			)
			)
		);
		$this->subtskPermute(array('u','d','t'), array(), 3, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 2,
				'info' => array(
					'team' => 'teamname',
					'time' => '03:10',
					'title' => 'desc',
					'typeofwork' => '',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => '40%',
					'projecttask' => 'Joon Moon Yuc',
					'projectsubtask' => 'PSubTask',
				)
			)
		);
		$this->subtskPermute(array('u','d','s'), array(), 3, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => date('Y-m-d'),
					'units' => 3,
					'taskstatus' => '40%',
					'projecttask' => 'Joon Moon Yuc',
					'projectsubtask' => 'PSubTask',
				)
			)
		);
		$this->subtskPermute(array('u','t','s'), array(), 3, $return, $test);
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 1,
					'taskstatus' => '40%',
					'projecttask' => 'Joon Moon Yuc',
					'projectsubtask' => 'PSubTask',
				)
			)
		);
		$this->subtskPermute(array('d','t','s'), array(), 3, $return, $test);
		/////////////  9
		$test = array(
			'pname-brand',
			'pname-brand',
			'teamname',
			'',
			array(
				'status' => 1,
				'info' => array(
					'team' => 'teamname',
					'time' => '3h 10m',
					'title' => 'desc',
					'typeofwork' => 'Richiamo',
					'datestart' => '2020-01-01',
					'units' => 3,
					'taskstatus' => '40%',
					'projecttask' => 'Joon Moon Yuc',
					'projectsubtask' => 'PSubTask',
				)
			)
		);
		$this->subtskPermute(array('u','d','t','s'), array(), 4, $return, $test);
		return $return;
	}

	/**
	 * Method testprocessSubTask
	 * @test
	 * @dataProvider processSubTaskProvidor
	 */
	public function testprocessSubTask($cname, $cdname, $tname, $text, $expected) {
		global $adb, $current_user;
		$rs = $adb->pquery(
			'select globalvariableid from vtiger_globalvariable inner join vtiger_crmentity on crmid=globalvariableid where deleted=0 and gvname=?',
			array('CWM_TC_ProjectTask')
		);
		if ($rs && $adb->num_rows($rs)>0) {
			vtws_delete(vtws_getEntityId('GlobalVariable').'x'.$rs->fields['globalvariableid'], $current_user);
		}
		$rec =  array(
			'default_check' => '1',
			'mandatory' => '0',
			'blocked' => '0',
			'module_list' => '',
			'category' => 'Application',
			'in_module_list' => '',
			'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
			'gvname' => 'CWM_TC_ProjectSubTask',
			'value' => 1,
		);
		vtws_upsert('GlobalVariable', $rec, 'gvname', implode(',', array_keys($rec)), $current_user);
		$_REQUEST['channel_name'] = $cname;
		$_REQUEST['chnl_dname'] = $cdname;
		$_REQUEST['text'] = $text;
		$_REQUEST['team_name'] = $tname;
		$_REQUEST['team_dname'] = $tname;
		$tcaction = new cbmmActionsbcreatetime();
		$tcaction->process();
		$this->assertEquals($expected['status'], $tcaction->time_status, $text);
		$this->assertEquals($expected['info'], $tcaction->timeinfo, $text);
		$rs = $adb->query('select max(timecontrolid) as id from vtiger_timecontrol inner join vtiger_crmentity on crmid=timecontrolid where deleted=0');
		if ($rs && $rs->fields['id']>0) {
			vtws_delete(vtws_getEntityId('Timecontrol').'x'.$rs->fields['id'], $current_user);
		}
	}
}