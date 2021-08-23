#!/bin/bash
# RoLinkX Dashboard v0.1a
# Setup script for minimum dashboard requirements

if netstat -tnlp | grep "lighttpd" >/dev/null; then
	printf 'Check : lighttpd is present\n'
else
	printf 'Check : lighttpd is not installed\n'
	read -p "Do you want to proceed with installation? (y/n)" -n 1 -r
	printf '\n'
	if [[ $REPLY =~ ^[Yy]$ ]];then
		sudo apt-get update
		sudo apt-get install lighttpd php7.3-fpm php-cgi -y
	fi
fi

if [ ! $(command -v php-cgi) ]; then
	printf 'Check : php-cgi not installed, installing...\n'
	sudo apt-get install php7.3-fpm php-cgi -y
fi

if [ -d "/var/www/html" ]; then
    sudo lighttpd-enable-mod fastcgi-php
    sudo service lighttpd force-reload
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
printf "Done! You should access the dashboard using\nhttp://rolink/rolink or http://<IP>/rolink\n"
