<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename']     = 'remote_boot';
$app['version']      = '1.0.0';
$app['release']      = '7';
$app['vendor']       = 'PoliLinux Soluções e Serviços TI Ltda';
$app['packager']     = 'PoliLinux';
$app['license']      = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description']  = lang('remote_boot_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('remote_boot_app_name');
$app['category'] = lang('base_category_network');
$app['subcategory'] = lang('base_subcategory_infrastructure');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['remote_boot']['title'] = lang('remote_boot_app_name');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
    'app-dhcp',
);

$app['core_requires'] = array(
    'app-network-core',
    'app-dhcp-core',
    'nfs-utils >= 1.3.0',
    'syslinux >= 4.05',
    'dnsmasq >= 2.48'
);

$app['core_directory_manifest'] = array(
    '/var/clearos/remote_boot' => array(),
    '/var/clearos/remote_boot/backup' => array(),
);

$app['core_file_manifest'] = array(
    'nfs-server.php' => array('target' => '/var/clearos/base/daemon/nfs-server.php'),
);

$app['delete_dependency'] = array(
    'app-remote-boot',
    'app-remote-boot-core',
);
