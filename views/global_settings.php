<?php

/**
* Remote Boot Global Settings View
*
* @category   apps
* @package    remote_boot
* @subpackage Views
* @author     PoliLinux <clearos@polilinux.com.br>
* @author     Felipe Stall Rechia <felipe.rechia@polilinux.com.br>
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

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('remote_boot');

///////////////////////////////////////////////////////////////////////////////
// Load messages about events into the infobox
///////////////////////////////////////////////////////////////////////////////

/*
* format expected for errors, warnings and info messages
*
* error/info/warn = array ('title' => 'main error/info/warn description',
*                          'messages' => array( 'head1' => 'description1',
*                                               'head2' => 'description2', ...
*                               )
*               )
*
*
*/

function display_messages_queue($msgs)
{
    $error_text       = '';
    $errorsbox        = '';
    $warning_text     = '';
    $warningsbox      = '';
    $info_text        = '';
    $infobox          = '';

    if(isset($msgs['messages']['error'])) {
        foreach ($msgs['messages']['error'] as $key => $msg) {
            $error_text .= '<b>'.$msg['head'].'</b>: '.$msg['text'].'<br />';
        }
    }
    if(isset($msgs['messages']['warning'])) {
        foreach ($msgs['messages']['warning'] as $key => $msg) {
            $warn_text .= '<b>'.$msg['head'].'</b>: '.$msg['text'].'<br />';
        }
    }
    if(isset($msgs['messages']['info'])) {
        foreach ($msgs['messages']['info'] as $key => $msg) {
            $info_text .= '<b>'.$msg['head'].'</b>: '.$msg['text'].'<br />';
        }
    }
    if(isset($msgs['messages']['success'])) {
        foreach ($msgs['messages']['success'] as $key => $msg) {
            $success_text .= '<b>'.$msg['head'].'</b>: '.$msg['text'].'<br />';
        }
    }
    if(!empty($error_text))
        echo infobox_critical($msgs['title'], $error_text);
    if(!empty($warn_text))
        echo infobox_warning($msgs['title'], $warn_text);
    if(!empty($info_text))
        echo infobox_info($msgs['title'], $info_text);
    if(!empty($success_text))
        echo infobox_highlight($msgs['title'], $success_text);

}

function label_with_help($label){
    $h = array('<div data-toggle="tooltip" data-original-title="','">','</div>');
    return $h[0].lang($label.'_help').$h[1].lang($label).$h[2];
}


///////////////////////////////////////////////////////////////////////////////
// global settings form handler
///////////////////////////////////////////////////////////////////////////////
if ($form_type === 'edit') {
    $form_path = 'remote_boot/global_settings/edit';
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/remote_boot')
    );
} else {
    $form_path = 'remote_boot/global_settings';
    $read_only = TRUE;
    $buttons = array(anchor_edit('/app/remote_boot/global_settings/edit'));
}
$toggle_dropdown = array(0 => lang('base_disabled'), 1 => lang('base_enabled'));


$profile_messages = $this->session->userdata('rb_profile_msgs');
$internal_messages = $this->session->userdata('rb_internal_msgs');

$this->session->unset_userdata('rb_profile_msgs');
$this->session->unset_userdata('rb_internal_msgs');

display_messages_queue($internal_messages);
display_messages_queue($profile_messages);

$attributes = array('id' => 'remote_boot_global_settings');
echo form_open($form_path,$attributes);
echo form_header(lang('text_global_settings'));

if (!clearos_console()) {
    // tftp
    echo field_dropdown('global[tftpserver][automanage]', $toggle_dropdown, $tftpserver['automanage'],
                        label_with_help('field_global_tftp_automanage'), $read_only);
    echo field_input('global[tftpserver][ip]', $tftpserver['ip'], label_with_help('field_global_tftp_ip'), $read_only);
    echo field_input('global[folders][tftp][name]', $folders['tftp']['name'],
                        label_with_help('field_global_tftp_rootfolder'), $read_only);
    echo field_input('global[tftpserver][cfgfile]', $tftpserver['cfgfile'],
                        label_with_help('field_global_tftp_cfgfile'), $read_only);
    // nfs exports
    echo field_dropdown('global[nfsserver][automanage]', $toggle_dropdown, $nfsserver['automanage'],
                        label_with_help('field_global_nfs_automanage'), $read_only);
    echo field_input('global[nfsserver][ip]', $nfsserver['ip'], label_with_help('field_global_nfs_ip'), $read_only);
    echo field_input('global[folders][nfs][name]', $folders['nfs']['name'],
                        label_with_help('field_global_nfs_rootfolder'), $read_only);
    // pxelinux
    echo field_input('global[folders][pxelinux][name]', $folders['pxelinux']['name'],
                        label_with_help('field_global_pxelinux_rootfolder'), $read_only);
}

echo field_button_set($buttons);

echo form_footer();
echo form_close();
