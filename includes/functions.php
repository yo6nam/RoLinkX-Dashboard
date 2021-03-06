<?php
/*
*   RoLinkX Dashboard v2.0
*   Copyright (C) 2022 by Razvan Marin YO6NAM / www.xpander.ro
*
*   This program is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; either version 2 of the License, or
*   (at your option) any later version.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software
*   Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
*/

/*
* Common functions
*/

// Static variables
$config = include __DIR__ .'/../config.php';
$cfgRefFile = '/opt/rolink/conf/rolink.json';
$tmpRefFile = '/tmp/rolink.json.tmp';
$cfgRefData = json_decode(file_get_contents($cfgRefFile), true);

// Switch file system status (ReadWrite <-> ReadOnly)
function toggleFS($status) {
	if (!preg_match('/ro,ro/', file_get_contents('/etc/fstab'))) return;
	exec('/usr/bin/cat /proc/mounts | grep -Po \'(?<=(ext4\s)).*(?=,noatime)\'', $prevStatus);
	$changeTo = ($status) ? '/usr/bin/sudo /usr/bin/mount -o remount,rw /' : '/usr/bin/sudo /usr/bin/mount -o remount,ro /';
	exec($changeTo);
	sleep(1);
	exec('/usr/bin/cat /proc/mounts | grep -Po \'(?<=(ext4\s)).*(?=,noatime)\'', $afterStatus);
	if ($status && $prevStatus[0] == 'ro' & $afterStatus[0] == 'ro' ||
		!$status && $prevStatus[0] == 'rw' & $afterStatus[0] == 'rw') {
		echo 'Something went wrong switching FS!<br/>Please reboot';
		exit(1);
	}
}

function serviceControl($service, $action){
	exec('/usr/bin/sudo /usr/bin/systemctl '. $action .' '. $service);
}

/* Prevent SA818 TX state = on */
function unstick(){
	global $config;
	$pinPath = '/sys/class/gpio/gpio'. $config['cfgPttPin'] .'/value';
	exec('/usr/bin/sudo /usr/bin/chmod guo+rw '. $pinPath .'; /usr/bin/echo 0 > '. $pinPath);
}
