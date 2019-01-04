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

class cbmmActionremindmeaction extends chatactionclass {

    public function addDefault() {
        return false;
    }

    public function getResponse() {
        global $current_user, $log, $adb;
        $ret = "";
        if(isset($_REQUEST['record']) && isset($_REQUEST['event'])) {
            $record = vtlib_purify($_REQUEST['record']);
            $event = vtlib_purify($_REQUEST['event']);
            if ($event == 'postpone') {
                $resultfromdb = $adb->pquery("select time_start, dtstart from vtiger_activity where activityid =$record ", array());
                $updatedstarttime = date('H:i', strtotime("+10 minutes", strtotime($resultfromdb ->fields['time_start'])));
                $updatedtstart = date('Y-m-d H:i', strtotime("+10 minutes", strtotime($resultfromdb ->fields['dtstart'])));
                $sql = "update vtiger_activity set  time_start = '$updatedstarttime', dtstart = '$updatedtstart'  where activityid =$record";
                $result = $adb->pquery($sql, array());
                if($result) {
                    $ret = array(
                    'update' => array(
                    'color' => getMMMsgColor('blue'),
                    'message' => 'Reminder Postponed',
                    ),
                    );
                }
            }
            elseif ($event == 'discard') {
                $result = $adb->pquery("update vtiger_activity set status = 'completed' where activityid =$record", array());
                if($result) {
                    $ret = array(
                    'update' => array(
                    'color' => getMMMsgColor('yellow'),
                    'message' => 'Reminder Discarded',
                    ),
                    );
                }    
            }
            return $ret;
        }
    }
}
?>