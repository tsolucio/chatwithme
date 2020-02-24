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
require_once 'modules/chatwithme/subitoutils.php';
include_once 'modules/Timecontrol/Timecontrol.php';

class cbmmActionsbcreatetime extends chatactionclass {
	const NOOP = -1;
	const STATUS_TIMER_CLOSED = 1;
	const STATUS_MISSINGTYPE = 2;
	const STATUS_BADFORMAT = 3;
	const STATUS_DATEFORMAT = 4;
	const STATUS_TIMEFORMAT = 5;
	const STATUS_TYPE_NOTFOUND = 6;
	const STATUS_TIMEOVER8TOTAL = 7;
	const STATUS_TIMEOVER8 = 8;
	const STATUS_MISSINGPRJTASK = 9;
	const STATUS_PRJTASK_NOTFOUND = 10;
	const STATUS_PRJSUBTASK_NOTFOUND = 11;
	const STATUS_PRJTASKSTATUS_NOTFOUND = 12;
	const STATUS_PRJSUBTASKSTATUS_NOTFOUND = 13;
	const STATUS_TIMER_CLOSED_STATUSNOTCHANGED=14;
	public $time_status;
	public $timeinfo = array();

	public function getHelp() {
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		if ($prjsubtsk) {
			return ' - '.getTranslatedString('sbcreatetime_commandTskSubTsk', 'chatwithme');
		} elseif ($prjtsk && !$prjsubtsk) {
			return ' - '.getTranslatedString('sbcreatetime_commandTsk', 'chatwithme');
		} else {
			return ' - '.getTranslatedString('sbcreatetime_command', 'chatwithme');
		}
	}

	public function process() {
		global $current_user;
		$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
		$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
		if ($prjsubtsk && !$prjtsk) {
			$prjtsk = 1;
		}
		if ($prjsubtsk) {
			$paramoffset = 2;
		} else {
			$paramoffset = 0;
		}
		$req = getMMRequest();
		$this->timeinfo['team'] = $req['team_dname'];
		$prm = parseMMMsgWithQuotes($req['text']);
		if (count($prm)<(3+$paramoffset)) {
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		}
		$time = $this->processTimeField($prm[1]);
		if (!preg_match('/\d\d:\d\d/', $time)) {
			$this->time_status = self::STATUS_TIMEFORMAT;
			return true;
		}
		list($h, $m) = explode(':', $time);
		if (((int)$h*60+(int)$m)>8*60) {
			$this->time_status = self::STATUS_TIMEOVER8;
			return true;
		}
		$this->timeinfo['time'] = $time;
		$title = $prm[2];
		$this->timeinfo['title'] = $title;
		$this->timeinfo['typeofwork'] = '';
		// validate project task and project subtask
		if ($prjsubtsk) {
			if (!sbProjectTaskExist($req['channel_dname'], $prm[2])) {
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
				return true;
			}
			$this->timeinfo['projecttask'] = $prm[2];
			if (!sbProjectSubTaskExist($req['channel_dname'], $prm[2], $prm[3])) {
				$this->time_status = self::STATUS_PRJSUBTASK_NOTFOUND;
				return true;
			}
			$this->timeinfo['projectsubtask'] = $prm[3];
		}
		if (count($prm)==3+$paramoffset) {
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			// validate project task and project subtask
			if ($prjsubtsk) {
				if (!sbProjectTaskExist($req['channel_dname'], $prm[3])) {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
				$this->timeinfo['projecttask'] = $prm[3];
				if (!sbProjectSubTaskExist($req['channel_dname'], $prm[3], $prm[4])) {
					$this->time_status = self::STATUS_PRJSUBTASK_NOTFOUND;
					return true;
				}
				$this->timeinfo['projectsubtask'] = $prm[4];
			} elseif ($prjtsk) {
				$this->time_status = self::STATUS_MISSINGPRJTASK;
				return true;
			}
			if (!sbTypeOfWorkMapExist($req['channel_dname'])) {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
			$this->time_status = self::STATUS_MISSINGTYPE;
			$this->timeinfo['units'] = 1;
			$this->timeinfo['datestart'] = date('Y-m-d');
			return true;
		}
		if (count($prm)==4+$paramoffset) {
			$param = $prm[3+$paramoffset];
			if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param) || preg_match('/^\d\d\-\d\d$/', $param)
			) {
				$this->processDateField($param);
				if ($this->time_status == self::STATUS_MISSINGTYPE && $prjtsk && $paramoffset==0) {
					$this->time_status = self::STATUS_MISSINGPRJTASK;
				}
				return true;
			} elseif (is_numeric($param)) {
				// we have units > ask for type of request
				$this->time_status = self::STATUS_MISSINGTYPE;
				if ($prjtsk && $paramoffset==0) {
					$this->time_status = self::STATUS_MISSINGPRJTASK;
				}
				$units = (int)$param;
				$this->timeinfo['units'] = (int)$param;
				return true;
			} else {
				$tow = sbgetTypeOfWork($req['channel_dname'], $param);
				if ($tow) {
					// we have all we need, units=1, date=today
					$cn = explode('-', $req['channel_dname']);
					$brand = $cn[0];
					$prjtype = $cn[1];
					if (Timecontrol::userTotalTime(date('Y-m-d'), $current_user->id)>8*60) {
						$this->time_status = self::STATUS_TIMEOVER8TOTAL;
						return true;
					}
					if ($prjtsk && $paramoffset==0) {
						$this->timeinfo['typeofwork'] = $param;
						$this->time_status = self::STATUS_MISSINGPRJTASK;
						return true;
					}
					$result = stoptimerDoCreateTC(time(), $time, $brand, $prjtype, $title, $param, 1, $req['team_dname'], '', '');
					$time_array = explode(':', $result['totaltime']);
					$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
					$this->timeinfo['title'] = $title;
					$this->timeinfo['typeofwork'] = $param;
					$this->time_status = self::STATUS_TIMER_CLOSED;
					return true;
				} else {
					if ($prjsubtsk) {
						$sttsk = sbgetPrjSubTaskStatus($param);
						if ($sttsk) {
							// we have status > ask for type of request
							$this->time_status = self::STATUS_MISSINGTYPE;
							$this->timeinfo['taskstatus'] = $param;
							return true;
						} else {
							$this->time_status = self::STATUS_MISSINGTYPE;
							return true;
						}
					} elseif ($prjtsk) {
						$sttsk = sbgetPrjTaskStatus($param);
						if (!$sttsk) {
							if (sbProjectTaskExist($req['channel_dname'], $param)) {
								// we have task > ask for type of request
								$this->time_status = self::STATUS_MISSINGTYPE;
								$this->timeinfo['projecttask'] = $param;
								return true;
							}
							$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
							return true;
						} else {
							// we have status > ask for project task
							$this->time_status = self::STATUS_MISSINGPRJTASK;
							$this->timeinfo['taskstatus'] = $param;
							return true;
						}
					} else {
						$this->time_status = self::STATUS_TYPE_NOTFOUND;
						return true;
					}
				}
			}
		}
		if (count($prm)==5+$paramoffset) {
			$param3 = $prm[3+$paramoffset];
			$param4 = $prm[4+$paramoffset];
			if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param3)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d$/', $param3)
			) {
				$date = $param3;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$type = '';
					$tskstatus = '';
					$this->time_status = self::STATUS_MISSINGTYPE;
					if ($prjtsk && !$prjsubtsk) {
						$this->time_status = self::STATUS_MISSINGPRJTASK;
					}
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = (int)$param4;
					return true;
				} else {
					$units = 1;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param4)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d$/', $param4)
			) {
				$date = $param4;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$type = '';
					$tskstatus = '';
					$this->time_status = self::STATUS_MISSINGTYPE;
					if ($prjtsk && !$prjsubtsk) {
						$this->time_status = self::STATUS_MISSINGPRJTASK;
					}
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = (int)$param3;
					return true;
				} else {
					$units = 1;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($param3, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (is_numeric($param3)) {
				$date = '';
				$units = (int)$param3;
				$this->timeinfo['datestart'] = $date;
				$this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->processUnkownTextField($param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} elseif (is_numeric($param4)) {
				$date = '';
				$units = (int)$param4;
				$this->timeinfo['datestart'] = $date;
				$this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->processUnkownTextField($param3, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			} else {
				$date = '';
				$units = 1;
				$this->timeinfo['datestart'] = '';
				$this->timeinfo['units'] = $units;
				$this->time_status = self::NOOP;
				$this->process2UnkownTextField($param3, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
				if ($this->time_status != self::NOOP) {
					return true;
				}
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			if ($date != '') {
				$this->processDateField($date);
				if ($this->time_status == self::STATUS_DATEFORMAT) {
					return true;
				}
				list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
				$date = mktime(0, 0, 0, $m, $d, $y);
			} else {
				$date = time();
			}
			$this->timeinfo['units'] = $units;
			$type = $this->timeinfo['typeofwork'];
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$pt = (empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask']);
				$pst = (empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask']);
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname'], $pt, $pst);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				$tskstatus = isset($this->timeinfo['taskstatus']) ? $this->timeinfo['taskstatus'] : '';
				if ($tskstatus!='') {
					if ($prjsubtsk) {
						$chgstatus = changePrjSubTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					} elseif ($prjtsk) {
						$chgstatus = changePrjTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					}
				}
				return true;
			} elseif ($type != '') {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			} else {
				$this->time_status = self::STATUS_MISSINGTYPE;
				return true;
			}
		}
		if (count($prm)==6+$paramoffset) {
			$param3 = $prm[3+$paramoffset];
			$param4 = $prm[4+$paramoffset];
			$param5 = $prm[5+$paramoffset];
			if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param3)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d$/', $param3)
			) {
				$date = $param3;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$workwith = $param5;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param4)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d$/', $param4)
			) {
				$date = $param4;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$workwith = $param5;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param3, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param5)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d$/', $param5)
			) {
				$date = $param5;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param4)) {
					$units = (int)$param4;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->processUnkownTextField($workwith, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param3, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} else {
				$date = date('Y-m-d');
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param4)) {
					$units = (int)$param4;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param3, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($param3, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					if ($prjsubtsk) {
						$this->time_status = self::STATUS_BADFORMAT;
						return true;
					}
					$units = 1;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			$this->processDateField($date);
			if ($this->time_status == self::STATUS_DATEFORMAT) {
				return true;
			}
			list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
			$date = mktime(0, 0, 0, $m, $d, $y);
			$this->timeinfo['units'] = $units;
			$type = $this->timeinfo['typeofwork'];
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$pt = (empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask']);
				$pst = (empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask']);
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname'], $pt, $pst);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				$tskstatus = isset($this->timeinfo['taskstatus']) ? $this->timeinfo['taskstatus'] : '';
				if ($tskstatus!='') {
					if ($prjsubtsk) {
						$chgstatus = changePrjSubTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					} elseif ($prjtsk) {
						$chgstatus = changePrjTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					}
				}
				return true;
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		if (count($prm)==7+$paramoffset) {
			$param3 = $prm[3+$paramoffset];
			$param4 = $prm[4+$paramoffset];
			$param5 = $prm[5+$paramoffset];
			$param6 = $prm[6+$paramoffset];
			if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param3)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d$/', $param3)
			) {
				$date = $param3;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$workwith = $param5;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($workwith, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param4)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d$/', $param4)
			) {
				$date = $param4;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$workwith = $param5;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($workwith, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param5)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d$/', $param5)
			) {
				$date = $param5;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param4)) {
					$units = (int)$param4;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($workwith, $param4, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param6) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param6)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param6) || preg_match('/^\d\d\-\d\d$/', $param6)
			) {
				$date = $param6;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$workwith = $param4;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param4)) {
					$units = (int)$param4;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process2UnkownTextField($workwith, $param4, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$units = 1;
					$workwith = $param3;
					$this->timeinfo['datestart'] = $date;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($workwith, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				}
			} else {
				$date = date('Y-m-d');
				if ($prjsubtsk) {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				} else {
					$this->timeinfo['datestart'] = '';
					if (is_numeric($param3)) {
						$units = (int)$param3;
						$this->timeinfo['units'] = $units;
						$this->time_status = self::NOOP;
						$this->process3UnkownTextField($param4, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
						if ($this->time_status != self::NOOP) {
							return true;
						}
					} elseif (is_numeric($param4)) {
						$units = (int)$param4;
						$this->timeinfo['units'] = $units;
						$this->time_status = self::NOOP;
						$this->process3UnkownTextField($param3, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
						if ($this->time_status != self::NOOP) {
							return true;
						}
					} elseif (is_numeric($param5)) {
						$units = (int)$param5;
						$this->timeinfo['units'] = $units;
						$this->time_status = self::NOOP;
						$this->process3UnkownTextField($param3, $param4, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
						if ($this->time_status != self::NOOP) {
							return true;
						}
					} elseif (is_numeric($param6)) {
						$units = (int)$param6;
						$this->timeinfo['units'] = $units;
						$this->time_status = self::NOOP;
						$this->process3UnkownTextField($param3, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
						if ($this->time_status != self::NOOP) {
							return true;
						}
					} else {
						$this->time_status = self::STATUS_BADFORMAT;
						return true;
					}
				}
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			$this->processDateField($date);
			if ($this->time_status == self::STATUS_DATEFORMAT) {
				return true;
			}
			list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
			$date = mktime(0, 0, 0, $m, $d, $y);
			$this->timeinfo['units'] = $units;
			$type = $this->timeinfo['typeofwork'];
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$pt = (empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask']);
				$pst = (empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask']);
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname'], $pt, $pst);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				$tskstatus = isset($this->timeinfo['taskstatus']) ? $this->timeinfo['taskstatus'] : '';
				if ($tskstatus!='') {
					if ($prjsubtsk) {
						$chgstatus = changePrjSubTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					} elseif ($prjtsk) {
						$chgstatus = changePrjTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					}
				}
				return true;
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		if (count($prm)==8) {
			$param3 = $prm[3];
			$param4 = $prm[4];
			$param5 = $prm[5];
			$param6 = $prm[6];
			$param7 = $prm[7];
			if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param3)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param3) || preg_match('/^\d\d\-\d\d$/', $param3)
			) {
				$date = $param3;
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param5, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param5, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param7)) {
					$units = (int)$param7;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param4)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param4) || preg_match('/^\d\d\-\d\d$/', $param4)
			) {
				$date = $param4;
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param3)) {
					$units = (int)$param3;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param5, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param5, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param7)) {
					$units = (int)$param7;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param5)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param5) || preg_match('/^\d\d\-\d\d$/', $param5)
			) {
				$date = $param5;
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param3)) {
					$units = (int)$param3;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param6, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param7)) {
					$units = (int)$param7;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param6) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param6)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param6) || preg_match('/^\d\d\-\d\d$/', $param6)
			) {
				$date = $param6;
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param5, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param3)) {
					$units = (int)$param3;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param5, $param7, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param7)) {
					$units = (int)$param7;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				}
			} elseif (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $param7) || preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $param7)
				|| preg_match('/^\d\d\-\d\d\-\d\d$/', $param7) || preg_match('/^\d\d\-\d\d$/', $param7)
			) {
				$date = $param7;
				$this->timeinfo['datestart'] = $date;
				if (is_numeric($param4)) {
					$units = (int)$param4;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param5)) {
					$units = (int)$param5;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param3)) {
					$units = (int)$param3;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param4, $param5, $param6, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} elseif (is_numeric($param6)) {
					$units = (int)$param6;
					$this->timeinfo['units'] = $units;
					$this->time_status = self::NOOP;
					$this->process3UnkownTextField($param3, $param4, $param5, $req['channel_dname'], $prjtsk, $prjsubtsk);
					if ($this->time_status != self::NOOP) {
						return true;
					}
				} else {
					$this->time_status = self::STATUS_BADFORMAT;
					return true;
				}
			} else {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			if (!is_numeric($units)) {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
			$this->processDateField($date);
			if ($this->time_status == self::STATUS_DATEFORMAT) {
				return true;
			}
			list($y, $m, $d) = explode('-', $this->timeinfo['datestart']);
			$date = mktime(0, 0, 0, $m, $d, $y);
			$this->timeinfo['units'] = $units;
			$type = $this->timeinfo['typeofwork'];
			$tow = sbgetTypeOfWork($req['channel_dname'], $type);
			if ($tow) {
				// we have all we need
				$cn = explode('-', $req['channel_dname']);
				$brand = $cn[0];
				$prjtype = $cn[1];
				if (Timecontrol::userTotalTime(date('Y-m-d', $date), $current_user->id)>8*60) {
					$this->time_status = self::STATUS_TIMEOVER8TOTAL;
					return true;
				}
				$pt = (empty($this->timeinfo['projecttask']) ? '' : $this->timeinfo['projecttask']);
				$pst = (empty($this->timeinfo['projectsubtask']) ? '' : $this->timeinfo['projectsubtask']);
				$result = stoptimerDoCreateTC($date, $time, $brand, $prjtype, $title, $type, $units, $req['team_dname'], $pt, $pst);
				$time_array = explode(':', $result['totaltime']);
				$this->timeinfo['time'] = (int)$time_array[0].'h '.$time_array[1].'m';
				$this->timeinfo['title'] = $title;
				$this->timeinfo['typeofwork'] = $type;
				$this->time_status = self::STATUS_TIMER_CLOSED;
				$tskstatus = $this->timeinfo['taskstatus'];
				if ($tskstatus!='') {
					if ($prjsubtsk) {
						$chgstatus = changePrjSubTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $this->timeinfo['projectsubtask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					} elseif ($prjtsk) {
						$chgstatus = changePrjTaskStatus($req['channel_dname'], $this->timeinfo['projecttask'], $tskstatus);
						if ($chgstatus) {
							//$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						} else {
							$this->time_status = self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED;
						}
					}
				}
				return true;
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
		$this->time_status = self::STATUS_BADFORMAT;
		return true;
	}

	private function processUnkownTextField($unknownfield, $chname, $prjtsk, $prjsubtsk) {
		$tow = sbgetTypeOfWork($chname, $unknownfield);
		if ($tow) {
			$this->timeinfo['typeofwork'] = $unknownfield;
			$this->timeinfo['taskstatus'] = '';
			if ($prjtsk && !$prjsubtsk) {
				$this->time_status = self::STATUS_MISSINGPRJTASK;
				return true;
			}
		} else {
			$workwith = $unknownfield;
			if ($prjsubtsk) {
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > ask for type of request
					$this->time_status = self::STATUS_MISSINGTYPE;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk && !$prjsubtsk) {
				$sttsk = sbgetPrjTaskStatus($workwith);
				if (!$sttsk) {
					if (sbProjectTaskExist($chname, $workwith)) {
						// we have task > ask for type of request
						$this->time_status = self::STATUS_MISSINGTYPE;
						$this->timeinfo['projecttask'] = $workwith;
						return true;
					}
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				} else {
					// we have status > ask for project task
					$this->time_status = self::STATUS_MISSINGPRJTASK;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				}
			} else {
				$this->time_status = self::STATUS_TYPE_NOTFOUND;
				return true;
			}
		}
	}

	private function process2UnkownTextField($unknownfield1, $unknownfield2, $chname, $prjtsk, $prjsubtsk) {
		$tow = sbgetTypeOfWork($chname, $unknownfield1);
		if ($tow) {
			$this->timeinfo['typeofwork'] = $unknownfield1;
			$workwith = $unknownfield2;
			if ($prjsubtsk) {
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk) {
				$sttsk = sbgetPrjTaskStatus($workwith);
				if ($sttsk) {
					// we have status > ask for project task
					$this->time_status = self::STATUS_MISSINGPRJTASK;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					if (sbProjectTaskExist($chname, $workwith)) {
						// we have task > we have all we need
						$this->time_status = self::NOOP;
						$this->timeinfo['projecttask'] = $workwith;
						return true;
					}
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			}
			$this->timeinfo['taskstatus'] = '';
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		} else {
			$workwith = $unknownfield1;
			if ($prjsubtsk) {
				$this->timeinfo['typeofwork'] = $unknownfield2;
				$sttsk = sbgetPrjSubTaskStatus($workwith);
				if ($sttsk) {
					// we have status > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['taskstatus'] = $workwith;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJSUBTASKSTATUS_NOTFOUND;
					return true;
				}
			} elseif ($prjtsk) {
				if (sbgetPrjTaskStatus($workwith)) {
					$this->timeinfo['taskstatus'] = $unknownfield1;
					$workwith = $unknownfield2;
				} else {
					if (sbgetPrjTaskStatus($unknownfield2)) {
						$this->timeinfo['taskstatus'] = $unknownfield2;
					} elseif (sbgetTypeOfWork($chname, $unknownfield2)) {
						$this->timeinfo['typeofwork'] = $unknownfield2;
					} else {
						$this->time_status = self::STATUS_BADFORMAT;
						return true;
					}
					$workwith = $unknownfield1;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					$this->timeinfo['projecttask'] = $workwith;
					if (!empty($this->timeinfo['typeofwork'])) {
						// we have all we need
						$this->time_status = self::NOOP;
						return true;
					}
					// we have task > ask for type of request
					$this->time_status = self::STATUS_MISSINGTYPE;
					return true;
				}
				if (sbgetTypeOfWork($chname, $workwith)) {
					$this->timeinfo['typeofwork'] = $workwith;
				}
				$this->time_status = self::STATUS_MISSINGPRJTASK;
				return true;
			} else {
				$this->time_status = self::STATUS_BADFORMAT;
				return true;
			}
		}
	}

	private function process3UnkownTextField($unknownfield1, $unknownfield2, $unknownfield3, $chname, $prjtsk, $prjsubtsk) {
		if (sbgetTypeOfWork($chname, $unknownfield1)) {
			$this->timeinfo['typeofwork'] = $unknownfield1;
			$workwith = $unknownfield2;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield3)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield3;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield3)) {
					$this->timeinfo['taskstatus'] = $unknownfield3;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} elseif (sbgetTypeOfWork($chname, $unknownfield2)) {
			$this->timeinfo['typeofwork'] = $unknownfield2;
			$workwith = $unknownfield1;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield3)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield3;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield3)) {
					$this->timeinfo['taskstatus'] = $unknownfield3;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} elseif (sbgetTypeOfWork($chname, $unknownfield3)) {
			$this->timeinfo['typeofwork'] = $unknownfield3;
			$workwith = $unknownfield1;
			$sttsk = sbgetPrjTaskStatus($workwith);
			if ($sttsk) {
				$this->timeinfo['taskstatus'] = $workwith;
				if (sbProjectTaskExist($chname, $unknownfield2)) {
					// we have status > we have all we need
					$this->timeinfo['projecttask'] = $unknownfield2;
					$this->time_status = self::NOOP;
					return true;
				} else {
					$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
					return true;
				}
			} else {
				if (sbgetPrjTaskStatus($unknownfield2)) {
					$this->timeinfo['taskstatus'] = $unknownfield2;
				} else {
					$this->time_status = self::STATUS_PRJTASKSTATUS_NOTFOUND;
					return true;
				}
				if (sbProjectTaskExist($chname, $workwith)) {
					// we have task > we have all we need
					$this->time_status = self::NOOP;
					$this->timeinfo['projecttask'] = $workwith;
					return true;
				}
				$this->time_status = self::STATUS_PRJTASK_NOTFOUND;
			}
			return true;
		} else {
			$this->time_status = self::STATUS_BADFORMAT;
			return true;
		}
	}

	private function processTimeField($timevalue) {
		if (preg_match('/^[0-9:]+$/', $timevalue)) {
			if (!preg_match('/\d\d:\d\d/', $timevalue)) {
				if (strpos($timevalue, ':')!==false) {
					list($h, $m) = explode(':', $timevalue);
					$h = substr('00'.$h, -2);
					$m = substr('00'.$m, -2);
				} else {
					$h = (int)($timevalue / 60);
					$m = ($timevalue % 60);
					$h = substr('00'.$h, -2);
					$m = substr('00'.$m, -2);
				}
				$timevalue = $h.':'.$m;
			}
		}
		return $timevalue;
	}

	private function processDateField($datevalue) {
		global $current_user;
		if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $datevalue)) {
			list($y, $m, $d) = explode('-', $datevalue);
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/^\d\d\-\d\d\-\d\d\d\d$/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m, $y) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d, $y) = explode('-', $datevalue);
					break;
			}
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/^\d\d\-\d\d\-\d\d$/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m, $y) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d, $y) = explode('-', $datevalue);
					break;
				case 'yyyy-mm-dd':
					list($y, $m, $d) = explode('-', $datevalue);
					break;
			}
			$y = '20'.$y;
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} elseif (preg_match('/^\d\d\-\d\d$/', $datevalue)) {
			switch ($current_user->date_format) {
				case 'dd-mm-yyyy':
					list($d, $m) = explode('-', $datevalue);
					break;
				case 'mm-dd-yyyy':
					list($m, $d) = explode('-', $datevalue);
					break;
				case 'yyyy-mm-dd':
					list($m, $d) = explode('-', $datevalue);
					break;
			}
			$y = date('Y');
			if (!checkdate($m, $d, $y)) {
				$this->time_status = self::STATUS_DATEFORMAT;
				return true;
			}
		} else {
			$this->time_status = self::STATUS_DATEFORMAT;
			return true;
		}
		// ask for type
		$this->time_status = self::STATUS_MISSINGTYPE;
		$this->timeinfo['datestart'] = $y.'-'.$m.'-'.$d;
		$this->timeinfo['units'] = 1;
		return true;
	}

	public function getResponse() {
		global $site_URL;
		$helpcommand = substr($this->getHelp(), 3);
		if ($this->time_status == self::STATUS_BADFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('IncorrectFormat', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeFormat', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEOVER8) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeOver8', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMEOVER8TOTAL) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadTimeOver8Total', 'chatwithme'),
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_DATEFORMAT) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('red'),
					'text' => getTranslatedString('BadDateFormat', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_PRJTASK_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('PrjTaskNotFound', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_PRJSUBTASK_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('PrjSubTaskNotFound', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_PRJTASKSTATUS_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('PrjTaskStatusNotFound', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_PRJSUBTASKSTATUS_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('PrjSubTaskStatusNotFound', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TYPE_NOTFOUND) {
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'text' => getTranslatedString('WorkTypeNotFound', 'chatwithme')."\n".$helpcommand,
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_MISSINGTYPE) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			coreBOS_Settings::setSetting($tcinfoid, json_encode($this->timeinfo));
			$chnlsep = '::';
			$chid = (isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : (isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : ''));
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = $site_URL.'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			$plvals = sbgetAllTypeOfWork($req['channel_dname']);
			asort($plvals);
			foreach ($plvals as $plid => $value) {
				$action_data = array(
					'name' => textlength_check(decode_html($value)),
					'integration' => array(
						'url' => $baseurl.'&wt='.$plid.'&pl=t',
					));
				array_push($fieldsArray, $action_data);
			}
			$msglen = 200+strlen(json_encode($fieldsArray));
			if ($msglen>6500) {
				$fieldsArray = array();
				foreach ($plvals as $plid => $value) {
					$opdata = array(
						'text' => textlength_check(decode_html($value)),
						'value' => (string)$plid,
					);
					$fieldsArray[] =$opdata;
				}
				$fieldsArray = array(array(
					'name' => getTranslatedString('SelectTOW', 'chatwithme'),
					'integration' => array(
						'url' => $baseurl.'&pl=t',
					),
					'type' => 'select',
					'options' => $fieldsArray
				));
			}
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedTOW', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_MISSINGPRJTASK) {
			$fieldsArray = array();
			$req = getMMRequest();
			$cn = explode('-', $req['channel_dname']);
			$brand = $cn[0];
			$prjtype = $cn[1];
			$tcinfoid = uniqid('CTC');
			coreBOS_Settings::setSetting($tcinfoid, json_encode($this->timeinfo));
			$chnlsep = '::';
			$chid = (isset($_REQUEST['channel_id']) ? vtlib_purify($_REQUEST['channel_id']) : (isset($_REQUEST['chnl_id']) ? vtlib_purify($_REQUEST['chnl_id']) : ''));
			$chnlinfo = (isset($_REQUEST['chnl_name']) ? vtlib_purify($_REQUEST['chnl_name']) : '').$chnlsep
				.(isset($_REQUEST['chnl_dname']) ? vtlib_purify($_REQUEST['chnl_dname']) : '').$chnlsep.$chid;
			coreBOS_Settings::setSetting('CWMCHINFO'.$chid, $chnlinfo);
			$baseurl = $site_URL.'/notifications.php?type=CWM&text=sbsavetime&token='.$req['token'].'&tc='.$tcinfoid.'&channel_id='.$chid;
			$plvals = sbgetAllProjectTasks($req['channel_dname']);
			asort($plvals);
			foreach ($plvals as $plid => $value) {
				$action_data = array(
					'name' => textlength_check(decode_html($value)),
					'integration' => array(
						'url' => $baseurl.'&pt='.$plid.'&pl=p',
					));
				array_push($fieldsArray, $action_data);
			}
			$msglen = 200+strlen(json_encode($fieldsArray));
			if ($msglen>6500) {
				$fieldsArray = array();
				foreach ($plvals as $plid => $value) {
					$opdata = array(
						'text' => textlength_check(decode_html($value)),
						'value' => (string)$plid,
					);
					$fieldsArray[] =$opdata;
				}
				$fieldsArray = array(array(
					'name' => getTranslatedString('SelectPRJ', 'chatwithme'),
					'integration' => array(
						'url' => $baseurl.'&pl=p',
					),
					'type' => 'select',
					'options' => $fieldsArray
				));
			}
			$ret = array(
				'response_type' => 'in_channel',
				'attachments' => array(array(
					'color' => getMMMsgColor('yellow'),
					'title' => getTranslatedString('TimerStoped1', 'chatwithme').getTranslatedString('TimerStopedPRT', 'chatwithme'),
					'actions' => $fieldsArray
				)),
			);
			return $ret;
		} elseif ($this->time_status == self::STATUS_TIMER_CLOSED || $this->time_status == self::STATUS_TIMER_CLOSED_STATUSNOTCHANGED) {
			$prjtsk = GlobalVariable::getVariable('CWM_TC_ProjectTask', 0);
			$prjsubtsk = GlobalVariable::getVariable('CWM_TC_ProjectSubTask', 0);
			if ($prjsubtsk && !$prjtsk) {
				$prjtsk = 1;
			}
			$ret = array(
				'response_type' => 'in_channel',
				'text' => getTranslatedString('UpdateFeedback1', 'chatwithme').$this->timeinfo['time'].' '
					.getTranslatedString('UpdateFeedback2', 'chatwithme').' "'.$this->timeinfo['title'].'"'
					.($prjtsk ? getTranslatedString('UpdateFeedback4', 'chatwithme').' "'.$this->timeinfo['projecttask'].'"' : '')
					.getTranslatedString('UpdateFeedback3', 'chatwithme').' "'.$this->timeinfo['typeofwork'].'"',
			);
			return $ret;
		}
	}
}
?>
