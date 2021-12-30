<?php
/*************************************************************************************************
 * Copyright 2020 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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

class addCWM_TCFields extends cbupdaterWorker {

	public function applyChange() {
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			$fields = array(
				'Project' => array(
					'LBL_PROJECT_INFORMATION' => array(
						'brand' => array(
							'columntype'=>'varchar(200)',
							'typeofdata'=>'V~O',
							'uitype'=>'15',
							'displaytype'=>'1',
							'label'=>'Brand',
							'massedit' => 0,
							'vals' => array('--None--'),
						),
						'teamname' => array(
							'columntype'=>'varchar(200)',
							'typeofdata'=>'V~O',
							'uitype'=>'1',
							'displaytype'=>'1',
							'label'=>'Team',
							'massedit' => 1,
						),
					)
				),
				'Users' => array(
					'LBL_MORE_INFORMATION' => array(
						'mmteam' => array(
							'columntype'=>'varchar(200)',
							'typeofdata'=>'V~O',
							'uitype'=>'1',
							'displaytype'=>'1',
							'label'=>'MM Team',
							'massedit' => 0,
						),
					),
				),
				'Timecontrol' => array(
					'LBL_TIMECONTROL_INFORMATION' => array(
						'typeofwork' => array(
							'label' => 'Type of Work',
							'columntype'=>'VARCHAR(300)',
							'typeofdata'=>'V~O',
							'uitype'=>'1',
							'displaytype'=>'1',
						),
						'prjtask' => array(
							'label' => 'Project Task',
							'columntype'=>'INT(11)',
							'typeofdata'=>'V~O',
							'uitype'=>'10',
							'displaytype'=>'1',
							'mods' => array('ProjectTask'),
						),
						'prjsubtask' => array(
							'label' => 'Project SubTask',
							'columntype'=>'INT(11)',
							'typeofdata'=>'V~O',
							'uitype'=>'10',
							'displaytype'=>'1',
							'mods' => array('ProjectSubTask'),
						),
						'tcname' => array(
							'label' => 'Timecontrol Name',
							'columntype'=>'VARCHAR(300)',
							'typeofdata'=>'V~O',
							'uitype'=>'1',
							'displaytype'=>'1',
						),
						'team' => array(
							'columntype'=>'varchar(200)',
							'typeofdata'=>'V~O',
							'uitype'=>'1',
							'displaytype'=>'1',
							'label'=>'Team',
							'massedit' => 1,
						),
						'brand' => array(
							'columntype'=>'varchar(200)',
							'typeofdata'=>'V~O',
							'uitype'=>'15',
							'displaytype'=>'1',
							'label'=>'Brand',
							'massedit' => 1,
							'vals' => array('--None--'),
						),
					),
				),
			);
			$this->massCreateFields($fields);
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied();
		}
		$this->finishExecution();
	}
}
