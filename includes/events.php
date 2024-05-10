<?php
/*
 *   RoLinkX Dashboard v3.68
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
 *   Real-time events
 */
ignore_user_abort(true);
set_time_limit(0);
header("Cache-Control: no-cache");
header("Content-Type: text/event-stream; charset=utf-8");
header("Connection: keep-alive");
header("Content-Encoding: none");
header("Access-Control-Allow-Origin: *");
ob_implicit_flush(true);
ob_end_flush();
$id       = $delay       = 0;
$prevData = [
    'talkerAction' => null,
    'gpio'         => null,
    'cpuLoad'      => null,
    'cpuTemp'      => null,
];
$firstStart = true;
$stream     = "/tmp/rolinkXstream";
$gpioPaths  = [
    'rx'  => '/sys/class/gpio/gpio10/value',
    'tx'  => '/sys/class/gpio/gpio7/value',
    'fan' => '/sys/class/gpio/gpio6/value',
];
$config = include __DIR__ . '/../config.php';
while (true) {
    // Clear the arrays
    $talkerData = $gpioData = $cpuData = $cpuStatus = [];

    // Add Talker status to array
    if (file_exists($stream)) {
        $event  = file_get_contents($stream);
        $evData = json_decode($event, true);
        if ($evData !== null) {
            $talkerData["ta"] = $evData["t"];
            $talkerData["tn"] = $evData["c"];
            if (!empty($evData["s"])) {
                $talkerData["s"] = $evData["s"];
            }
            if ($prevData['talkerAction'] != $talkerData["ta"]) {
                $jsonTalker = json_encode($talkerData);
                echo "id: $id\nretry: 1000\ndata: $jsonTalker\n\n";
                flush();
                ++$id;
                $prevData['talkerAction'] = $talkerData["ta"];
                $prevData['talkerName']   = $talkerData["tn"];
            }
        }
    }

    // Add GPIO status to array
    foreach ($gpioPaths as $key => $path) {
        if (file_exists($path)) {
            $value          = trim(file_get_contents($path));
            $gpioData[$key] = $value === "0" || $value === "1" ? $value : "0";
        }
    }
    $jsonGpio = json_encode($gpioData);
    if ($jsonGpio != $prevData['gpio']) {
        echo "id: $id\nretry: 1000\ndata: $jsonGpio\n\n";
        flush();
        ++$id;
        $prevData['gpio'] = $jsonGpio;
    }

    // Add CPU Load & Temp to array
    if ($firstStart || $delay >= 15) {
        $firstStart = false;
        exec("top -bn1 | awk '/Cpu/ { print $2}'", $cpuStatus);
        if (isset($cpuStatus[0]) && is_numeric($cpuStatus[0])) {
            $cpuLoad = $cpuStatus[0];
        }
        $rawTemp = file_get_contents(
            "/sys/devices/virtual/thermal/thermal_zone0/temp"
        );
        $cpuTemp       = number_format((int) $rawTemp / 1000, 1);
        $cpuData["cl"] = $cpuLoad;
        $cpuData["ct"] = ($config['cfgTempOffset'] == 'true') ? $cpuTemp + 38 : $cpuTemp;
        $delay         = 0;
        if ($prevData['cpuLoad'] == $cpuLoad) {
            continue;
        }
        if ($prevData['cpuTemp'] == $cpuTemp) {
            if (isset($cpuData["ct"])) {
                unset($cpuData["ct"]);
            }

        }
        $jsonCpu = json_encode($cpuData);
        echo "id: $id\nretry: 1000\ndata: $jsonCpu\n\n";
        flush();
        ++$id;
        $prevData['cpuLoad'] = $cpuLoad;
        $prevData['cpuTemp'] = $cpuTemp;
    }
    $delay++;
    usleep(200000);
    if (connection_aborted()) {
        exit();
    }

}
