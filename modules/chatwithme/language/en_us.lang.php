<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

$mod_strings = array(
	'chatwithme' => 'Chat with me',
	'SINGLE_chatwithme' => 'Chat with me',
	'chatwithme ID' => 'Chat with me ID',
	'ErrProcessingAction' => 'Error processing action.',
	'IncorrectFormat' => 'Sorry, I could not understand the command',
	'Incorrect parameters' => 'Sorry, Incorrect parameters in method call.',
	'MMUserIDUpdated' => 'Mattermost user ID correctly updated',
	'MMUserIDError' => 'Mattermost user could not be found nor created!',
	'ThisIsHelp' => 'This is the syntax I understand:',
	'HelpTitle' => '**Hello!**, these are the commands you can use:',
	'record' => 'record',
	'deleted' => 'has been deleted',
	'notdeleted' => 'has NOT been deleted',
	'QuestionError' => 'Error processing the question.',
	'FoundSome' => 'I could not find the indicated element.',
	'OneOfThese' => 'Could it be one of these?',
	'ClickHereForFullResults' => 'Click Here for full answer',
	'_active' => 'Integration Active',
	'_cmdlang' => 'Command Language',
	'_username' => 'User name to post as',
	'_icon_url' => 'Icon URL to show on post',
	'_posturl' => 'Mattermost URL (no trailing slash)',
	'_tokens' => 'Comma separated list of valid tokens',
	'_mmuserpasswd' => 'Default mattermost user password (used when syncing users)',
	'LBL_MODULE_NOT_FOUND' => 'I could not find the indicated module',
	'LBL_FIELD_NOT_FOUND' => 'I could not find the indicated field',
	'LBL_NOSEARCHRESULTS' => 'I could not find any information with that search term',
	'LBL_SEARCHRESULTS' => 'Search results:',
	'MMInvalidUser' => 'Invalid mattermost User: mattermost user ID cannot be found.',
	'OkWillDo' => 'Ok, will do :-)',
	'Reminder Postponed' => 'Reminder Postponed. Get back to you soon.',
	'Reminder Discarded' => 'Reminder Discarded. I will not bother you about this again.',
	'Reminder Command Not Recognized' => '**ERROR*: Reminder command not recognized',
	'Color' => 'Color',
	'Green' => 'Green',
	'Blue' => 'Blue',
	'Yellow' => 'Yellow',
	'Red' => 'Red',
	'Msg is ephemeral' => 'Message is ephemeral',
	'Title' => 'Title',
	'Body' => 'Message',
	'Buttons' => 'Buttons',
	'optional' => 'optional',
	'First Button Title' => 'First Button Title',
	'First Button Parameters' => 'First Button Parameters',
	'Second Button Title' => 'Second Button Title',
	'Second Button Parameters' => 'Second Button Parameters',
	'Third Button Title' => 'Third Button Title',
	'Third Button Parameters' => 'Third Button Parameters',
	'Postpone' => 'Postpone',
	'Discard' => 'Discard',
	'StartedNewTimer' => 'Okay, I started New Timer :stopwatch:',
	'ThereIsOpenTimer' => ':warning: There is another open timer, stop it first',
	'NoOpenTimer' => ':warning: Sorry, There is no started timer',
	'ProjectNotFound' => ':warning: Sorry, could not find that project',
	'NoTimeDescription' => 'I need you to give me the task description, try again, please',
	'WorkTypeNotFound' => 'I could not find the given type of work **or** you are on the wrong channel, please try again.',
	'PrjTaskNotFound' => 'I could not find the given project task **or** you are on the wrong channel, please try again.',
	'PrjTaskStatusNotFound' => 'I could not find the given project task status **or** you are on the wrong channel, please try again.',
	'PrjSubTaskNotFound' => 'I could not find the given project subtask **or** you are on the wrong channel, please try again.',
	'PrjSubTaskStatusNotFound' => 'I could not find the given project subtask status **or** you are on the wrong channel, please try again.',
	'BadTimeFormat' => 'I could not understand the time you gave me, please try again',
	'BadDateFormat' => 'I could not understand the date you gave me, please try again',
	'BadTimeOver8' => 'We cannot register time records longer than 8 hours, please create more than one dividing the time correctly.',
	'BadTimeOver8Total' => 'You have more than 8 hours assigned to this date, please assign the time to another date.',
	'TimerStoped1' =>'Timer stopped',
	'TimerStopedTOW' =>', I just need a few details for this time entry, which type of work did you do?',
	'TimerStopedPRT' =>', I just need a few details for this time entry, what project task did you work on?',
	'ProjectAdded1' =>'Ok, I selected ',
	'ProjectAdded2' =>'as the project, **What did you do?** start with _**#taskfortime** {task title}_',
	'ProjectAdded3' =>'as the project, **What did you do?** start with _**#logtask** {task title}_',
	'SelectTOW' => 'Select a type of work',
	'SelectPRJ' => 'Select a project task',
	'UpdateFeedback1' => ':tada: Timecontrol has been recorded successfully with these details: ',
	'For' => 'for',
	'UpdateFeedback2' => ', ticket:',
	'UpdateFeedback3' => ', type of work:',
	'UpdateFeedback4' => ', task:',
	'ShowAll' => 'Show All',
	'CallError' => ':x: Invalid Call',
	'AddProject' => 'What project did you work on? I am fetching your most recent projects.' ,
	'AddLogTime' => ', **How many hours did you spend on this task?**. format _#timespent {hours minutes}_ You can type time in form of  \'3.3\', or  \'3.3\', or \'3hrs 30 mins\' ',
	'Pname' => 'PROJECT NAME',
	'TypeProject' => 'Type the Project you worked on, syntax _```#taskforproject``` ``{ID}``_',
	'RecordUpdated' => ':tada: Record updated successfully!',
	'RecordNotFound' => 'Specified record is not found',
	'NewRecordAdded' => 'New record added successfully!',
	'NewRecordNotAdded' => 'New record add failed!',
	'CommentAdded' => 'Comment added successfully!',
	'CommentNotAdded' => 'Comment add failed!',
	'UserNotFound' => 'This user not found here',
	'RecordNotUpdated' => 'Record update failed!',
	'InvalidField' => ':x: Invalid field name',
	'MMUserTEAMError' => 'Mattermost team (MM Team) is empty please fill it first',
	'JoinMeeting' => 'Join the meeting',
	'RoomCreated' => 'Meeting room created.',
	// commands
	'delete_command' => '**delete {record} [yes]**: Delete a record',
	'show_command' => '**show {question}**: Launch a question and see the results',
	'find_command1' => '**find [{module}] {term}**: search the given term globally or in the given module',
	'find_command2' => '**find module {field}{condition}{value}**: search the {field} on the module for the {value}, where condition can be =|>|>=|<|<=|%',
	'see_command' => '**see {record}**: See the details of a record',
	'remindme_command1' => '**remindme {about this} [at] $1 h:m**: will send a message more or less **AT** the indicated time',
	'remindme_command2' => '**remindme {about this} [in] Dd Hh Mm**: will send a message more or less **IN** D days, H hours, and M minutes',
	'starttimer_command' => '**starttimer** : will start a new timer if there is no open timer',
	'stoptimer_command' => '**stoptimer** "task description" {units} {"type"}: will stop the last open timer if it exists',
	'stoptimer_commandTsk' => '**stoptimer** "task description" {projecttask} {units} {"type"} {"new status"}: will stop the last open timer if it exists',
	'stoptimer_commandTskSubTsk' => '**stoptimer** "task description" projecttask projectsubtask {units} {"type"} {"new status"}: will stop the last open timer if it exists',
	'logtime_command' => 'will create a new timecontrol entry record, and ask for more details',
	'logtask_command' => '**logtask {task title}** : Insert title of the timecontrol record',
	'timespent_command' => '**timespent {hours minutes}** : example 3.3 or 3:3 or 3hrs 30mins',
	'taskforproject_command' => '**taskforproject** {ID} insert project to the timecontrol records',
	'update_command' => '**update {crmid} {fieldname}={value}**: Update field value of a certain record',
	'sbcreatetime_command' => '**time hh:mm "task description" {units} {yyyy-mm-dd} {"type"}**: will create a new time record',
	'sbcreatetime_commandTsk' => '**time hh:mm "task description" {projecttask} {units} {yyyy-mm-dd} {"type"} {"new status"}**: will create a new time record related to a project task and a type of work, optionally changing the status',
	'sbcreatetime_commandTskSubTsk' => '**time hh:mm "task description" projecttask projectsubtask {units} {yyyy-mm-dd} {"type"} {"new status"}**: will create a new time record related to a project subtask and a type of work, optionally changing the status',
	'video_command' => '**video {name}**: will share a link to a video chat room. If no name is given the channel name will be used',
	'note_command' => '**note {user/who} [1-5] "text/comment"**: It will create a new record in Survey Done module that will take the user selected and the note written in the command',
	'comment_command' => '**comment {record} "text/comment"**: It will update record by add a comment for that module',
);
?>
