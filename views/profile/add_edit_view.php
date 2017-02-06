<?php

/**
* Remote Boot Profile Settings View
*
* @category   apps
* @package    remote_boot
* @subpackage Views
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
// load dependencies
////////////////////////////////////////////////////////////////////////

$this->lang->load('remote_boot');

////////////////////////////////////////////////////////////////////////
// some structures to help create the form with help tooltips and
// auto hide parts of the form
////////////////////////////////////////////////////////////////////////

$bttl = array('<p><h3 class="box-title" title="">','</h3></p>');
function label_with_help($label){
    $h = array('<div data-toggle="tooltip" data-original-title="','">','</div>');
    return $h[0].lang($label.'_help').$h[1].lang($label).$h[2];
}

////////////////////////////////////////////////////////////////////////
// form
////////////////////////////////////////////////////////////////////////
if ($form_type === 'edit') {
    $form_path = '/remote_boot/profile/edit/'.$profile['@attributes']['id'];
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/remote_boot')
    );
} elseif ($form_type === 'add') {
    $form_path = '/remote_boot/profile/add';
    $read_only = FALSE;
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/remote_boot')
    );
} elseif ($form_type === 'view') {
    $form_path = '/remote_boot/profile/view/'.$profile['@attributes']['id'];
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/remote_boot/profile/edit/'.$profile['@attributes']['id']),
        anchor_custom('/app/remote_boot',lang('base_back'), 'low')
    );
}
$toggle_dropdown = array(0 => lang('base_disabled'), 1 => lang('base_enabled'));
$YN_toggle_dropdown = array(0 => lang('base_no'), 1 => lang('base_yes'));
$export_dropdown_options['no-field'] = 1;
$export_dropdown_options['no_label'] = 1;
$export_folder_options['no_label'] = 1;
// Javascript controlled read only.
$js_read_only = FALSE;

$attributes = array('id' => 'remote_boot_profile_settings');
echo form_open($form_path,$attributes);
echo form_header(lang('text_profile_settings'));

echo field_dropdown('profile[enabled]', $toggle_dropdown, $profile['enabled'],
                    label_with_help('field_profile_enable'),$read_only);
echo field_input('profile[name]',$profile['name'],label_with_help('field_profile_name'),$read_only);
echo field_input('profile[nfs][ip]',$profile['nfs']['ip'],label_with_help('field_profile_nfs_ip'),$read_only);
echo field_input('profile[network_cidr]',$profile['network_cidr'],label_with_help('field_profile_network'),$read_only);

echo form_fieldset(' ');
echo form_fieldset_close();

$nfs_settings_grid = array();
$nfs_settings_grid[0][0] = form_label(label_with_help('field_export_folders'));
$nfs_settings_grid[1][0] = field_textarea('profile[folders][rootfs][name]',$profile['folders']['rootfs']['name'],'',
                            $js_read_only,$export_folder_options);
$nfs_settings_grid[2][0] = field_textarea('profile[folders][home][name]',$profile['folders']['home']['name'],'',
                            $js_read_only,$export_folder_options);
$nfs_settings_grid[3][0] = field_textarea('profile[folders][etc][name]',$profile['folders']['etc']['name'],'',
                            $js_read_only,$export_folder_options);
$nfs_settings_grid[4][0] = field_textarea('profile[folders][var][name]',$profile['folders']['var']['name'],'',
                            $js_read_only,$export_folder_options);
$nfs_settings_grid[5][0] = field_textarea('profile[folders][root][name]',$profile['folders']['root']['name'],'',
                            $js_read_only,$export_folder_options);

$nfs_settings_grid[0][1] = form_label(label_with_help('field_export_options'));
$nfs_settings_grid[1][1] = field_textarea('profile[folders][rootfs][export][options]',
                            $profile['folders']['rootfs']['export']['options'],'',$read_only,$export_folder_options);
$nfs_settings_grid[2][1] = field_textarea('profile[folders][home][export][options]',
                            $profile['folders']['home']['export']['options'],'',$read_only,$export_folder_options);
$nfs_settings_grid[3][1] = field_textarea('profile[folders][etc][export][options]',
                            $profile['folders']['etc']['export']['options'],'',$read_only,$export_folder_options);
$nfs_settings_grid[4][1] = field_textarea('profile[folders][var][export][options]',
                            $profile['folders']['var']['export']['options'],'',$read_only,$export_folder_options);
$nfs_settings_grid[5][1] = field_textarea('profile[folders][root][export][options]',
                            $profile['folders']['root']['export']['options'],'',$read_only,$export_folder_options);

$nfs_settings_grid[0][2] = form_label(label_with_help('field_export_checkbox'));
$nfs_settings_grid[1][2] = field_dropdown('profile[folders][rootfs][export][enabled]', $YN_toggle_dropdown,
                            $profile['folders']['rootfs']['export']['enabled'],'',$read_only,$export_dropdown_options);
$nfs_settings_grid[2][2] = field_dropdown('profile[folders][home][export][enabled]', $YN_toggle_dropdown,
                            $profile['folders']['home']['export']['enabled'],'',$read_only,$export_dropdown_options);
$nfs_settings_grid[3][2] = field_dropdown('profile[folders][etc][export][enabled]', $YN_toggle_dropdown,
                            $profile['folders']['etc']['export']['enabled'],'',$read_only,$export_dropdown_options);
$nfs_settings_grid[4][2] = field_dropdown('profile[folders][var][export][enabled]', $YN_toggle_dropdown,
                            $profile['folders']['var']['export']['enabled'],'',$read_only,$export_dropdown_options);
$nfs_settings_grid[5][2] = field_dropdown('profile[folders][root][export][enabled]', $YN_toggle_dropdown,
                            $profile['folders']['root']['export']['enabled'],'',$read_only,$export_dropdown_options);

echo '<div id="nfs-cfg-title" class="show-hide-field">';
echo $bttl[0].lang('text_nfs_settings').$bttl[1];
echo '</div>';
echo '<div id="nfs-cfg-field">';
foreach ($nfs_settings_grid as $row => $line) {
    echo row_open();
    foreach ($line as $column => $value) {
        if ($column === 0)
        echo column_open(5);
        else if ($column === 1)
        echo column_open(5);
        else
        echo column_open(2);
        echo $value;
        echo column_close();
    }
    echo row_close();
}
echo form_fieldset(' ');
echo form_fieldset_close();
echo '</div>';

echo '<div id="pxe-cfg-title" class="show-hide-field">';
echo $bttl[0].lang('text_pxe_settings').$bttl[1];
echo '</div>';
echo '<div id="pxe-cfg-field">';
echo field_textarea('profile[pxelinux][kparams]',$profile['pxelinux']['kparams'],
                    label_with_help('field_profile_pxelinux_kparams'),$read_only);
echo field_input('profile[folders][kernel][name]',$profile['folders']['kernel']['name'],
                    label_with_help('field_profile_folder_kernel'),$js_read_only);
echo field_input('profile[pxelinux][cfgfile]',$profile['pxelinux']['cfgfile'],
                    label_with_help('field_profile_pxelinux_cfgfile'),$js_read_only);
echo field_input('profile[pxelinux][initrd]',$profile['pxelinux']['initrd'],
                    label_with_help('field_profile_pxelinux_initrd'),$js_read_only);
echo field_input('profile[pxelinux][kernel]',$profile['pxelinux']['kernel'],
                    label_with_help('field_profile_pxelinux_kernel'),$js_read_only);
echo form_fieldset(' ');
echo form_fieldset_close();
echo '</div>';

echo field_button_set($buttons);

echo form_footer();
echo form_close();
