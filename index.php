<?php
/*
*   RoLinkX Dashboard v0.8
*   Copyright (C) 2021 by Razvan Marin YO6NAM / www.xpander.ro
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

$htmlOutput = null;
$pages = array("wifi", "svx", "sa", "log", "cfg");
$page = (null !== filter_input(INPUT_GET, 'p', FILTER_SANITIZE_SPECIAL_CHARS)) ? $_GET['p'] : '';

// Detect mobiles
$mobile = (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))) ? '' : 'style="display: none !important" ';

if (in_array($page, $pages)) {
	include __DIR__ . '/includes/forms.php';
} else {
	$config = include 'config.php';
	include __DIR__ . '/includes/status.php';
}

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
  case "log":
    $htmlOutput = logsForm();
    break;
  case "cfg":
    $htmlOutput = cfgForm();
    break;
  default:
	$svxAction = (getSVXLinkStatus(1) == 'Not running') ? 'Start' : 'Restart';
	$htmlOutput = '<h2 class="m-2 mt-2 alert alert-success fw-bold">Status</h2>
	<div class="card m-2">
	<div class="card-body">';
	$htmlOutput .= ($config['cfgHostname'] == 'true') ? hostName() : NULL;
	$htmlOutput .= ($config['cfgUptime'] == 'true') ? getUpTime() : NULL;
	$htmlOutput .= ($config['cfgCpuStats'] == 'true') ? getCpuStats() : NULL;
	$htmlOutput .= ($config['cfgNetworking'] == 'true') ? networking() : NULL;
	$htmlOutput .= ($config['cfgSsid'] == 'true') ? getSSID() : NULL;
	$htmlOutput .= ($config['cfgPublicIp'] == 'true') ? getPublicIP() : NULL;
	$htmlOutput .= ($config['cfgSvxStatus'] == 'true') ? getSVXLinkStatus() : NULL;
	$htmlOutput .= '<div id="refContainer">' . getReflector() . '</div>';
	$htmlOutput .= ($config['cfgRefNodes'] == 'true') ? getRefNodes() : NULL;
	$htmlOutput .= ($config['cfgCallsign'] == 'true') ? getCallSign() . PHP_EOL : NULL;
	$htmlOutput .= '<div class="d-grid gap-2 col-7 mx-auto">
	<button id="halt" class="btn btn-danger btn-lg">Power Off</button>
	<button id="reboot" class="btn btn-primary btn-lg">Reboot</button>
	<button id="rewifi" class="btn btn-info btn-lg">Restart Wi-Fi</button>
	<button id="resvx" class="btn btn-warning btn-lg">'. $svxAction .' RoLink</button>
	<button id="endsvx" class="btn btn-dark btn-lg">Stop RoLink</button>
	</div>
	</div>
	</div>';
	$htmlOutput .= ($config['cfgDTMF'] == 'true') ? dtmfSender() . PHP_EOL : NULL;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>RoLinkX Dashboard</title>
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
		<link rel="manifest" href="/manifest.json">
		<meta name="msapplication-TileColor" content="#ffffff">
		<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
		<meta name="theme-color" content="#ffffff">
        <link href="css/styles.css?cb=20211002" rel="stylesheet" />
    	<link href="css/jquery.toast.min.css" rel="stylesheet" />
    	<link href="css/iziModal.min.css" rel="stylesheet" />
    </head>
	<body>
		<div class="d-flex" id="wrapper">
            <div class="border-end bg-white" id="sidebar-wrapper">
                <div class="sidebar-heading border-bottom bg-light fw-bold"><i class="icon-dashboard" style="font-size:26px;color:purple;vertical-align: middle;padding: 0 4px 4px 0;"></i>RoLinkX Dashboard</div>
                <div class="list-group list-group-flush">
					<a class="<?php echo ($page == '') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./">Status</a>
					<a class="<?php echo ($page == 'wifi') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=wifi">WiFi</a>
					<a class="<?php echo ($page == 'svx') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=svx">SVXLink</a>
					<a class="<?php echo ($page == 'sa') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=sa">SA818</a>
					<a class="<?php echo ($page == 'log') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=log">Logs</a>
					<a class="<?php echo ($page == 'cfg') ? 'active' : ''; ?> list-group-item list-group-item-action list-group-item-light p-3" href="./?p=cfg">Config</a>
                </div>
            </div>
            <div id="page-content-wrapper">
                <nav <?php echo $mobile; ?>class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><h1 class="sidebar-heading bg-light fw-light mt-1 text-dark">RoLinkX Dashboard</h1><i class="icon-dashboard" style="font-size:40px;color:purple"></i>
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
								<li class="nav-item"><a class="<?php echo ($page == '') ? 'active p-2' : ''; ?> nav-link" href="./">Status</a></li>
								<li class="nav-item"><a class="<?php echo ($page == 'wifi') ? 'active p-2' : ''; ?> nav-link" href="./?p=wifi">WiFi</a></li>
								<li class="nav-item"><a class="<?php echo ($page == 'svx') ? 'active p-2' : ''; ?> nav-link" href="./?p=svx">SVXLink</a></li>
								<li class="nav-item"><a class="<?php echo ($page == 'sa') ? 'active p-2' : ''; ?> nav-link" href="./?p=sa">SA818</a></li>
								<li class="nav-item"><a class="<?php echo ($page == 'log') ? 'active p-2' : ''; ?> nav-link" href="./?p=log">Logs</a></li>
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
			<div class="text-center p-2">
v0.8 © 2021 Copyright <a class="text-primary" href="https://www.xpander.ro/">Razvan / YO6NAM</a>
			</div>
		</footer>
        <script src="js/jquery.js"></script>
        <script src="js/iziModal.min.js"></script>
        <script src="js/bootstrap.js"></script>
        <script src="js/scripts.js?cb=20211002"></script>
    </body>
</html>
