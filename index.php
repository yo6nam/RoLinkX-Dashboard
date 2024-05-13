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
 * Index page
 */

// Password protection
if (is_file(__DIR__ . '/assets/pwd')) {
    $password = file_get_contents(__DIR__ . '/assets/pwd');
    $hash     = md5($password);
    if (!isset($_COOKIE[$hash]) && !empty($password)) {
        require_once __DIR__ . '/includes/access.php';
    }
}
$pages = array("wifi", "svx", "sa", "log", "aprs", "tty", "cfg");
$page  = (null !== filter_input(INPUT_GET, 'p', FILTER_SANITIZE_SPECIAL_CHARS)) ? $_GET['p'] : '';

// Common functions
include __DIR__ . '/includes/functions.php';

// Events
$version    = version();
$eventsData = 'var events=0';
$ajaxData   = 'var auto_refresh = setInterval( function () { cpuData(); gpioStatus(); }, 3000);';
if ($version && $version['date'] > 20231120) {
    $ajaxData   = '';
    $eventsData = 'var events=1; var timeOutTimer=180;';
}

// Detect mobiles
require_once __DIR__ . '/includes/Mobile_Detect.php';
$detect = new Mobile_Detect();

if (in_array($page, $pages)) {
    include __DIR__ . '/includes/forms.php';
} else {
    $config = include 'config.php';
    include __DIR__ . '/includes/status.php';
}

$rolink = (is_file($cfgFile)) ? true : false;

switch ($page) {
    case "wifi":
        $htmlOutput = wifiForm();
        break;
    case "svx":
        $htmlOutput = svxForm();
        break;
    case "sa":
        $htmlOutput = sa818Form();
        break;
    case "aprs":
        $htmlOutput    = aprsForm();
        $extraResource = '<link href="https://cdn.jsdelivr.net/npm/ol@v8.1.0/ol.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/ol@v8.1.0/dist/ol.js"></script>';
        break;
    case "log":
        $htmlOutput = logsForm();
        break;
    case "tty":
        $htmlOutput = ttyForm();
        break;
    case "cfg":
        $htmlOutput = cfgForm();
        break;
    default:
        $svxAction  = (getSVXLinkStatus(1)) ? 'Restart' : 'Start';
        $htmlOutput = '<h4 class="m-2 mt-2 alert alert-success fw-bold talker">' . ($detect->isMobile() ? '&nbsp;' : 'Status') . '<span id="onair" class="badge position-absolute top-50 start-50 translate-middle"></span></h4>
    <div class="card m-2">
    <div class="card-body">';
        $htmlOutput .= ($config['cfgHostname'] == 'true' && $rolink) ? hostName() : null;
        $htmlOutput .= ($config['cfgUptime'] == 'true') ? getUpTime() : null;
        $htmlOutput .= ($config['cfgCpuStats'] == 'true') ? getCpuStats() : null;
        $htmlOutput .= ($config['cfgNetworking'] == 'true') ? networking() : null;
        $htmlOutput .= ($config['cfgSsid'] == 'true') ? getSSID() : null;
        $htmlOutput .= ($config['cfgPublicIp'] == 'true') ? getPublicIP() : null;
        $htmlOutput .= gpioStatus();
        $htmlOutput .= ($config['cfgDetectSa'] == 'true') ? sa818() : null;
        $htmlOutput .= ($config['cfgSvxStatus'] == 'true' && $rolink) ? '<div id="svxStatus">' . getSVXLinkStatus() . '</div>' : null;
        $htmlOutput .= '<div id="refContainer">' . getReflector() . '</div>';
        $htmlOutput .= ($config['cfgRefNodes'] == 'true' && $rolink) ? getRefNodes() : null;
        $htmlOutput .= ($config['cfgCallsign'] == 'true' && $rolink) ? getCallSign() . PHP_EOL : null;
        $htmlOutput .= ($rolink) ? getGPSDongle() . PHP_EOL : null;
        $htmlOutput .= ($config['cfgKernel'] == 'true') ? getKernel() : null;
        $htmlOutput .= ($config['cfgFreeSpace'] == 'true') ? getFreeSpace() : null;
        $htmlOutput .= ($rolink) ? getFileSystem() . PHP_EOL : null;
        $htmlOutput .= ($rolink) ? getRemoteVersion() . PHP_EOL : null;
        $htmlOutput .= ($rolink) ? '<div class="d-grid gap-2 col-7 mx-auto">
    <button id="resvx" class="btn btn-warning btn-lg">' . $svxAction . ' RoLink</button>
    <button id="endsvx" class="btn btn-dark btn-lg">Stop RoLink</button>
    <button id="reboot" class="btn btn-primary btn-lg">Reboot</button>
    <button id="halt" class="btn btn-danger btn-lg">Power Off</button>
    </div>
    </div>
    </div>' : null;
        $htmlOutput .= ($config['cfgDTMF'] == 'true') ? dtmfSender() . PHP_EOL : null;
        $ajax = ($config['cfgCpuStats'] == 'true') ? "$(document).ready(function () {
        cpuData();
        gpioStatus();
        $ajaxData
    });" : null;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="RoLinkX Dashboard" />
        <meta name="author" content="YO6NAM" />
        <title>RoLinkX Dashboard - <?php echo gethostname(); ?></title>
        <link rel="apple-touch-icon" sizes="57x57" href="assets/fav/apple-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="60x60" href="assets/fav/apple-icon-60x60.png">
        <link rel="apple-touch-icon" sizes="72x72" href="assets/fav/apple-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="76x76" href="assets/fav/apple-icon-76x76.png">
        <link rel="apple-touch-icon" sizes="114x114" href="assets/fav/apple-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="120x120" href="assets/fav/apple-icon-120x120.png">
        <link rel="apple-touch-icon" sizes="144x144" href="assets/fav/apple-icon-144x144.png">
        <link rel="apple-touch-icon" sizes="152x152" href="assets/fav/apple-icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="assets/fav/apple-icon-180x180.png">
        <link rel="icon" type="image/png" sizes="192x192"  href="assets/fav/android-icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="assets/fav/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="assets/fav/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="16x16" href="assets/fav/favicon-16x16.png">
        <link rel="manifest" href="manifest.json">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="ms-icon-144x144.png">
        <meta name="theme-color" content="#ffffff">
        <link href="css/styles.css?_=<?php echo cacheBuster('css/styles.css'); ?>" rel="stylesheet" />
        <link href="css/select2.min.css" rel="stylesheet" />
        <link href="css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
        <link href="css/jquery.toast.min.css" rel="stylesheet" />
        <link href="css/iziModal.min.css" rel="stylesheet" />
        <?php echo (isset($extraResource)) ? $extraResource . PHP_EOL : null; ?>
    </head>
    <body>
        <div class="d-flex" id="wrapper">
            <div class="border-end bg-white" id="sidebar-wrapper">
                <div class="sidebar-heading border-bottom bg-light fw-bold">
                    <a href="./" class="text-decoration-none" style="color:purple">
                        <i class="icon-dashboard" style="font-size:26px;color:purple;vertical-align: middle;padding: 0 4px 4px 0;"></i>RoLinkX Dashboard
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    <a class="<?php echo ($page == '') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./">Status</a>
                    <a class="<?php echo ($page == 'wifi') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=wifi">WiFi</a>
                    <a class="<?php echo ($page == 'svx') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=svx">SVXLink</a>
                    <a class="<?php echo ($page == 'sa') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=sa">SA818</a>
                    <a class="<?php echo ($page == 'aprs') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=aprs">APRS</a>
                    <a class="<?php echo ($page == 'log') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=log">Logs</a>
                    <a class="<?php echo ($page == 'tty') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=tty">Terminal</a>
                    <a class="<?php echo ($page == 'cfg') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=cfg">Config</a>
                </div>
            </div>
            <div id="page-content-wrapper">
                <nav <?php echo ($detect->isMobile() ? '' : 'style="display: none !important" '); ?>class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                        </button>
                        <h1 class="sidebar-heading bg-light fw-light mt-1 text-dark"><a href="./" class="text-decoration-none" style="color:black">RoLinkX Dashboard</a></h1>
                        <i class="icon-dashboard" style="font-size:40px;color:purple"></i>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                                <li class="nav-item"><a class="<?php echo ($page == '') ? 'active p-2' : ''; ?> nav-link" href="./">Status</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'wifi') ? 'active p-2' : ''; ?> nav-link" href="./?p=wifi">WiFi</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'svx') ? 'active p-2' : ''; ?> nav-link" href="./?p=svx">SVXLink</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'sa') ? 'active p-2' : ''; ?> nav-link" href="./?p=sa">SA818</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'aprs') ? 'active p-2' : ''; ?> nav-link" href="./?p=aprs">APRS</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'log') ? 'active p-2' : ''; ?> nav-link" href="./?p=log">Logs</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'tty') ? 'active p-2' : ''; ?> nav-link" href="./?p=tty">Terminal</a></li>
                                <li class="nav-item"><a class="<?php echo ($page == 'cfg') ? 'active p-2' : ''; ?> nav-link" href="./?p=cfg">Config</a></li>
                            </ul>
                        </div>
                    </div>
                </nav>
                <div id='main-content' class="container-fluid mb-5">
                    <?php echo $htmlOutput; ?>
                </div>
            </div>
            <div id="sysmsg"></div>
        </div>
        <footer class="page-footer fixed-bottom font-small bg-light">
            <div class="text-center small p-2">v3.68 © 2024 Copyright <a class="text-primary" target="_blank" href="https://github.com/yo6nam/RoLinkX-Dashboard">Razvan / YO6NAM</a></div>
        </footer>
        <script><?php echo $eventsData; ?></script>
        <script src="js/jquery.js"></script>
        <script src="js/iziModal.min.js"></script>
        <script src="js/bootstrap.js"></script>
        <script src="js/select2.min.js"></script>
        <script src="js/scripts.js?_=<?php echo cacheBuster('js/scripts.js'); ?>"></script>
        <?php echo (isset($ajax)) ? '<script>' . $ajax . '</script>' . PHP_EOL : null; ?>
    </body>
</html>
