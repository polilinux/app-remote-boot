<?php

/**
* Remote Boot Profile Settings View
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

////////////////////////////////////////////////////////////////////////
// Load dependencies
////////////////////////////////////////////////////////////////////////

$this->lang->load('remote_boot');


$profile_status = array(0 => lang('base_disabled'), 1 => lang('base_enabled'));


$title = lang('text_profile_summary');

$headers = array(
    lang('header_profile_name'),
    lang('header_profile_status'),
    lang('header_profile_network')
);
// anchors must refer to functions within the controller!
// e.g. /app/remote_boot/profile/view/0 should call Profile::view(0)

$items = array();

// we receive a list of profiles indexed by the real profile ID
foreach ($profile_list as $id => $profile) {

    // disable/enable profile button
    if ($profile['enabled'] == 1) {
        $disable_enable_button = anchor_custom('/app/remote_boot/profile/disable/'.$id,
        lang('profile_summary_disable'), 'low');
    } else {
        $disable_enable_button = anchor_custom('/app/remote_boot/profile/enable/'.$id,
        lang('profile_summary_enable'), 'high');
    }


    $button_list = array(
        anchor_view('/app/remote_boot/profile/view/'.$id),
        anchor_edit('/app/remote_boot/profile/edit/'.$id),
        $disable_enable_button,
        anchor_delete('/app/remote_boot/profile/delete/'.$id)
    );

    $item = array(
        'anchors' => button_set($button_list),
        'details' => array(
            'title' => $profile['name'],
            'status' => $profile_status[$profile['enabled']],
            'network' => $profile['network_cidr']
        )
    );
    $items[$id] = $item;
}

$anchors = array(
    anchor_add('remote_boot/profile/add'),
);

$options['id'] = 'remote_boot_profile_summary';

echo summary_table(
    $title,
    $anchors,
    $headers,
    $items,
    $options
);
