
Name: app-remote-boot
Epoch: 1
Version: 1.0.0
Release: 7%{dist}
Summary: Remote Boot
License: GPLv3
Group: ClearOS/Apps
Packager: PoliLinux
Vendor: PoliLinux Soluções e Serviços TI Ltda
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-network
Requires: app-dhcp

%description
The Remote Boot app allows an administrator to setup all configuration needed to manage a tftp boot system.

%package core
Summary: Remote Boot - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: app-dhcp-core
Requires: nfs-utils >= 1.3.0
Requires: syslinux >= 4.05
Requires: dnsmasq >= 2.48

%description core
The Remote Boot app allows an administrator to setup all configuration needed to manage a tftp boot system.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/remote_boot
cp -r * %{buildroot}/usr/clearos/apps/remote_boot/

install -d -m 0755 %{buildroot}/var/clearos/remote_boot
install -d -m 0755 %{buildroot}/var/clearos/remote_boot/backup
install -D -m 0644 packaging/nfs-server.php %{buildroot}/var/clearos/base/daemon/nfs-server.php

%post
logger -p local6.notice -t installer 'app-remote-boot - installing'

%post core
logger -p local6.notice -t installer 'app-remote-boot-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/remote_boot/deploy/install ] && /usr/clearos/apps/remote_boot/deploy/install
fi

[ -x /usr/clearos/apps/remote_boot/deploy/upgrade ] && /usr/clearos/apps/remote_boot/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-remote-boot - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-remote-boot-core - uninstalling'
    [ -x /usr/clearos/apps/remote_boot/deploy/uninstall ] && /usr/clearos/apps/remote_boot/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/remote_boot/controllers
/usr/clearos/apps/remote_boot/htdocs
/usr/clearos/apps/remote_boot/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/remote_boot/packaging
%dir /usr/clearos/apps/remote_boot
%dir /var/clearos/remote_boot
%dir /var/clearos/remote_boot/backup
/usr/clearos/apps/remote_boot/deploy
/usr/clearos/apps/remote_boot/language
/usr/clearos/apps/remote_boot/libraries
/var/clearos/base/daemon/nfs-server.php
