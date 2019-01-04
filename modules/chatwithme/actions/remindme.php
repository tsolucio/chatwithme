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
require 'include/Webservices/Create.php';

class cbmmActionRemindme extends chatactionclass {
    public $time_start, $description, $time_end, $start_date, $dtstart, $dtend;
    public $activitytype = "MMRemindMe"; 
    public function getHelp() {
        return ' - '.getTranslatedString('remindme_command', 'chatwithme');
    }

    public function process() {
        global $log,$adb,$current_user; 
        $req = getMMRequest();
        $prm = parseMMMsg($req['text']);
        if (in_array('[at]', $prm)) {
            $desc = array_slice($prm, 1, -3);
            $description = implode(" ", $desc);
            $subject = substr($description, 0, 40);
            $position = array_keys($prm, '[at]');
            $rectime_start = $prm[$position[0] + 1].' '.$prm[$position[0] + 2];
            $date_number = strtr($rectime_start, '/', '-');
            $time_start = date('H:i', strtotime($date_number));
            $date_start = date('Y-m-d', strtotime($date_number));
            $dtstart = $rectime_start;
            $dtend =date('Y-m-d H:i', strtotime("+1 minutes", strtotime($date_number))); 
            $time_end = date('H:i', strtotime("+1 minutes", strtotime($rectime_start)));
        }
        if (in_array('[in]', $prm)) {
            $desc = array_slice($prm, 1, -2);
            $description = implode(" ", $desc);
            $subject = substr($description, 0, 4);
            $position = array_keys($prm, '[in]');
            $time_startAdd = str_replace('m', '', $prm[$position[0] + 1]);
            $temprec = str_replace('m', '', $prm[$position[0] + 1]);
            $inc = $temprec+1;
            $current_time = date('Y-m-d H:i');
            $date_number = strtr($current_time, '/', '-');
            $time_start = date('H:i', strtotime("+".$time_startAdd."minutes", strtotime($date_number)));
            $dtstart = $current_time;
            $date_start = date('Y-M-d', strtotime($date_number));
            $time_end = date('H:i:s', strtotime("+".$inc."minutes", strtotime($current_time)));
            $dtend = date('Y-m-d H:i', strtotime("+".$inc."minutes", strtotime($current_time)));
        }
        $data = array(
        'assigned_user_id' => vtws_getEntityId('Users').'x'.$current_user->id,
        'subject' => $subject, 
        'activitytype' =>$this->activitytype, 
        'time_start' => $time_start, 
        'date_start' => $date_start, 
        'time_end' =>$time_end,
        'dtstart' => $dtstart,
        'dtend' => $dtend
        );
        $log ->fatal($data);
        vtws_create('cbCalendar', $data, $current_user);
        return true;
    }
}
?>