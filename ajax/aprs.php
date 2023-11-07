<?php
/*
*   RoLinkX Dashboard v3.5
*   Copyright (C) 2023 by Razvan Marin YO6NAM / www.xpander.ro
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
* APRS configuration module
*/

/*
* APRS password generator code source :
* https://github.com/magicbug/PHP-APRS-Passcode
*/

include __DIR__ .'/../includes/functions.php';
$cfgFile = '/etc/direwolf.conf';
$newFile = '/tmp/direwolf.conf.tmp';
$overlay = '';
$msgOut = 'Configuration saved';

$frmService		= filter_input(INPUT_POST, 'service', FILTER_SANITIZE_NUMBER_INT);
$frmCallsign		= filter_input(INPUT_POST, 'callsign', FILTER_SANITIZE_ADD_SLASHES);
$frmSymbol		= filter_input(INPUT_POST, 'symbol', FILTER_SANITIZE_ADD_SLASHES);
$frmComment		= filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_ADD_SLASHES);
$frmTemp		= filter_input(INPUT_POST, 'temp', FILTER_SANITIZE_NUMBER_INT);
$frmServer		= filter_input(INPUT_POST, 'server', FILTER_SANITIZE_ADD_SLASHES);
$frmReport		= filter_input(INPUT_POST, 'report', FILTER_SANITIZE_NUMBER_INT);

$dwStatus 	= trim(shell_exec("systemctl is-enabled direwolf"));
$svcDirewolf 	= trim(shell_exec("systemctl is-active direwolf"));

$cfgData = file_get_contents($cfgFile);
preg_match('/MYCALL\s+(.+)/', $cfgData, $cfgCallsign);
preg_match('/TBEACON.*symbol="([^"]*)".*overlay="([^"]*)".*comment="([^"]*)"(?:.*commentcmd="([^"]*)")?/', $cfgData, $cfgBeacon);
preg_match('/IGSERVER\s+(.+)/', $cfgData, $cfgServer);
preg_match('/IGLOGIN\s+(.+)/', $cfgData, $cfgLogin);
preg_match('/KISSCOPY\s+(.+)/', $cfgData, $cfgReport);
if ($cfgCallsign) {
    $cfgData = preg_replace('/MYCALL\s+(.+)/', 'MYCALL ' . $frmCallsign, $cfgData);
}
if ($cfgBeacon) {
	if ($frmSymbol == 'rolink') {
		$overlay = 'R';
		$frmSymbol = 'V0';
	}
	$command = ($frmTemp == 1) ? 'commentcmd="/opt/rolink/scripts/aprs temp"' : null;
    $cfgData = preg_replace('/TBEACON.*symbol="([^"]*)".*overlay="([^"]*)".*comment="([^"]*)"(?:.*commentcmd="([^"]*)")?/',
    'TBEACON sendto=IG altitude=1 symbol="' . stripslashes($frmSymbol) . '" overlay="' . $overlay . '" comment="' . $frmComment . '" '. $command, $cfgData);
}
if ($cfgServer) {
    $cfgData = preg_replace('/IGSERVER\s+(.+)/', 'IGSERVER ' . $frmServer, $cfgData);
}
if ($cfgLogin) {
    $cfgData = preg_replace('/IGLOGIN\s+(.+)/', 'IGLOGIN ' . $frmCallsign . ' '. aprspass($frmCallsign), $cfgData);
}
if ($cfgReport) {
    $cfgData = preg_replace('/KISSCOPY\s+(.+)/', 'KISSCOPY ' . $frmReport, $cfgData);
}

toggleFS(true);
file_put_contents($newFile, $cfgData);
exec("/usr/bin/sudo /usr/bin/cp $newFile $cfgFile");
if ($frmService == 0 && $dwStatus == 'enabled') {
	serviceControl('direwolf.service', 'disable');
	serviceControl('direwolf.service', 'stop');
	$msgOut .= ' & Direwolf service disabled';
} elseif ($frmService == 1 && $dwStatus == 'disabled') {
	serviceControl('direwolf.service', 'enable');
	serviceControl('direwolf.service', 'start');
	$msgOut .= ' & Direwolf service enabled';
} else {
	if ($dwStatus == 'enabled') {
		serviceControl('direwolf.service', 'restart');
		$msgOut .= ' & Direwolf service restarted';
	}
}
toggleFS(false);
echo $msgOut;

function aprspass($callsign) {
    $call = strtoupper(substr(strtok($callsign, '-'), 0, 10));
    $parts = unpack("n*", strlen($call) % 2 ? "$call\x00" : $call);
    $hash = 0x73e2;
    foreach ($parts as $part) {
        $hash ^= $part;
    }
    return $hash & 0x7fff;
}
