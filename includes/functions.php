<?php
/*
 *   RoLinkX Dashboard v3.61
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
 * Common functions
 */

// Static variables
$config       = include __DIR__ . '/../config.php';
$cfgFile      = '/opt/rolink/conf/rolink.conf';
$cfgELFile    = '/opt/rolink/conf/svxlink.d/ModuleEchoLink.conf';
$cfgRefFile   = '/opt/rolink/conf/rolink.json';
$tmpRefFile   = '/tmp/rolink.json.tmp';
$verFile      = '/opt/rolink/version';
$remoteVerUrl = 'https://rolink.network/data/version';
$cfgRefData   = json_decode(file_get_contents($cfgRefFile), true);
$pinsArray    = [2, 3, 6, 7, 10, 18, 19];

// Switch file system status (ReadWrite <-> ReadOnly)
function toggleFS($status)
{
    if (!preg_match('/ro,ro/', file_get_contents('/etc/fstab'))) {
        return;
    }

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

function serviceControl($service, $action)
{
    exec('/usr/bin/sudo /usr/bin/systemctl ' . $action . ' ' . $service);
}

/* Prevent SA818 TX state = on */
function unstick()
{
    global $config;
    $pinPath = '/sys/class/gpio/gpio' . $config['cfgPttPin'] . '/value';
    exec('/usr/bin/sudo /usr/bin/chmod guo+rw ' . $pinPath . '; /usr/bin/echo 0 > ' . $pinPath);
}

/* GPSD */
function gpsd()
{
    $gpsdSock = fsockopen('localhost', 2947, $errno, $errstr, 2);
    $device   = shell_exec('/usr/bin/sudo /opt/rolink/scripts/init aprs');
    if (!$gpsdSock) {
        return '{"class":"ERROR","message":"' . $errstr . '"}';
    }
    $request = "?WATCH={\"enable\":true,\"json\":true,\"scaled\":true}\n";
    fwrite($gpsdSock, $request);
    usleep(750);
    $request = "?POLL;\n";
    fwrite($gpsdSock, $request);
    usleep(750);
    $response = '';
    for ($tries = 0; $tries < 10; $tries++) {
        $line = fgets($gpsdSock, 20000);
        if (preg_match('/{"class":"POLL".+}/i', $line, $m)) {
            $response = $m[0];
            break;
        }
    }
    fclose($gpsdSock);
    if (!empty($device)) {
        $response = $device;
    }

    if (!$response) {
        $response = '{"class":"ERROR","message":"no response from GPS daemon"}';
    }
    return $response;
}

/* Handle JS/CSS changes */
function cacheBuster($target)
{
    return sprintf("%u", crc32(file_get_contents($target)));
}

/* Return version & date */
function version()
{
    global $verFile;
    $v         = [];
    $localData = file_get_contents($verFile);
    if ($localData === false) {
        return false;
    }
    $data        = explode('|', $localData);
    $v['date']   = (int) $data[0];
    $v['number'] = $data[1];
    return $v;
}

/* Check environment */
function checkEnvironment()
{
    global $cfgFile;
    if (!is_file($cfgFile)) {
        return '<div class="alert alert-danger text-center" role="alert">RoLink not installed!</div>';
    }
    return false;
}

/* Detect SA8x8 */
function sa8x8Detect()
{
    $ttyPortDetected = $sa818Firmware = null;
    $version         = version();
    if ($version) {
        if ($version['date'] >= 20230126) {
            $sysReply = shell_exec('/usr/bin/sudo /opt/rolink/scripts/init sa_detect');
            if (!empty($sysReply)) {
                $sysData         = explode('|', $sysReply);
                $ttyPortDetected = (int) $sysData[0];
                $sa818Firmware   = str_replace("+VERSION:", "", trim($sysData[1]));
                return ['port' => $ttyPortDetected, 'version' => $sa818Firmware];
            }
        }
    }
    return;
}
