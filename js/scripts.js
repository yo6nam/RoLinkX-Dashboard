/*!
* Start Bootstrap - Simple Sidebar v6.0.3 (https://startbootstrap.com/template/simple-sidebar)
* Copyright 2013-2021 Start Bootstrap
* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-simple-sidebar/blob/master/LICENSE)
*/
// 
// Scripts
// 

window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment Below to persist sidebar toggle between refreshes
        // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        //     document.body.classList.toggle('sb-sidenav-toggled');
        // }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }
});

/*
*   RoLinkX Dashboard v0.1a
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
* jQuery stuff
*/

$(document).ready(function() {
	
	// SA818 Programming
	$("#programm").click(function() {
		$('#sysmsg').iziModal('destroy');
		$('#sysmsg').iziModal({
				title: 'Sending data, please wait...',
				width: "40vh",
				icon: "icon-warning",
				headerColor: "#941919",
				autoOpen: 5,
				timeout: 12000,
				timeoutProgressbar: true,
				closeOnEscape: false,
				closeButton: false,
				overlay: true
		});
		$('#programm').prop('disabled', true);
		$('#programm').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#programm').prop('disabled', false);
				$('#programm').fadeTo("fast", 1);
			}, 12000);
		$.ajax({
			type: 'POST',
			url: "ajax/trx.php",
			data: {	grp: $('#sa_grp').val(),
					dev: $('#sa_dev').val(),
					tpl: $('#sa_tpl').val(),
					sql: $('#sa_sql').val(),
					vol: $('#sa_vol').val(),
					flt: $('#sa_flt').val()
			},
			success: function(data) {
				if (data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
						icon: "icon-check",
						headerColor: "#00af66",
						timeout: 8000,
						timeoutProgressbar: true,
						transitionIn: "fadeInUp",
						transitionOut: "fadeOutDown",
						bottom: 0,
						zindex:1031,
						autoOpen: 50
					});
				}
			}
		});
	});
	
	// SVXLink config
	$("#savesvxcfg").click(function() {
		$('#savesvxcfg').prop('disabled', true);
		$('#savesvxcfg').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#savesvxcfg').prop('disabled', false);
				$('#savesvxcfg').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/svx.php",
			data: {	ref: $('#svx_ref').val(),
					prt: $('#svx_prt').val(),
					cal: $('#svx_cal').val(),
					key: $('#svx_key').val(),
					clb: $('#svx_clb').val(),
					sid: $('#svx_sid').val(),
					lid: $('#svx_lid').val()
			},
			success: function(data) {
					if (data) {
						$('#sysmsg').iziModal('destroy');
						$('#sysmsg').iziModal({ 
							title: data,
    						icon: "icon-check",
    						headerColor: "#00af66",
    						timeout: 5000,
    						timeoutProgressbar: true,
    						transitionIn: "fadeInUp",
    						transitionOut: "fadeOutDown",
    						bottom: 0,
    						autoOpen: 50,
    						zindex:1031,
    						overlay: false
						});
      					setTimeout(function(){
      						location.reload(true);
      					}, 7000);
      				}
			}
		});
	});
	
	// WiFi config
	$("#savewifi").click(function() {
		$('#savewifi').prop('disabled', true);
		$('#savewifi').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#savewifi').prop('disabled', false);
				$('#savewifi').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/wifi.php",
			data: {	wn1: $('#wlan_network_1').val(),
					wk1: $('#wlan_authkey_1').val(),
					wn2: $('#wlan_network_2').val(),
					wk2: $('#wlan_authkey_2').val(),
					wn3: $('#wlan_network_3').val(),
					wk3: $('#wlan_authkey_3').val()
			},
			success: function(data) {
					if (data) {
						$('#sysmsg').iziModal('destroy');
						$('#sysmsg').iziModal({ 
							title: data,
    						icon: "icon-check",
    						headerColor: "#00af66",
    						timeout: 5000,
    						timeoutProgressbar: true,
    						transitionIn: "fadeInUp",
    						transitionOut: "fadeOutDown",
    						bottom: 0,
    						autoOpen: 50,
    						zindex:1031,
    						overlay: false
						});
      					setTimeout(function(){
      						location.reload(true);
      					}, 7000);
      				}
			}
		});
	});

	/* System functions*/
	
	// Reboot OS
	$("#reboot").click(function() {
		$('#reboot').prop('disabled', true);
		$('#reboot').fadeTo("fast", 0.15);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	reboot: 1 }
		});
	});
	
	// Restart Wi-Fi
	$("#rewifi").click(function() {
		$('#rewifi').prop('disabled', true);
		$('#rewifi').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#rewifi').prop('disabled', false);
				$('#rewifi').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	rewifi: 1 },
			success: function(data){
				if(data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: 'Wi-Fi service restarted',
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 5000,
    					timeoutProgressbar: true,
    					transitionIn: "fadeInUp",
    					transitionOut: "fadeOutDown",
    					bottom: 0,
    					autoOpen: 50,
    					zindex:1031,
    					overlay: false
					})
				}
			}
		});
	});
	
	// Restart SVXLink service
	$("#resvx").click(function() {
		$('#resvx').prop('disabled', true);
		$('#resvx').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#resvx').prop('disabled', false);
				$('#resvx').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	resvx: 1 },
			success: function(data){
				if(data == true){
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: 'RoLink service has been (re)started',
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 5000,
    					timeoutProgressbar: true,
    					transitionIn: "fadeInUp",
    					transitionOut: "fadeOutDown",
    					bottom: 0,
    					autoOpen: 50,
    					zindex:1031,
    					overlay: false
					});
      				setTimeout(function(){
      					$.ajax({ type: 'GET', url: "includes/status.php?svxStatus",
							success: function(data){
								$("#svxStatus").attr("placeholder", data).val("").focus().blur();
								$("#refContainer").load('includes/status.php?svxReflector');
							}
      					});
      				}, 3500);
      			}
			}
		});
	});
	
	// Stop SVXLink service
	$("#endsvx").click(function() {
		$('#endsvx').prop('disabled', true);
		$('#endsvx').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#endsvx').prop('disabled', false);
				$('#endsvx').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	endsvx: 1 },
			success: function(data){
				if(data == true){
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: 'RoLink service has been stopped',
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 5000,
    					timeoutProgressbar: true,
    					transitionIn: "fadeInUp",
    					transitionOut: "fadeOutDown",
    					bottom: 0,
    					autoOpen: 50,
    					zindex:1031,
    					overlay: false
					});
					setTimeout(function(){
      					$.ajax({ type: 'GET', url: "includes/status.php?svxStatus",
							success: function(data){
								$("#svxStatus").attr("placeholder", data).val("").focus().blur();
								$("#refContainer").load('includes/status.php?svxReflector');
							}
      					});
      				}, 3500);
      			}
			}
		});
	});
	
	// Switch Host Name (default -> callsign)
	$("#switchHostName").click(function() {
		$('#switchHostName').prop('disabled', true);
		$('#switchHostName').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#switchHostName').prop('disabled', false);
				$('#switchHostName').fadeTo("fast", 1);
			}, 1000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	switchHostName: 1 },
			success: function(data) {
				if(data == 'ToDo...'){
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 5000,
    					timeoutProgressbar: true,
    					transitionIn: "fadeInUp",
    					transitionOut: "fadeOutDown",
    					bottom: 0,
    					autoOpen: 50,
    					zindex:1031,
    					overlay: false
					});
      				setTimeout(function(){
      					location.reload();
      				}, 6000);
      			}
			}
		});
	});
});