<?php

////////////////////////////////////////////////////////////////////////
// App info
////////////////////////////////////////////////////////////////////////

$lang['remote_boot_app_name']        = 'Remote Boot';
$lang['remote_boot_app_description'] = 'The Remote Boot app allows an administrator to set up a TFTP server, '.
    'an NFS server and pxelinux configuration files in order to boot diskless stations through the network.';
$lang['remote_boot_nfs_server'] = 'NFS Server';

////////////////////////////////////////////////////////////////////////
// Global settings form
////////////////////////////////////////////////////////////////////////
$lang['text_global_settings'] = 'Global Settings';
// Global TFTP server configuration section
$lang['field_global_tftp_automanage'] = 'Manage TFTP automatically';
$lang['field_global_tftp_name'] = 'TFTP Server name';
$lang['field_global_tftp_ip'] = 'TFTP Server IP address';
$lang['field_global_tftp_rootfolder'] = 'TFTP Server Root Folder';
$lang['field_global_tftp_cfgfile'] = 'TFTP Server config file';

// Global NFS exports configuration section
$lang['field_global_nfs_automanage'] = 'Manage NFS Exports automatically';
$lang['field_global_nfs_ip'] = 'NFS Server IP address';
$lang['field_global_nfs_rootfolder'] = 'NFS Server Exports Root Folder';

// Global Pxelinux configuration section
$lang['field_global_pxelinux_rootfolder'] = 'PXELinux configuration folder';

////////////////////////////////////////////////////////////////////////
// Profile summary form
////////////////////////////////////////////////////////////////////////

// Profile text labels and fields
// general section
$lang['text_profile_summary'] = 'Profile Summary';
$lang['header_profile_name'] = 'Name';
$lang['header_profile_status'] = 'Status';
$lang['header_profile_network'] = 'Network';

$lang['profile_summary_disable'] = 'Disable';
$lang['profile_summary_enable'] = 'Enable';

////////////////////////////////////////////////////////////////////////
// Profile add_edit settings form
////////////////////////////////////////////////////////////////////////

// Profile text labels and fields
// general section
$lang['text_profile_settings'] = 'Profile Settings';
$lang['field_profile_name'] = 'Profile Name';
$lang['field_profile_network'] = 'Client Network with Prefix';
$lang['field_profile_network_cidr'] = 'Client Network (CIDR)';
$lang['field_profile_enable'] = 'Enable';
$lang['field_profile_tftp_ip'] = 'TFTP Server IP address';

// PXElinux section
$lang['text_pxe_settings'] = 'Pxelinux Settings';
$lang['field_profile_folder_kernel'] = 'Kernel Folder';
$lang['field_profile_pxelinux_cfgfile'] = 'PXELinux config file';
$lang['field_profile_pxelinux_initrd'] = 'initrd.img file';
$lang['field_profile_pxelinux_kernel'] = 'kernel (vmlinuz) file';
$lang['field_profile_pxelinux_kparams'] = 'kernel parameters';

// NFS Folder Exports section
$lang['text_nfs_settings'] = 'NFS Exports Settings';
$lang['field_profile_nfs_ip'] = 'NFS Server IP address';
$lang['field_export_folders'] = 'Export Folder';
$lang['field_export_options'] = 'Export Options';
$lang['field_export_checkbox'] = 'Export?';


////////////////////////////////////////////////////////////////////////
// Form validation messages
////////////////////////////////////////////////////////////////////////

$lang['form_validation_profile_name_characters'] = 'May have only letters, digits, underscores, dashes, and dots';
$lang['form_validation_profile_name_length'] = 'Profile name too long (max 15 characters)';
$lang['form_validation_path_invalid'] = 'Absolute path required. May have only slashes, letters, digits, underscores, '.
    'dashes, and dots';
$lang['form_validation_kparams_invalid'] = 'May have only letters, digits, and characters from this group: /_-.=. '.
    'No newlines allowed (\n).';
$lang['form_validation_network_invalid'] = 'Invalid network address';
$lang['form_validation_network_address_invalid'] = 'Please supply correct network address for this prefix: ';
$lang['form_validation_boolean'] = ' This field accepts only TRUE or FALSE (1 or 0)';
$lang['form_validation_nfs_empty_param'] = 'Empty nfs export option detected (check for extra commas)';
$lang['form_validation_nfs_invalid_param_char'] = 'Invalid nfs export option:';
$lang['form_validation_no_newlines_allowed'] = 'No newline characters allowed';

////////////////////////////////////////////////////////////////////////
// App Messages block
////////////////////////////////////////////////////////////////////////

$lang['msg_head_message_id_does_not_exist'] = 'Warning';
$lang['msg_text_message_id_does_not_exist'] = 'message not implemented: ';

// profile management queue title messages
$lang['msg_title_internal_app_checks'] = 'Internal Application checks';
$lang['msg_title_profile_add'] = 'Adding new profile';
$lang['msg_title_profile_edit'] = 'Editing existing profile';
$lang['msg_title_profile_delete'] = 'Deleting existing profile';
$lang['msg_title_profile_enable'] = 'Enabling existing profile';
$lang['msg_title_profile_disable'] = 'Disabling existing profile';
$lang['msg_title_global_edit'] = 'Editing global settings';

$lang['msg_head_invalid_profile_name'] = 'Invalid profile name';
$lang['msg_head_invalid_profile_network'] = 'Invalid profile network';

$lang['msg_text_profile_name_already_exists'] = 'This profile name already exists';
$lang['msg_text_profile_network_already_exists'] = 'This network already exists';

$lang['msg_text_profile_write_to_cfg_failed'] = 'Could not write to file';
$lang['msg_text_profile_write_to_cfg_success'] = 'Profile configuration written to file';

$lang['msg_head_profile_network_overlap'] = 'Network overlap';
$lang['msg_text_profile_network_overlap'] = '';

$lang['msg_text_global_settings_not_updated'] = 'Global settings not updated';
$lang['msg_text_global_settings_udpated'] = 'Global settings updated';

$lang['msg_text_profile_exports_file_created'] = 'Exports file created';
$lang['msg_text_profile_exports_file_not_created'] = 'Exports file not created';
$lang['msg_text_profile_exports_file_removed'] = 'Exports file removed';
$lang['msg_text_profile_exports_file_not_removed'] = 'Exports file not removed';
$lang['msg_text_profile_exports_reloaded'] = 'NFS exports reloaded';
$lang['msg_text_profile_exports_not_reloaded'] = 'NFS exports not reloaded';
$lang['msg_text_profile_folders_created'] = 'Profile folders created';
$lang['msg_text_profile_folders_not_created'] = 'Profile folders not created';
$lang['msg_text_profile_folders_moved'] = 'Profile folders moved';
$lang['msg_text_profile_folders_not_moved'] = 'Profile folders not moved';
$lang['msg_text_profile_pxelinux_cfg_file_created'] = 'Pxelinux config file created';
$lang['msg_text_profile_pxelinux_cfg_file_removed'] = 'Pxelinux config file removed';
$lang['msg_text_profile_pxelinux_cfg_file_not_created'] = 'Pxelinux config file not created';
$lang['msg_text_profile_pxelinux_cfg_file_not_removed'] = 'Pxelinux config file not removed';
$lang['msg_text_profile_removed_from_config'] = 'Profile removed from configuration file';
$lang['msg_text_profile_not_removed_from_config'] = 'Could not remove profile from configuration file';
$lang['msg_text_profile_replaced_in_config'] = 'Profile replaced in configuration file';
$lang['msg_text_profile_failed_to_replace_config'] = 'Could not replace profile in configuration file';

$lang['msg_head_file_not_found']         = 'File not found';
$lang['msg_text_file_not_found']         = '';

$lang['msg_head_file_created']         = 'File created';
$lang['msg_text_file_created']         = '';

$lang['msg_head_error_copying_file']         = 'File error';
$lang['msg_text_error_copying_file']         = 'File copy failed';

$lang['msg_head_folder_not_found']         = 'Folder not found';
$lang['msg_text_folder_not_found']         = '';

$lang['msg_head_folder_created']         = 'Folder created';
$lang['msg_text_folder_created']         = '';

$lang['msg_head_file_deleted']         = 'File deleted';
$lang['msg_text_file_deleted']         = '';

$lang['msg_head_folder_deleted']         = 'Folder deleted';
$lang['msg_text_folder_deleted']         = '';

$lang['msg_text_profile_all_folders_deleted']     = 'All profile folders were deleted';
$lang['msg_text_profile_not_all_folders_deleted'] = 'One or more profile folders were not deleted!';

$lang['msg_head_success']         = 'Success';
$lang['msg_text_success']         = '';

$lang['msg_head_warn']         = 'Warning';
$lang['msg_text_warn']         = '';

$lang['msg_head_error']         = 'Error';
$lang['msg_text_error']         = '';

$lang['msg_head_info']         = 'Info';
$lang['msg_text_info']         = '';

$lang['msg_head_profile_not_found'] = 'Profile error';
$lang['msg_text_profile_not_found'] = 'Profile not found';

$lang['msg_head_folder_not_created'] = 'Folder Error';
$lang['msg_text_folder_not_created'] = 'Folder could not be created';

$lang['msg_head_file_not_created'] = 'File Error';
$lang['msg_text_file_not_created'] = 'File could not be created';


////////////////////////////////////////////////////////////////////////
// Help Texts block
////////////////////////////////////////////////////////////////////////

// Global settings help text
$lang['field_global_tftp_automanage_help'] = 'If enabled, we will manage a local dnsmasq TFTP configuration for you.';
$lang['field_global_tftp_name_help'] = 'The hostname of the TFTP server. This hostname must contain dots'.
    '(e.g. server.lan). Default: local machine hostname.';
$lang['field_global_tftp_ip_help'] = 'Fill out with the IP address of the TFTP server where you want to get initrd and '.
    'vmlinuz images from';
$lang['field_global_tftp_rootfolder_help'] = 'This is the root folder of the TFTP server. It is always created even if '.
    'we don\'t manage TFTP for you';
$lang['field_global_tftp_cfgfile_help'] = 'This file is only created if TFTP automatic management is enabled. You '.
    'cannot edit the location of this file.';
$lang['field_global_nfs_automanage_help'] = 'If enabled, we will manage a local nfs-server service for you.';
$lang['field_global_nfs_ip_help'] = 'Fill out with the IP address of the NFS server which exports the NFS folders your '.
    'clients will access (e.g. rootfs, var, etc.)';
$lang['field_global_nfs_rootfolder_help'] = 'This is the root folder of the NFS server. It is always created even if we '.
    'don\'t manage NFS for you';
$lang['field_global_pxelinux_rootfolder_help'] = 'This is the root folder of all pxelinux configuration files. These '.
    'files are automatically generated based on profile configuration, so you cannot change this folder.';

// profile settings help text
$lang['field_profile_enable_help'] = 'If enabled, we create exports and pxelinux configuration files for you. If '.
    'disabled, we remove them. Default: enabled';
$lang['field_profile_name_help'] = 'Choose a name for your profile. Try to keep this short and descriptive (e.g. '.
    'ubuntu1604, fedora24, john, bob)!';
$lang['field_profile_nfs_ip_help'] = 'The IP address of your NFS server. The default value is taken from the global '.
    'settings, and this field affects the kernel parameters field.';
$lang['field_profile_tftp_ip_help'] = 'The IP address of your TFTP server. The default value is taken from the global '.
    'settings.';
$lang['field_profile_network_help'] = 'This network will be served by the Remote Boot server. The pxelinux config file '.
    'field is automatically filled out for you based on this field.';
$lang['field_export_folders_help'] = 'These folders store files used by clients booting through the network.';
$lang['field_export_options_help'] = "Export options for the folders you export. See 'man exports' for a complete l'.
    'ist of options.";
$lang['field_export_checkbox_help'] = 'Choose whether or not you want the folders on the left to be exported through NFS.';
$lang['field_profile_pxelinux_kparams_help'] = 'The parameters supplied to the kernel at boot time in the client stations.';
$lang['field_profile_folder_kernel_help'] = 'The TFTP server folder where the initrd.img and the kernel (vmlinuz) '.
    'executables need to be stored';
$lang['field_profile_pxelinux_cfgfile_help'] = 'The pxelinux config file name is automatically generated based on the '.
    'client network value. This file will be automatically created for you.';
$lang['field_profile_pxelinux_initrd_help'] = 'The initrd image file specified here is loaded from the TFTP server '.
    'specified by your DHCP server. If you allow TFTP to be managed automatically, this file will be loaded from this '.
    'server';
$lang['field_profile_pxelinux_kernel_help'] = 'The vmlinuz executable kernel image file specified here is loaded from '.
    'the TFTP server specified by your DHCP server. If you allow TFTP to be managed automatically, this file will be '.
    'loaded from this server';


////////////////////////////////////////////////////////////////////////
