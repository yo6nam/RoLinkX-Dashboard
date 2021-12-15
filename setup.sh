#!/bin/bash
# RoLinkX Dashboard v1.0
# Setup script for minimum dashboard requirements

wlanCfgFile="/etc/wpa_supplicant/wpa_supplicant.conf"

# Buster defaults
nmService=network-manager
phpVersion=7.3

if grep "bullseye" /etc/os-release >/dev/null;then
	nmService=NetworkManager
	phpVersion=7.4
fi

# Check if we should modify network
if systemctl is-enabled $nmService | grep enabled >/dev/null; then
	printf 'Network Manager is enabled. We must disable it\n'
	# Setup eth0
	if cat /etc/network/interfaces | grep 'auto eth0' >/dev/null;then
		printf 'LAN interface already configured\n';
	else
		printf '\nauto eth0\nallow-hotplug eth0\niface eth0 inet dhcp\n' | tee -a /etc/network/interfaces >/dev/null
	fi
	# Setup wlan0
	if cat /etc/network/interfaces | grep 'auto wlan0' >/dev/null;then
		printf 'WLAN interface already configured\n';
	else
		printf '\nauto wlan0\nallow-hotplug wlan0\niface wlan0 inet dhcp\nwpa-conf /etc/wpa_supplicant/wpa_supplicant.conf\niface default inet dhcp\n' | tee -a /etc/network/interfaces >/dev/null
	fi
	# Now it's safe to disable NM
	systemctl stop $nmService
	systemctl disable $nmService
fi

# Fix possible DNS issues
if grep '8.8.8.8' /etc/resolvconf/resolv.conf.d/head >/dev/null; then
	printf 'DNS tweak is present, moving on...\n';
else
	printf 'Tweaking DNS\n';
	/usr/bin/expect<<EOF
spawn dpkg-reconfigure -f readline resolvconf
expect "updates?" { send "Yes\r" }
expect "dynamic files?" { send "Yes\r" }
EOF
	printf 'nameserver 8.8.8.8\nnameserver 8.8.4.4\n' | tee -a /etc/resolvconf/resolv.conf.d/head >/dev/null
	resolvconf --enable-updates
	resolvconf -u
fi

# Setup wpa_supplicant
if [ ! -f "$wlanCfgFile" ]; then
	printf "wpa_supplicant.conf was not found so we'll create one with defaults\n";
	printf 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev\nupdate_config=1\nap_scan=1\nfast_reauth=1\ncountry=GB' | tee -a $wlanCfgFile >/dev/null
	wpa_supplicant -B -c $wlanCfgFile -i wlan0 >/dev/null
fi

# Check and install lighttpd & PHP
if netstat -tnlp | grep "lighttpd" >/dev/null; then
	printf 'lighttpd is present, moving on...\n'
else
	printf 'lighttpd is NOT present, installing it now\n'
	apt-get update
	apt-get install lighttpd php$phpVersion-fpm php-cgi -y
	lighttpd-enable-mod fastcgi-php >/dev/null
	service lighttpd force-reload
fi

if [ -d "/var/www/html" ]; then
	printf "Copying files...\n"
	cp -r . /var/www/html/rolink
	printf "Setting up permissions\n"
	chown -R www-data /var/www/html/rolink
	if [ -f /var/www/html/rolink/69_rolink ]; then
		printf "Giving access to certain commands\n"
		cp /var/www/html/rolink/69_rolink /etc/sudoers.d/69_rolink;
		rm -f /var/www/html/rolink/69_rolink /var/www/html/rolink/setup.sh /var/www/html/rolink/README.md
		[ -f /var/www/html/rolink/profiles/.gitignore ] && rm -f /var/www/html/rolink/profiles/.gitignore
	fi
fi
printf "Done! You should access the dashboard using\nhttp://$(hostname)/rolink or http://$(hostname -I|xargs)/rolink\n"
