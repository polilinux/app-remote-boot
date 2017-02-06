<?php
/**
* Javascript helper for Remote_Boot.
*
* @category   apps
* @package    remote_boot
* @subpackage controllers
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
// J A V A S C R I P T
////////////////////////////////////////////////////////////////////////
header('Content-Type: application/x-javascript');
?>
// keep a copy of the old profile name global for substitution purposes
$(document).ready( function() {
    set_read_only(true);
    update_pxelinux_cfg_file();

    // react to changes to tftp root folder
    $(document.getElementById('global.folders.tftp.name')).change(function () {
        update_pxelinux_cfg_dir();
    });

    // react to changes to profile name
    $(document.getElementById('profile.name')).change(function () {
        update_form_info();
    });

    // react to changes to NFS ip address
    $(document.getElementById('profile.nfs.ip')).change(function () {
        update_form_info();
    });

    // react to changes to client network
    $(document.getElementById('profile.network_cidr')).change(function () {
        update_pxelinux_cfg_file();
    });
});

// update pxelinux configuration folder based on TFTP root global setting
function update_pxelinux_cfg_dir() {
    var tftp_root = $(document.getElementById('global.folders.tftp.name')).prop('value');

    // we hard-code pxelinux.cfg folder for now
    var pxelinux_folder = tftp_root + '/pxelinux.cfg';

    document.getElementById('global.folders.pxelinux.name').setAttribute('value',pxelinux_folder);

    return;
}

// updates all fields that depend on profile name
function update_form_info() {
    var profile_name = $(document.getElementById('profile.name')).prop('value');
    var nfs_ip = $(document.getElementById('profile.nfs.ip')).prop('value');

    generate_form_info(profile_name, nfs_ip);
    return;
}

// update pxelinux config file field, which depends on client network field
function update_pxelinux_cfg_file() {
    var new_client_network = $(document.getElementById('profile.network_cidr')).prop('value');
    var pxelinux_cfgfile = $("input[id=profile\\.pxelinux\\.cfgfile");

    var old_filename = basename(pxelinux_cfgfile.prop('value'));
    var new_filename = convert_network_to_pxelinux_filename(new_client_network);

    pxelinux_cfgfile.val(pxelinux_cfgfile.prop('value').replace(old_filename,new_filename));
}

// converts a network to pxelnux cfg filename in HEX
function convert_network_to_pxelinux_filename(network_cidr)
{
    var regex_pattern = /(\d+)\.(\d+)\.(\d+)\.(\d+)\/\d+/;
    var octets = regex_pattern.exec(network_cidr);

    try{
        var hex_result = d2h(octets[1]) + d2h(octets[2])
        + d2h(octets[3]) + d2h(octets[4]);
    } catch(err) {
        hex_result = "invalid";
    }
    cfg_filename = hex_result.replace(/0+$/,'');
    if (cfg_filename.length === 0) {
        cfg_filename = "default";
    }

    // remove trailing zeros and return
    return cfg_filename;
}

// convert from decimal to hex with padding
function d2h(d) {
    // convert to hex
    var hex_number = (+d).toString(16).toUpperCase();

    // pad with leading zeroes
    var padded_hex = ('00'+hex_number).slice(-2);

    return padded_hex;
}

// get the basename from an absolute path
function basename(path) {
    return path.split('/').reverse()[0];
}

function set_read_only(value) {
    var form_id = $('form').attr('id');

    if (form_id == "remote_boot_profile_settings") {
        document.getElementById('profile.folders.rootfs.name').readOnly = value;
        document.getElementById('profile.folders.home.name').readOnly = value;
        document.getElementById('profile.folders.etc.name').readOnly = value;
        document.getElementById('profile.folders.var.name').readOnly = value;
        document.getElementById('profile.folders.root.name').readOnly = value;
        document.getElementById('profile.folders.kernel.name').readOnly = value;
        document.getElementById('profile.pxelinux.initrd').readOnly = value;
        document.getElementById('profile.pxelinux.cfgfile').readOnly = value;
        document.getElementById('profile.pxelinux.kernel').readOnly = value;
    }
    else if (form_id == "remote_boot_global_settings") {
        document.getElementById('global.tftpserver.cfgfile').readOnly = value;
        document.getElementById('global.folders.pxelinux.name').readOnly = value;
    }

}

// Ajax function that generates new form info based on new profile name
// and NFS ip to ensure compliance to global config settings
function generate_form_info(name, nfsip) {
    var url = '/app/remote_boot/profile/gen_form_info/' + name + '/' + nfsip;

    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: url,
        success: function(data) {
            document.getElementById('profile.folders.rootfs.name').innerHTML = data['folders']['rootfs']['name'];
            document.getElementById('profile.folders.home.name').innerHTML = data['folders']['home']['name'];
            document.getElementById('profile.folders.etc.name').innerHTML = data['folders']['etc']['name'];
            document.getElementById('profile.folders.var.name').innerHTML = data['folders']['var']['name'];
            document.getElementById('profile.folders.root.name').innerHTML = data['folders']['root']['name'];

            document.getElementById('profile.folders.kernel.name').setAttribute('value',data['folders']['kernel']['name']);
            document.getElementById('profile.pxelinux.initrd').setAttribute('value',data['pxelinux']['initrd']);
            document.getElementById('profile.pxelinux.kernel').setAttribute('value',data['pxelinux']['kernel']);

            document.getElementById('profile.pxelinux.kparams').innerHTML = data['pxelinux']['kparams'];
        }
    });

    return;
}
// vim: syntax=javascript ts=4
