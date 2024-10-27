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

class cbmmActionshow extends chatactionclass {
	private $questionid;
	private $status;
	const STATUS_RETURNRESULTS = 1;
	const STATUS_FOUNDSOME = 2;
	const STATUS_NOTFOUND = 3;
	const STATUS_NOPERMISSION = 4;
	const STATUS_BADFORMAT = 5;

	public function getHelp() {
		return ' - '.getTranslatedString('show_command', 'chatwithme');
	}

	public function process() {
		global $adb, $current_user;
		$req = getMMRequest();
		$prm = parseMMMsg($req['text']);
		$this->questionid = 0;
		$this->status = 0;
		$numprms = count($prm);
		if ($numprms==1 || empty($prm[1])) {
			$this->status = self::STATUS_BADFORMAT;
		} else {
			if (!is_numeric($prm[1])) {
				$qname = '';
				for ($t=1; $t<$numprms; $t++) {
					$qname .= ' '.$prm[$t];
				}
				$qname = trim($qname);
				$queryGenerator = new QueryGenerator('cbQuestion', $current_user);
				$queryGenerator->setFields(array('id'));
				$queryGenerator->addCondition('qname', $qname, 'e');
				$query = $queryGenerator->getQuery();
				$qrs = $adb->pquery($query, array());
				if ($adb->num_rows($qrs)==1) {
					$prm[1] = $adb->query_result($qrs, 0, 'cbquestionid');
				} else {
					if ($adb->num_rows($qrs)==0) {
						$queryGenerator = new QueryGenerator('cbQuestion', $current_user);
						$queryGenerator->setFields(array('id'));
						$queryGenerator->addCondition('qname', $qname, 'c');
						$query = $queryGenerator->getQuery();
						$qrs = $adb->pquery($query, array());
						if ($adb->num_rows($qrs)==0) {
							$this->status = self::STATUS_NOTFOUND;
						} else {
							$this->status = self::STATUS_FOUNDSOME;
							$this->questionid = $qname;
						}
					} else {
						$this->status = self::STATUS_FOUNDSOME;
						$this->questionid = $qname;
					}
				}
			}
			if ($this->status == 0) {
				if (!isRecordExists($prm[1]) || getSalesEntityType($prm[1])!='cbQuestion') {
					$this->status = self::STATUS_NOTFOUND;
				} elseif (isPermitted('cbQuestion', 'DetailView', $prm[1]) != 'yes') {
					$this->status = self::STATUS_NOPERMISSION;
				} else {
					$this->status = self::STATUS_RETURNRESULTS;
					$this->questionid = $prm[1];
				}
			}
		}
		return true;
	}

	public function getResponse() {
		switch ($this->status) {
			case self::STATUS_FOUNDSOME:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('yellow'),
						'title' => getTranslatedString('FoundSome', 'chatwithme'),
						'text' => getTranslatedString('OneOfThese', 'chatwithme')."\n\n".$this->getQuestionCandidates(),
					)),
				);
				break;
			case self::STATUS_RETURNRESULTS:
				$ret = $this->getQuestionResultsMsg();
				break;
			case self::STATUS_NOPERMISSION:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_PERMISSION'),
					)),
				);
				break;
			case self::STATUS_NOTFOUND:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('LBL_RECORD_NOT_FOUND', 'chatwithme'),
					)),
				);
				break;
			case self::STATUS_BADFORMAT:
			default:
				$ret = getMMDoNotUnderstandMessage($this->getHelp());
				break;
		}
		return $ret;
	}

	private function getQuestionCandidates() {
		global $adb;
		$qrs = $adb->pquery(
			'select cbquestionid,qname
				from vtiger_cbquestion
				inner join vtiger_crmentity on crmid = cbquestionid
				where deleted=0 and qname like ?',
			array('%'.$this->questionid.'%')
		);
		$md = '| ID | '.getTranslatedString('qname', 'cbQuestion').' |'."\n";
		$md .= '|----|----|'."\n";
		while ($q = $adb->fetch_array($qrs)) {
			$md .= '| '.$q['cbquestionid'].' | '.$q['qname']."\n";
		}
		return $md;
	}

	private function getQuestionResultsMsg() {
		include_once 'modules/cbQuestion/cbQuestion.php';
		$q = cbQuestion::getAnswer($this->questionid);
		switch ($q['type']) {
			case 'ERROR':
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('red'),
						'title' => getTranslatedString('QuestionError', 'chatwithme'),
					)),
				);
				break;
			case 'Number':
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('blue'),
						'title' => $this->getNumberQuestionMD($q['answer'], $q['properties'], $q['module'], $q['title']),
					)),
				);
				break;
			case 'RowChart':
			case 'Pie':
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('blue'),
						'title' => $q['title'],
					)),
					'props' => array('chartdata'=>$this->getChartQuestionMD($q['answer'], $q['properties'], $q['module'], $q['type'])),
				);
				break;
			case 'Mermaid':
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('blue'),
						'title' => $q['title'],
					)),
					'props' => array('mermaidData'=>$q['answer']),
				);
				break;
			case 'Table':
			default:
				$ret = array(
					'response_type' => 'in_channel',
					'attachments' => array(array(
						'color' => getMMMsgColor('blue'),
						'title' => $q['title'],
						'text'=> $this->getTableQuestionMD($q['answer'], $q['properties'], $q['module']),
					)),
				);
		}
		return $ret;
	}

	private function getNumberQuestionMD($response, $props, $module, $title) {
		$ps = json_decode($props, true);
		if (json_last_error()!= JSON_ERROR_NONE || empty($props) || empty($ps) || empty($ps['columnlabels'])) {
			$md = $title.': ';
		} else {
			$md = getTranslatedString($ps['columnlabels'][0], $module).': ';
		}
		$md .= reset($response[0]);
		return $md;
	}

	private function getTableQuestionMD($table, $props, $module) {
		global $site_URL;
		$ps = json_decode($props, true);
		if (json_last_error()!= JSON_ERROR_NONE || empty($props) || empty($ps) || empty($ps['columnlabels'])) {
			$headers = array_keys($table[0]);
			$md = '| ';
			$dashes = '|';
			foreach ($headers as $fname) {
				if ($fname == 'id') {
					$md .= getTranslatedString($module.' ID', $module).' | ';
				} else {
					$md .= getTranslatedString($fname, $module).' | ';
				}
				$dashes .= '----|';
			}
		} else {
			$md = '| ';
			$dashes = '|';
			foreach ($ps['columnlabels'] as $fname) {
				if ($fname == 'id') {
					$md .= ' ID | ';
				} else {
					$md .= getTranslatedString($fname, $module).' | ';
				}
				$dashes .= '----|';
			}
		}
		$md = trim($md)."\n".$dashes."\n";
		$list_max_entries_per_page = GlobalVariable::getVariable('Application_ListView_PageSize', 20);
		$cnt = 0;
		foreach ($table as $row) {
			$md .= '| ';
			foreach ($row as $fname => $fvalue) {
				if ($fname == 'id') {
					$crmid = vtws_getCRMID($fvalue);
					$md .= "[$crmid]($site_URL/index.php?action=DetailView&module=$module&record=$crmid) | ";
				} else {
					$md .= $fvalue.' | ';
				}
			}
			$md = trim($md)."\n";
			$cnt++;
			if ($cnt>$list_max_entries_per_page) {
				break;
			}
		}
		if ($cnt<count($table)) {
			$md .= "\n\n[".getTranslatedString('ClickHereForFullResults', 'chatwithme').'](todo)';
		}
		return $md;
	}

	private function getChartQuestionMD($chartdata, $props, $module, $type) {
		include_once 'modules/chatwithme/vendor/RandomColor.php';
		$ps = json_decode($props, true);
		if (json_last_error()!= JSON_ERROR_NONE || empty($props) || empty($ps)) {
			$keys = array_keys($chartdata[0]);
			$keylabel = $keys[0];
			$keyvalue = $keys[1];
		} else {
			$keylabel = $ps['key_label'];
			$keyvalue = $ps['key_value'];
		}
		$data = array(
			'labels' => array(),
			'datasets' => array(array(
				'data' => array(),
				'backgroundColor' => array(),
			)),
		);
		foreach ($chartdata as $row) {
			$data['labels'][] = $row[$keylabel];
			$data['datasets'][0]['data'][] = $row[$keyvalue];
			$data['datasets'][0]['backgroundColor'][] = RandomColor::one(array(
				'luminosity' => 'dark',
				'hue' => 'random'
			));
		}
		return array(
			'type' => $type,
			'data' => $data,
		);
	}
}
?>
