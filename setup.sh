#!/bin/bash
# RoLinkX Dashboard v0.1a
# Setup script for minimum dashboard requirements

wlanCfgFile="/etc/wpa_supplicant/wpa_supplicant.conf"

# Check if we should modify network
if systemctl is-enabled network-manager | grep enabled >/dev/null; then
	printf 'Network Manager is enabled. We must disable it\n'
	read -p "Do you want to proceed? (y/n)" -n 1 -r
	printf '\n'
	if [[ $REPLY =~ ^[Yy]$ ]];then
		# Setup eth0
		if cat /etc/network/interfaces | grep 'auto eth0' >/dev/null;then
			printf 'LAN interface already configured\n';
		else
			printf '\nauto eth0\nallow-hotplug eth0\niface eth0 inet dhcp\n' | tee -a /etc/network/interfaces > /dev/null
		fi
		# Setup wlan0
		if cat /etc/network/interfaces | grep 'auto wlan0' >/dev/null;then
			printf 'WLAN interface already configured\n';
		else
			printf '\nauto wlan0\nallow-hotplug wlan0\niface wlan0 inet dhcp\nwpa-conf /etc/wpa_supplicant/wpa_supplicant.conf\niface default inet dhcp\n' | tee -a /etc/network/interfaces > /dev/null
		fi
		# Now it's safe to disable NM
		systemctl stop network-manager
		systemctl disable network-manager
	else
		printf "Bye!\n"; exit 0;
	fi
fi

# Setup wpa_supplicant
if [ ! -f "$wlanCfgFile" ]; then
	printf "wpa_supplicant.conf was not found so we'll create one with defaults\n";
	printf 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev\nupdate_config=1\nap_scan=1\nfast_reauth=1\ncountry=GB' | tee -a $wlanCfgFile > /dev/null
	wpa_supplicant -B -c $wlanCfgFile -i wlan0 >/dev/null
fi

if netstat -tnlp | grep "lighttpd" >/dev/null; then
	printf 'Check : lighttpd is present\n'
else
	printf 'Check : lighttpd is not installed\n'
	read -p "Do you want to proceed with installation? (y/n)" -n 1 -r
	printf '\n'
	if [[ $REPLY =~ ^[Yy]$ ]];then
		apt-get update
		apt-get install lighttpd php7.3-fpm php-cgi -y
	fi
fi

if [ ! $(command -v php-cgi) ]; then
	printf 'Check : php-cgi not installed, installing...\n'
	apt-get install php7.3-fpm php-cgi -y
fi

if [ -d "/var/www/html" ]; then
    lighttpd-enable-mod fastcgi-php
    service lighttpd force-reload
    printf "Copying files...\n"
    cp -r . /var/www/html/rolink
    printf "Setting up permissions\n"
    chown -R www-data /var/www/html/rolink
    if [ -f /var/www/html/rolink/69_rolink ]; then
    	printf "Giving access to certain commands\n"
    	cp /var/www/html/rolink/69_rolink /etc/sudoers.d/69_rolink;
    	rm -f /var/www/html/rolink/69_rolink
    	rm -f /var/www/html/rolink/setup.sh
    	[ -f /var/www/html/rolink/profiles/.gitignore ] && rm -f /var/www/html/rolink/profiles/.gitignore
    fi
fi
printf "Done! You should access the dashboard using\nhttp://$(hostname)/rolink or http://<IP>/rolink\n"
