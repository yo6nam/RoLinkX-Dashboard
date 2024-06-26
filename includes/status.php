<?php
/*
 *   RoLinkX Dashboard v3.68
 *   Copyright (C) 2024 by Razvan Marin YO6NAM / www.xpander.ro
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
 * Status reporting module
 */

if (isset($_GET['svxStatus'])) {
    echo getSVXLinkStatus();
}

if (isset($_GET['svxReflector'])) {
    echo getReflector(1);
}

if (isset($_GET['cpuData'])) {
    echo getCpuStats(1);
}

if (isset($_GET['gpio'])) {
    echo gpioStatus(1);
}

/* GPS dongle */
function getGPSDongle()
{
    $vidPidList      = array("1546:01a6", "1546:01a7", "067b:aaa0");
    $usbDevices      = shell_exec('lsusb');
    $usbDevicesArray = explode("\n", $usbDevices);
    $detected        = false;
    $status          = null;
    $gps             = 'No dongle';
    $toggle          = 'class="input-group-text"';
    foreach ($vidPidList as $vidPid) {
        foreach ($usbDevicesArray as $line) {
            if (strpos($line, $vidPid) !== false) {
                if (preg_match('/' . preg_quote($vidPid, '/') . ' (.+)/', $line, $matches)) {
                    $detected = true;
                    $gpsName  = trim($matches[1]);
                }
            }
        }
    }
    if ($detected) {
        $status = 'background:lightgreen';
        $toggle = 'class="input-group-text collapsed dropdown-toggle" role="button"';
        $gps    = 'Connected';
    }
    $data = '<div class="input-group mb-2">
        <span ' . $toggle . ' data-bs-toggle="collapse" data-bs-target="#gps" aria-expanded="false" aria-controls="gps" style="width: 6.5rem;' . $status . '">GPS</span>
        <input type="text" class="form-control" placeholder="' . $gps . '" readonly>
    </div>';
    $data .= ($detected) ? '<div id="gps" class="accordion-collapse collapse">
            <div class="accordion-body">
                <div class="input-group mb-1">
                <span class="input-group-text">Device</span>
                <span class="input-group-text">' . $gpsName . '</span>
            </div>
        </div>
    </div>' : null;
    return $data;
}

/* GPIO Status(es)*/
function gpioStatus($ajax = 0)
{
    $rxGPIO  = 'gpio10';
    $txGPIO  = 'gpio7';
    $cfgFile = '/opt/rolink/conf/rolink.conf';
    if (is_file($cfgFile)) {
        $data = file_get_contents($cfgFile);
        preg_match('/(GPIO_SQL_PIN=)(\S+)/', $data, $rx);
        preg_match('/(PTT_PIN=)(\S+)/', $data, $tx);
        // Check if the PCB board is supported
        if ($rx[2] != $rxGPIO || $tx[2] != $txGPIO) {
            return;
        }

    } else {
        return;
    }
    $gpioPaths = array(
        'rx'  => '/sys/class/gpio/' . $rxGPIO . '/value',
        'tx'  => '/sys/class/gpio/' . $txGPIO . '/value',
        'fan' => '/sys/class/gpio/gpio6/value',
    );
    $data = array();
    foreach ($gpioPaths as $key => $path) {
        $value      = trim(exec('/usr/bin/cat ' . $path));
        $data[$key] = ($value === '0' || $value === '1') ? $value : '0';
    }
    $jsonData = json_encode($data);
    if ($ajax) {
        return $jsonData;
    }
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">GPIO Status</span>
        <input id="gpioRx" type="text" class="form-control text-center" placeholder="..." readonly>
        <input id="gpioTx" type="text" class="form-control text-center" placeholder="..." readonly>
        <input id="gpioFan" type="text" class="form-control text-center" placeholder="..." readonly>
    </div>';
}

/* Get IP(s) */
function networking()
{
    $interfaces = array(
        'eth0'  => 'LAN',
        'wlan0' => 'WLAN',
        'usb0'  => '4G/LTE',
        'ppp0'  => 'PPTP',
    );
    $returnData = '';
    foreach ($interfaces as $interface => $name) {
        $data = [];
        exec("/usr/bin/ip addr show dev $interface 2>/dev/null| /usr/bin/grep 'inet' | /usr/bin/grep -oE '([0-9]{1,3}\.){3}[0-9]{1,3}' | head -n 1", $data);
        $ip = (empty($data) || preg_match('/^169\.254/', $data[0]) !== 0) ? '' : $data[0];
        if (!empty($ip)) {
            $returnData .= '<div class="input-group mb-2">
                <span class="input-group-text" style="width: 6.5rem;">' . $name . ' IP</span>
                <input type="text" class="form-control" placeholder="' . $ip . '" readonly>
            </div>';
        }
    }
    return $returnData;
}

/* Display SA818 tty port & firmware */
function sa818()
{
    $status  = null;
    $version = version();
    if (!$version || $version['date'] < 20230126) {
        return;
    }
    $sa818    = 'Not detected';
    $toggle   = 'class="input-group-text"';
    $detected = false;
    $saDetect = sa8x8Detect();
    if (is_array($saDetect)) {
        $status   = 'background:lightgreen';
        $toggle   = 'class="input-group-text collapsed dropdown-toggle" role="button"';
        $detected = true;
        $sa818    = 'Detected';
    }
    $data = '<div class="input-group mb-2">
        <span ' . $toggle . ' data-bs-toggle="collapse" data-bs-target="#sa818" aria-expanded="false" aria-controls="sa818" style="width: 6.5rem;' . $status . '">SA818</span>
        <input type="text" class="form-control" placeholder="' . $sa818 . '" readonly>
    </div>';
    $data .= ($detected) ? '<div id="sa818" class="accordion-collapse collapse">
        <div class="accordion-body">
            <div class="input-group mb-1">
                <span class="input-group-text" style="width: 6rem;">Serial Port</span>
                <span class="input-group-text" style="width: 8rem;">' . $saDetect['port'] . '</span>
            </div>
            <div class="input-group mb-1">
                <span class="input-group-text" style="width: 6rem;">Firmware</span>
                <span class="input-group-text" style="width: 8rem;">' . str_replace("+VERSION:", "", trim($saDetect['version'])) . '</span>
            </div>
        </div>
    </div>' : null;
    return $data;
}

/* Get Hostname */
function hostName()
{
    return '<div class="input-group mb-2">
        <button data-bs-toggle="tooltip" title="Click to change the current hostname to the value declared on SVXLink page as <b>Callsign (Beacon)</b>" class="btn btn-dark" style="width: 6.5rem;" type="button" id="switchHostName">Host Name</button>
        <input type="text" class="form-control" placeholder="' . gethostname() . '" readonly>
    </div>';
}

/* Uptime */
function getUpTime()
{
    exec("/usr/bin/uptime -p", $reply);
    $result = (empty($reply)) ? 'Not available' : substr($reply[0], 3);
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">Uptime</span>
        <input type="text" class="form-control" placeholder="' . $result . '" readonly>
    </div>';
}

/* CPU Load & Temp */
function getCpuStats($ajax = 0)
{
    $config      = include __DIR__ . '/../config.php';
    $avgLoad     = $cpuTemp     = '...';
    $tempWarning = null;
    if ($ajax) {
        $cpuLoad     = getServerLoad();
        $avgLoad     = (is_null($cpuLoad)) ? 'N/A' : number_format($cpuLoad, 2) . "%";
        $thermalZone = file('/sys/devices/virtual/thermal/thermal_zone0/temp', FILE_IGNORE_NEW_LINES);
        $rawTemp     = ($config['cfgTempOffset'] == 'true') ? $thermalZone[0] + 38000 : $thermalZone[0];
        $cpuTemp     = substr($rawTemp, 0, -3);
        $tempWarning = ($cpuTemp > 60) ? 'bg-warning text-dark' : '';
        $svxState    = getSVXLinkStatus(1);
        return json_encode(array($avgLoad, $cpuTemp . '℃', $tempWarning, $svxState));
    }
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">CPU</span>
        <input id="cpuLoad" type="text" class="form-control text-center" placeholder="' . $avgLoad . '" readonly>
        <input id="cpuTemp" type="text" class="form-control text-center ' . $tempWarning . '" placeholder="' . $cpuTemp . '" readonly>
    </div>';
}

function getServerLoad()
{
    if (is_readable("/proc/stat")) {
        $statData1 = $statData2 = null;
        for ($i = 0; $i < 2; $i++) {
            $stats = file_get_contents("/proc/stat");
            if ($stats !== false) {
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
                $stats = str_replace(["\r\n", "\n\r", "\r"], "\n", $stats);
                $stats = explode("\n", $stats);
                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));
                    if (count($statLineData) >= 5 && $statLineData[0] === "cpu") {
                        if ($i === 0) {
                            $statData1 = array_slice($statLineData, 1, 4);
                        } else {
                            $statData2 = array_slice($statLineData, 1, 4);
                        }
                        break;
                    }
                }
            }
            sleep(1);
        }
        if (!is_null($statData1) && !is_null($statData2)) {
            $delta = array_map(function ($a, $b) {
                return $b - $a;
            }, $statData1, $statData2);
            $cpuTime = array_sum($delta);
            return 100 - ($delta[3] * 100 / $cpuTime);
        }
    }
    return null;
}

/* Retreive SSID (if connected) */
function getSSID()
{
    exec('/sbin/iwgetid --raw', $reply);
    if (isset($reply[0])) {
        $wifiStatus = $reply[0];
        $wifiMode   = 'WLAN SSID';
    } else {
        #exec('/usr/bin/systemctl is-active hostapd', $mode);
        #$wifiStatus = ($mode[0] == 'active') ? 'Hotspot' : 'Not associated';
        # ToDo : AutoHotspot
        $wifiStatus = 'Not associated';
        $wifiMode   = 'Wi-Fi mode';
    }
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">' . $wifiMode . '</span>
        <input type="text" class="form-control" placeholder="' . $wifiStatus . '" readonly>
    </div>';
}

/* Get Public IP */
function getPublicIP()
{
    $ip     = 'Not available';
    $status = 'color:white;background:red';
    $toggle = 'class="input-group-text" role="button"';
    $gotIP  = getExtIp();
    if ($gotIP) {
        $ip     = $gotIP;
        $status = 'background:lightgreen';
        $toggle = 'class="input-group-text collapsed dropdown-toggle" role="button"';
    }
    $data = '<div class="input-group mb-2">
        <span data-bs-toggle="tooltip" title="Click to reveal the <b>Network Performance</b> tool">
            <span ' . $toggle . ' data-bs-toggle="collapse" data-bs-target="#netPerf" aria-expanded="false" aria-controls="netPerf" style="width: 6.5rem;' . $status . '">External IP</span>
        </span>
        <input type="text" class="form-control" placeholder="' . $ip . '" readonly>
    </div>';
    $data .= ($gotIP) ? '<div id="netPerf" class="accordion-collapse collapse">
        <div class="accordion-body">
            <div class="row">
                <div class="col text-center pb-2">
                    <button type="button" class="btn btn-info col-sm px-2" id="latencyCheck"><i class="icon-timer px-2" aria-hidden="true"></i>Run test</button>
                </div>
            </div>
            <div class="row">
                <div class="col-sm">
                    <label for="tcp_bw" class="form-control-sm col-form-label">TCP Bandwidth</label>
                    <input id="tcp_bw" type="text" class="form-control text-center" placeholder="..." readonly>
                </div>
                <div class="col-sm">
                    <label for="tcp_lat" class="form-control-sm col-form-label">TCP Latency</label>
                    <input id="tcp_lat" type="text" class="form-control text-center" placeholder="..." readonly>
                </div>
                <div class="col-sm">
                    <label for="udp_sbw" class="form-control-sm col-form-label">UDP TX Bandwidth</label>
                    <input id="udp_sbw" type="text" class="form-control text-center" placeholder="..." readonly>
                </div>
                <div class="col-sm">
                    <label for="udp_rbw" class="form-control-sm col-form-label">UDP RX Bandwidth</label>
                    <input id="udp_rbw" type="text" class="form-control text-center" placeholder="..." readonly>
                </div>
                <div class="col-sm">
                    <label for="udp_lat" class="form-control-sm col-form-label">UDP Latency</label>
                    <input id="udp_lat" type="text" class="form-control text-center" placeholder="..." readonly>
                </div>
            </div>
            <div class="pt-3 text-center">
                <small class="d-inline-flex px-2 py-1 font-monospace text-muted border rounded-3">Optimal performance is achieved when bandwidth is higher than 350 KB/sec and latency is lower than 150 ms</small>
            </div>
        </div>
    </div>' : null;
    return $data;
}

/* Get SVXLink status */
function getSVXLinkStatus($ext = 0)
{
    $config = include __DIR__ . '/../config.php';
    exec("/usr/bin/pgrep svxlink", $reply);
    if ($ext == 1) {
        return ((empty($reply)) ? false : $reply[0]);
    }

    $result      = (empty($reply)) ? 'Not running' : 'Running (' . $reply[0] . ')';
    $status      = (empty($reply)) ? 'width:6.5rem;' : 'width:6.5rem;background:lightgreen;';
    $dtmfTrigger = ($config['cfgDTMF'] == 'true' && $result != 'Not running') ? '<span data-bs-toggle="tooltip" title="Click to display the <b>DTMF Sender Tool</b> and send commands to the SVXLink application. Usefull when you don\'t have a radio with the DTMF feature."><button id="dtmf" data-bs-toggle="modal" data-bs-target="#dtmfModal" class="input-group-text btn btn-secondary" type="button">#</button></span>' : null;
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="' . $status . '">SVXLink</span>
        <input id="svxStatus" type="text" class="form-control" placeholder="' . $result . '" readonly>'
        . $dtmfTrigger .
        '</div>';
}

/* Get Reflector address */
function getReflector($ext = 0)
{
    $config    = include __DIR__ . '/../config.php';
    $cfgFile   = '/opt/rolink/conf/rolink.conf';
    $conStatus = $stateColor = $prevStatus = '';
    if (is_file($cfgFile)) {
        preg_match('/HOST=(\S+)/', file_get_contents($cfgFile), $reply);
    }
    $refHost = (!empty($reply)) ? $reply[1] : 'Not available';
    preg_match_all('/(Could not open GPIO|Disconnected|established)/', file_get_contents('/tmp/svxlink.log'), $logData);
    if (!empty($logData) && getSVXLinkStatus(1)) {
        $statusData = (isset($logData[0][array_key_last($logData[0]) - 1])) ? $logData[0][array_key_last($logData[0]) - 1] : null;
        $prevStatus = (count($logData) > 1) ? $statusData : null;
        $conStatus  = ($prevStatus == 'Could not open GPIO') ? 'GPIO' : $logData[0][array_key_last($logData[0])];
        switch ($conStatus) {
            case "established":
                $stateColor = 'background:lightgreen;';
                break;
            case "Disconnected":
                $stateColor = 'background:tomato;';
                break;
            case "GPIO":
                $stateColor = 'background:red;';
                $refHost    = 'Check your GPIO!';
                break;
        }
    }
    $showNodes = ($config['cfgRefNodes'] == 'true' && $conStatus == 'established') ? ' collapsed dropdown-toggle" role="button" data-bs-toggle="collapse" data-bs-target="#refStations" aria-expanded="false" aria-controls="refStations"' : '"';
    return '<div class="input-group mb-2">
        <span class="input-group-text' . $showNodes . ' style="width: 6.5rem;' . $stateColor . '">Reflector</span>
        <input type="text" class="form-control" placeholder="' . $refHost . '" readonly>
    </div>';
}

/* Get Reflector connected nodes */
function getRefNodes()
{
    $status = getSVXLinkStatus(1);
    if (!$status) {
        return false;
    }

    $logFilePath       = '/tmp/svxlink.log';
    $baseCacheFileName = '/tmp/nodes_cache_' . $status . '.txt';
    if (file_exists($baseCacheFileName)) {
        return file_get_contents($baseCacheFileName);
    }
    // Delete other cache files
    foreach (glob('/tmp/nodes_cache_*.txt') as $file) {
        if ($file !== $baseCacheFileName) {
            unlink($file);
        }
    }
    $logLines = file($logFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($logLines)) {
        return false;
    }
    $stationHTML = '<div id="refStations" class="accordion-collapse collapse">
        <div class="accordion-body">
            <div class="row">' . PHP_EOL;
    foreach ($logLines as $line) {
        if (preg_match('/Connected nodes:\s(.*)/', $line, $matches)) {
            $nodes = explode(', ', $matches[1]);
            natsort($nodes);
            foreach ($nodes as $node) {
                $typeBackground = 'danger';
                if (strpos($node, '-P') !== false) {
                    $typeBackground = 'primary';
                }

                if (strpos($node, '-M') !== false) {
                    $typeBackground = 'warning';
                }

                $stationHTML .= '<div class="col col-lg-2 badge badge-' . $typeBackground . ' m-1" style="font-weight: 400;">' . $node . '</div>' . PHP_EOL;
            }
            break;
        }
    }
    $stationHTML .= '</div>
        </div>
        </div>' . PHP_EOL;
    file_put_contents($baseCacheFileName, $stationHTML);
    return $stationHTML;
}

/* Get SVX Callsign    */
function getCallSign()
{
    global $cfgFile;
    if (is_file($cfgFile)) {
        preg_match('/(CALLSIGN=")(\S+)"/', file_get_contents($cfgFile), $reply);
    }
    $callsign = (!empty($reply)) ? $reply[2] : 'Not available';
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">Callsign</span>
        <input type="text" class="form-control" placeholder="' . $callsign . '" readonly>
    </div>';
}

/* Get free space */
function getFreeSpace()
{
    $expand = null;
    $status = 'background:lightgreen';
    $bytes  = disk_free_space('/');
    $suffix = array('B', 'KB', 'MB', 'GB');
    $scale  = min((int) log($bytes, 1024), count($suffix) - 1);
    $space  = sprintf('%1.2f', $bytes / pow(1024, $scale)) . ' ' . $suffix[$scale];
    if ($bytes < 104857600) {
        $status = 'background:red;color:white';
    } elseif ($bytes < 262144000) {
        $status = 'background:orange;color:white';
    }
    /* Determine if it's an image based install */
    exec('/usr/bin/df /', $drive);
    preg_match('/mmcblk0p1\s+(\d+)/m', $drive[1], $size);
    if ($size[1] < 3145728) {
        $status = 'background:yellow;color:black';
        $expand = '<button type="button" id="expandFS" class="btn btn-danger">&#8633;</button>';
    }
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;' . $status . '">Free Space</span>
        <input type="text" class="form-control" placeholder="' . $space . '" readonly>
        ' . $expand . '
    </div>';
}

/* Get kernel & release version */
function getKernel()
{
    preg_match('/VERSION_CODENAME=(\S+)/', file_get_contents('/etc/os-release'), $reply);
    $kernel = str_replace(['-sunxi', '-current'], '', posix_uname()['release']);
    return '<div class="input-group mb-2">
        <span class="input-group-text" style="width: 6.5rem;">Kernel</span>
        <input type="text" class="form-control" placeholder="' . $kernel . ' (' . $reply[1] . ')" readonly>
    </div>';
}

/* File System status */
function getFileSystem()
{
    if (!preg_match('/ro,ro/', file_get_contents('/etc/fstab'))) {
        return;
    }

    exec('/usr/bin/cat /proc/mounts | grep -Po \'(?<=(ext4\s)).*(?=,noatime)\'', $fileSystemStatus);
    $stateFS      = ($fileSystemStatus[0] == 'rw') ? 'Read/Write' : 'Read-only';
    $stateFSColor = ($fileSystemStatus[0] == 'rw') ? 'background:red;color: white;' : 'background:lightgreen;';
    return '<div class="input-group mb-2">
        <button data-bs-toggle="tooltip" title="Click to toggle between <b>Read-Only</b> and <b>Read/Write</b> state" class="btn" style="' . $stateFSColor . 'width: 6.5rem;" value="' . $fileSystemStatus[0] . '" type="button" id="changeFS">File system</button>
        <input type="text" class="form-control" placeholder="' . $stateFS . '" readonly>
    </div>';
}

/* Version check */
function getRemoteVersion()
{
    global $remoteVerUrl;

    $version = version();
    if (!$version) {
        return;
    }

    $remoteData    = false;
    $cachedVersion = (isset($_COOKIE["remote_version"])) ? (int) $_COOKIE["remote_version"] : false;
    $notify        = 'width: 6.5rem';
    if ($cachedVersion) {
        $result = ($cachedVersion > $version['date']) ? 'Update available (' . $cachedVersion . ')' : $version['number'] . ' (' . $version['date'] . ')';
        $notify = ($cachedVersion > $version['date']) ? 'width:6.5rem;border-left-width:thick;border-left-color:red' : $notify;
    } else {
        $gotIP = getExtIp();
        if ($gotIP) {
            $remoteData = file_get_contents($remoteVerUrl);
        }
    }
    if ($remoteData) {
        $remoteVersion = explode('|', $remoteData);
        $result        = ((int) $remoteVersion[0] > $version['date']) ? 'Update available (' . $remoteVersion[0] . ')' : $version['number'] . ' (' . $version['date'] . ')';
        $notify        = ((int) $remoteVersion[0] > $version['date']) ? 'width:6.5rem;border-left-width:thick;border-left-color:red' : $notify;
        setcookie("remote_version", $remoteVersion[0], time() + 60 * 60 * 24);
    } elseif (!isset($result)) {
        $result = 'Unavailable';
    }
    return '<div class="input-group mb-2">
        <span data-bs-toggle="tooltip" title="Local version : ' . $version['date'] . '</br>Remote version : ' .
        (isset($remoteVersion[0]) ? $remoteVersion[0] : $cachedVersion) . '" class="input-group-text" style="' . $notify . '">Version</span>
        <input type="text" class="form-control" placeholder="' . $result . '" readonly>
    </div>';
}

/* DTMF commands sender */
function dtmfSender()
{
    return '<div class="modal fade" id="dtmfModal" tabindex="-1" aria-labelledby="dtmfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dtmfModalLabel">DTMF Sender</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group flex-nowrap">
                    <span data-bs-toggle="tooltip" title="DTMF commands to be sent to SVXLink application." class="input-group-text" id="addon-wrapping">Command:</span>
                    <input type="tel" id="dtmfCommand" class="form-control" aria-label="Command" aria-describedby="addon-wrapping">
                </div>
                <div class="alert alert-success m-1" id="dtmfConsole" role="alert" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <div class="col">
                    <button data-bs-toggle="tooltip" title="Enable the link and pass the audio to and from the reflector you are connected to." id="sendDTMF_EnableLink" type="button" class="btn btn-info mb-1" value="551#">Enable &#128279;</button>
                    <button data-bs-toggle="tooltip" title="Disable the link and stop audio to and from the reflector you are connected to." id="sendDTMF_DisableLink" type="button" class="btn btn-info mb-1" value="55#">Disable &#128279;</button>
                    <button data-bs-toggle="tooltip" title="Switch to Talk Group 9" id="sendDTMF_TG9" type="button" class="btn btn-info mb-1" value="5519#">TG#9</button>
                    <button data-bs-toggle="tooltip" title="Switch to Talk Group 226" id="sendDTMF_TG226" type="button" class="btn btn-info mb-1" value="551226#">TG#226</button>
                    <button data-bs-toggle="tooltip" title="Enable the parrot and test your audio. <em>Note : After 60 seconds of inactivity the parrot will be disabled and audio will resume the normal flow." id="sendDTMF_ParrotOn" type="button" class="btn btn-info mb-1" value="1#">Parrot On</button>
                    <button data-bs-toggle="tooltip" title="Disable the parrot module (if already active)" id="sendDTMF_ParrotOff" type="button" class="btn btn-info mb-1" value="#">Parrot Off</button>
                </div>
                <button data-bs-toggle="tooltip" title="Click to send the data from the <b>Command</b> input field" data-bs-placement="bottom" id="sendDTMF" type="button" class="btn btn-danger btn-lg">Send</button>
            </div>
        </div>
    </div>
</div>' . PHP_EOL;
}
