<?php
////////////////////////////////////////////////////////////////////
// C O N S T A N T S
////////////////////////////////////////////////////////////////////
// _PATH, _FILE constants should always be absolute
// FOLDERs start with "/" to indicate a desired folder name
// *_FILENAME indicates that it doesn't start with "/"
// avoid ending any constant with "/" (we standardize it as a prefix)
define( "RB_DEF_PROFILE_NAME"                   , 'default');
define( "RB_DEF_PROFILE_NAME_MAX_SIZE"          , 15);
define( "RB_DEF_PROFILE_NETWORK_CIDR"           , '0.0.0.0/0');

// the following three definitions should replace
//   - SUBNETS_CONFIG_BASE (used in Config)
define( "RB_BASE_SERVER_PATH"           , '/srv');
define( "RB_TFTP_PATH"                  , RB_BASE_SERVER_PATH.'/tftp');
define( "RB_NFSROOT_PATH"               , RB_BASE_SERVER_PATH.'/nfs');
define( "RB_DEF_ROOTFS_PATH"            , RB_NFSROOT_PATH.'/'.RB_DEF_PROFILE_NAME.'/rootfs');
define( "RB_DEF_ROOT_PATH"              , RB_NFSROOT_PATH.'/'.RB_DEF_PROFILE_NAME.'/root');
define( "RB_DEF_HOME_PATH"              , RB_NFSROOT_PATH.'/'.RB_DEF_PROFILE_NAME.'/home');
define( "RB_DEF_VAR_PATH"               , RB_NFSROOT_PATH.'/'.RB_DEF_PROFILE_NAME.'/var');
define( "RB_DEF_ETC_PATH"               , RB_NFSROOT_PATH.'/'.RB_DEF_PROFILE_NAME.'/etc');
define( "RB_DEF_SYSUSER"                , 'root');
define( "RB_DEF_SYSGROUP"               , 'root');
define( "RB_DEF_FOLDER_PERMS"           , '755');
define( "RB_DEF_FILE_PERMS"             , '644');

define( "RB_PXE_CFG_PATH"               , RB_TFTP_PATH.'/pxelinux.cfg');
define( "RB_PXE_CFG_DEFAULT_FILE"       , RB_PXE_CFG_PATH.'/default');
define( "RB_PXE_DEF_KERNEL_PATH"        , RB_TFTP_PATH.'/'.RB_DEF_PROFILE_NAME.'/kernel');
define( "RB_PXE_DEF_KERNEL_FILE"        , RB_PXE_DEF_KERNEL_PATH.'/vmlinuz');
define( "RB_PXE_DEF_INITRAMFS_FILE"     , RB_PXE_DEF_KERNEL_PATH.'/initrd.img');
define( "RB_PXE_BOOTLOADER_FILENAME"    , 'pxelinux.0');                                      // this is the bootloader we use
define( "RB_PXE_BOOTLOADER"             , RB_TFTP_PATH.'/'.RB_PXE_BOOTLOADER_FILENAME);       // this is the bootloader we use
define( "RB_PXE_BOOTLOADER_SYS_FILE"    , '/usr/share/syslinux/'.RB_PXE_BOOTLOADER_FILENAME); // we expect syslinux to make this file available to us

define( "RB_APP_CONFIG_PATH"            , '/etc/clearos');
define( "RB_APP_CONFIG_FILE"            , RB_APP_CONFIG_PATH.'/remote_boot.conf');

define( "RB_TFTP_CFG_FILE"              , '/etc/dnsmasq.d/remote_boot_tftp.conf');
define( "RB_NFS_EXPORTS_FILE"           , '/etc/exports');
define( "RB_NFS_EXPORTS_PATH"           , '/etc/exports.d');
define( "RB_NFS_EXPORTS_PREFIX"         , 'remote_boot_');
define( "RB_NFS_EXPORTS_SUFFIX"         , '.exports');
define( "RB_NFS_EXPORTOPTS_RO_SQUASH"   , 'ro,async,root_squash,no_subtree_check');
define( "RB_NFS_EXPORTOPTS_RW_SQUASH"   , 'rw,async,root_squash,no_subtree_check');
define( "RB_NFS_EXPORTOPTS_RW_NOSQUASH" , 'rw,async,no_root_squash,no_subtree_check');


define( "RB_LDAP_FILENAME"              , 'ldap.conf');                                 // replaces LDAP_FILE
define( "RB_NSSWITCH_FILENAME"          , 'nsswitch.conf');                             // replaces NSSWITCH_FILE

define( "RB_SYNC_EXPORTS_FILE"          , TRUE);
define( "RB_SYNC_LDAP_FILES"            , TRUE);
define( "RB_SYNC_PXELINUX_CONF"         , TRUE);

define( "RB_LINE_BREAK"                 , "\n");
