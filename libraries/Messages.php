<?php

/**
* This is a class that manages queues for error, warning and informational
* messages. It also sends messages to ClearOS debug log if /tmp/webconfig.debug file exists
*
* @category   apps
* @package    remote_boot
* @subpackage Libraries
* @author     PoliLinux <clearos@polilinux.com.br>
* @author     Felipe Stall Rechia <felipe.rechia@polilinux.com.br>
* @author     Alexandre Calerio de Oliveira <alexandre@polilinux.com.br>
* @copyright  2016 PoliLinux Soluções e Serviços TI Ltda
* @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
*/

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
////////////////////////////////////////////////////////////////////////

namespace clearos\apps\remote_boot;

////////////////////////////////////////////////////////////////////////
// C L A S S
////////////////////////////////////////////////////////////////////////

class Messages
{
    ////////////////////////////////////////////////////////////////////
    // M E M B E R S
    ////////////////////////////////////////////////////////////////////
    protected $context_title = '';
    protected $message = array();
    protected $message_queue = array();
    protected $current_queue_size = 0;
    protected $max_queue_size;
    protected $max_extra_info_length;
    ////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ////////////////////////////////////////////////////////////////////

    /**
    * Remote Boot Messages constructor
    *
    * @param $max_queue_size integer
    * @param $max_extra_info_length integer
    *
    * @return void
    */

    public function __construct($max_queue_size = 20, $max_extra_info_length = 50)
    {
        clearos_profile(__METHOD__, __LINE__);
        $this->max_queue_size = $max_queue_size;
        $this->max_extra_info_length = $max_extra_info_length;

        // QUESTION: How to use a config or constants file to put these
        $this->message['successmsg']['type'] = 'success';
        $this->message['successmsg']['head'] = lang('msg_head_success');
        $this->message['successmsg']['text'] = lang('msg_text_success');

        $this->message['errormsg']['type'] = 'error';
        $this->message['errormsg']['head'] = lang('msg_head_error');
        $this->message['errormsg']['text'] = lang('msg_text_error');

        $this->message['warnmsg']['type'] = 'warning';
        $this->message['warnmsg']['head'] = lang('msg_head_warn');
        $this->message['warnmsg']['text'] = lang('msg_text_warn');

        $this->message['infomsg']['type'] = 'info';
        $this->message['infomsg']['head'] = lang('msg_head_info');
        $this->message['infomsg']['text'] = lang('msg_text_info');

        $this->message['message_id_does_not_exist']['type'] = 'warning';
        $this->message['message_id_does_not_exist']['head'] = lang('msg_head_message_id_does_not_exist');
        $this->message['message_id_does_not_exist']['text'] = lang('msg_text_message_id_does_not_exist');

        // global settings
        $this->message['global_settings_udpated']['type'] = 'success';
        $this->message['global_settings_udpated']['head'] = lang('msg_head_success');
        $this->message['global_settings_udpated']['text'] = lang('msg_text_global_settings_udpated');

        $this->message['global_settings_not_updated']['type'] = 'error';
        $this->message['global_settings_not_updated']['head'] = lang('msg_head_error');
        $this->message['global_settings_not_updated']['text'] = lang('msg_text_global_settings_not_updated');

        // profile creation
        $this->message['profile_added_to_config']['type'] = 'success';
        $this->message['profile_added_to_config']['head'] = lang('msg_head_success');
        $this->message['profile_added_to_config']['text'] = lang('msg_text_profile_write_to_cfg_success');

        $this->message['profile_folders_created']['type'] = 'success';
        $this->message['profile_folders_created']['head'] = lang('msg_head_success');
        $this->message['profile_folders_created']['text'] = lang('msg_text_profile_folders_created');

        $this->message['profile_exports_file_created']['type'] = 'success';
        $this->message['profile_exports_file_created']['head'] = lang('msg_head_success');
        $this->message['profile_exports_file_created']['text'] = lang('msg_text_profile_exports_file_created');

        $this->message['profile_pxelinux_cfg_file_created']['type'] = 'success';
        $this->message['profile_pxelinux_cfg_file_created']['head'] = lang('msg_head_success');
        $this->message['profile_pxelinux_cfg_file_created']['text'] = lang('msg_text_profile_pxelinux_cfg_file_created');

        $this->message['profile_exports_file_removed']['type'] = 'success';
        $this->message['profile_exports_file_removed']['head'] = lang('msg_head_success');
        $this->message['profile_exports_file_removed']['text'] = lang('msg_text_profile_exports_file_removed');

        // editing profile
        $this->message['profile_replaced_in_config']['type'] = 'success';
        $this->message['profile_replaced_in_config']['head'] = lang('msg_head_success');
        $this->message['profile_replaced_in_config']['text'] = lang('msg_text_profile_replaced_in_config');

        $this->message['profile_folders_moved']['type'] = 'success';
        $this->message['profile_folders_moved']['head'] = lang('msg_head_success');
        $this->message['profile_folders_moved']['text'] = lang('msg_text_profile_folders_moved');


        // deleting profile
        $this->message['profile_pxelinux_cfg_file_removed']['type'] = 'success';
        $this->message['profile_pxelinux_cfg_file_removed']['head'] = lang('msg_head_success');
        $this->message['profile_pxelinux_cfg_file_removed']['text'] = lang('msg_text_profile_pxelinux_cfg_file_removed');

        $this->message['profile_removed_from_config']['type'] = 'success';
        $this->message['profile_removed_from_config']['head'] = lang('msg_head_success');
        $this->message['profile_removed_from_config']['text'] = lang('msg_text_profile_removed_from_config');

        $this->message['profile_exports_reloaded']['type'] = 'success';
        $this->message['profile_exports_reloaded']['head'] = lang('msg_head_success');
        $this->message['profile_exports_reloaded']['text'] = lang('msg_text_profile_exports_reloaded');

        $this->message['file_created']['type'] = 'info';
        $this->message['file_created']['head'] = lang('msg_head_file_created');
        $this->message['file_created']['text'] = lang('msg_text_file_created');

        $this->message['file_not_created']['type'] = 'error';
        $this->message['file_not_created']['head'] = lang('msg_head_file_not_created');
        $this->message['file_not_created']['text'] = lang('msg_text_file_not_created');

        $this->message['folder_created']['type'] = 'info';
        $this->message['folder_created']['head'] = lang('msg_head_folder_created');
        $this->message['folder_created']['text'] = lang('msg_text_folder_created');

        $this->message['folder_not_created']['type'] = 'error';
        $this->message['folder_not_created']['head'] = lang('msg_head_folder_not_created');
        $this->message['folder_not_created']['text'] = lang('msg_text_folder_not_created');

        $this->message['file_deleted']['type'] = 'info';
        $this->message['file_deleted']['head'] = lang('msg_head_file_deleted');
        $this->message['file_deleted']['text'] = lang('msg_text_file_deleted');

        $this->message['folder_deleted']['type'] = 'info';
        $this->message['folder_deleted']['head'] = lang('msg_head_folder_deleted');
        $this->message['folder_deleted']['text'] = lang('msg_text_folder_deleted');

        $this->message['profile_all_folders_deleted']['type'] = 'success';
        $this->message['profile_all_folders_deleted']['head'] = lang('msg_head_success');
        $this->message['profile_all_folders_deleted']['text'] = lang('msg_text_profile_all_folders_deleted');

        $this->message['profile_not_all_folders_deleted']['type'] = 'warning';
        $this->message['profile_not_all_folders_deleted']['head'] = lang('msg_head_warn');
        $this->message['profile_not_all_folders_deleted']['text'] = lang('msg_text_profile_not_all_folders_deleted');

        // some errors and warnings
        $this->message['profile_network_overlap']['type'] = 'warning';
        $this->message['profile_network_overlap']['head'] = lang('msg_head_profile_network_overlap');
        $this->message['profile_network_overlap']['text'] = lang('msg_text_profile_network_overlap');

        $this->message['profile_failed_to_add_to_config']['type'] = 'error';
        $this->message['profile_failed_to_add_to_config']['head'] = lang('msg_head_profile_write_to_cfg');
        $this->message['profile_failed_to_add_to_config']['text'] = lang('msg_text_profile_write_to_cfg_failed');

        $this->message['file_not_found']['type'] = 'error';
        $this->message['file_not_found']['head'] = lang('msg_head_file_not_found');
        $this->message['file_not_found']['text'] = lang('msg_text_file_not_found');

        $this->message['folder_not_found']['type'] = 'error';
        $this->message['folder_not_found']['head'] = lang('msg_head_folder_not_found');
        $this->message['folder_not_found']['text'] = lang('msg_text_folder_not_found');

        $this->message['error_copying_file']['type'] = 'error';
        $this->message['error_copying_file']['head'] = lang('msg_head_error_copying_file');
        $this->message['error_copying_file']['text'] = lang('msg_text_error_copying_file');

        $this->message['profile_exports_file_not_created']['type'] = 'error';
        $this->message['profile_exports_file_not_created']['head'] = lang('msg_head_error');
        $this->message['profile_exports_file_not_created']['text'] = lang('msg_text_profile_exports_file_not_created');

        $this->message['profile_exports_file_not_removed']['type'] = 'error';
        $this->message['profile_exports_file_not_removed']['head'] = lang('msg_head_error');
        $this->message['profile_exports_file_not_removed']['text'] = lang('msg_text_profile_exports_file_not_removed');

        $this->message['profile_exports_not_reloaded']['type'] = 'error';
        $this->message['profile_exports_not_reloaded']['head'] = lang('msg_head_error');
        $this->message['profile_exports_not_reloaded']['text'] = lang('msg_text_profile_exports_not_reloaded');

        $this->message['profile_failed_to_replace_config']['type'] = 'error';
        $this->message['profile_failed_to_replace_config']['head'] = lang('msg_head_error');
        $this->message['profile_failed_to_replace_config']['text'] = lang('msg_text_profile_failed_to_replace_config');

        $this->message['profile_folders_not_created']['type'] = 'error';
        $this->message['profile_folders_not_created']['head'] = lang('msg_head_error');
        $this->message['profile_folders_not_created']['text'] = lang('msg_text_profile_folders_not_created');

        $this->message['profile_folders_not_moved']['type'] = 'error';
        $this->message['profile_folders_not_moved']['head'] = lang('msg_head_error');
        $this->message['profile_folders_not_moved']['text'] = lang('msg_text_profile_folders_not_moved');

        $this->message['profile_not_removed_from_config']['type'] = 'error';
        $this->message['profile_not_removed_from_config']['head'] = lang('msg_head_error');
        $this->message['profile_not_removed_from_config']['text'] = lang('msg_text_profile_not_removed_from_config');

        $this->message['profile_pxelinux_cfg_file_not_created']['type'] = 'error';
        $this->message['profile_pxelinux_cfg_file_not_created']['head'] = lang('msg_head_error');
        $this->message['profile_pxelinux_cfg_file_not_created']['text'] = lang('msg_text_profile_pxelinux_cfg_file_not_created');

        $this->message['profile_pxelinux_cfg_file_not_removed']['type'] = 'error';
        $this->message['profile_pxelinux_cfg_file_not_removed']['head'] = lang('msg_head_error');
        $this->message['profile_pxelinux_cfg_file_not_removed']['text'] = lang('msg_text_profile_pxelinux_cfg_file_not_removed');

        $this->message['profile_not_found']['type'] = 'error';
        $this->message['profile_not_found']['head'] = lang('msg_head_profile_not_found');
        $this->message['profile_not_found']['text'] = lang('msg_text_profile_not_found');

        $this->message['profile_name_already_exists']['type'] = 'error';
        $this->message['profile_name_already_exists']['head'] = lang('msg_head_invalid_profile_name');
        $this->message['profile_name_already_exists']['text'] = lang('msg_text_profile_name_already_exists');

        $this->message['profile_network_already_exists']['type'] = 'error';
        $this->message['profile_network_already_exists']['head'] = lang('msg_head_invalid_profile_network');
        $this->message['profile_network_already_exists']['text'] = lang('msg_text_profile_network_already_exists');
    }

    /**
    * sends messages to ClearOS log file with default level of debug
    *
    * @param  $method  use __METHOD__ to tell our log function which method called it
    * @param  $msg     the message to be written to the logfile
    * @param  $level   level of log message (e.g. 'info', 'error', or 'debug')
    *
    * @return void
    */

    public function log($method, $msg, $level = 'debug')
    {
        clearos_profile(__METHOD__, __LINE__);

        $message = $method.': '.$msg;
        log_message($level,$message);
    }

    /**
    * adds a message to the message queue (it is just an array).
    *
    * @param  $message_id  a string containing the ID of a message
    * @param  $extra_info  extra information to be appended to the standard message
    *
    * @return TRUE if message was successfully appended to the message queue, FALSE if not
    */
    public function add_msg_to_queue($message_id, $extra_info='' )
    {
        clearos_profile(__METHOD__, __LINE__);

        if (isset($this->message[$message_id])) {
            $msg = $this->message[$message_id];
        }
        else {
            $msg = $this->message['message_id_does_not_exist'];
            $extra_info = $message_id;
        }

        // truncate long messages
        if (strlen($extra_info) > $this->max_extra_info_length) {
            $extra_info = substr($extra_info,0,$this->max_extra_info_length)."...";
        }
        if (!empty($extra_info)) {
            $msg['text'] =   $msg['text'].' '.$extra_info;
        }

        // log messages to debug file (it is only logged if enabled):
        $log_this = $msg['head'].': '.$msg['text'];
        $this->log(__METHOD__,$log_this);

        if ($this->current_queue_size < $this->max_queue_size) {
            $this->message_queue['title'] = $this->context_title;
            $this->message_queue['messages'][$msg['type']][] = $msg;
            $this->current_queue_size++;
            return TRUE;
        }
        return FALSE;
    }

    /**
    * sets title for current set of messages
    *
    * @param  $title  a string with the title to be set
    *
    * @return void
    */

    public function set_queue_title($title)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->context_title = $title;

        return;
    }

    /**
    * retrieves the array with stored messages
    *
    * @return $this->message_queue array with messages
    */

    public function get_msg_queue()
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->message_queue;
    }

    /* ************************************************************** */
}
