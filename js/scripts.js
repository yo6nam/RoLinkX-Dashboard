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
*   RoLinkX Dashboard v1.0
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
				overlayClose: false,
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
			}, 5000);
		$.ajax({
			type: 'POST',
			url: "ajax/svx.php",
			data: {	prn: $('#svx_prn').val(),
					ref: $('#svx_ref').val(),
					prt: $('#svx_prt').val(),
					cal: $('#svx_cal').val(),
					key: $('#svx_key').val(),
					clb: $('#svx_clb').val(),
					vop: $('#svx_vop').val(),
					sid: $('#svx_sid').val(),
					lid: $('#svx_lid').val(),
					cbr: $('#svx_cbr').val(),
					rgr: $('#svx_rgr').val(),
					rxp: $('#svx_rxp').val(),
					txp: $('#svx_txp').val(),
					mtg: $('#svx_mtg').val(),
					tgt: $('#svx_tgt').val(),
					sqd: $('#svx_sqd').val()
			},
			success: function(data) {
					if (data) {
						$('#sysmsg').iziModal('destroy');
						$('#sysmsg').iziModal({ 
							title: data,
    						icon: "icon-check",
    						headerColor: "#00af66",
    						timeout: 3000,
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
      					}, 3200);
      				}
			}
		});
	});

	// SVXLink delete profile
	$("#delsvxprofile").click(function() {
		$.ajax({
			type: 'POST',
			url: "ajax/svx.php",
			data: {	prd: $('#svx_spn').val()
			},
			success: function(data) {
					if (data) {
						$('#sysmsg').iziModal('destroy');
						$('#sysmsg').iziModal({ 
							title: data,
    						icon: "icon-check",
    						headerColor: "#00af66",
    						timeout: 3000,
    						timeoutProgressbar: true,
    						transitionIn: "fadeInUp",
    						transitionOut: "fadeOutDown",
    						bottom: 0,
    						autoOpen: 50,
    						zindex:1031,
    						overlay: false
						});
      				}
      				setTimeout(function(){
      						location.reload(true);
					}, 3200);
			}
		});
	});

	// Load selected SVX profile and populate fields
    $('#svx_spn').on('change',function(event){
    	var selection = $('#svx_spn').val();
    	if (selection) {
			$.ajax({
				type: 'GET',
				url: "ajax/svx.php",
				data: {	lpn: selection },
				success: function(data) {
					if (data) {
						var profile = jQuery.parseJSON(data);
						$('#svx_ref').val(profile.reflector);
						$('#svx_prt').val(profile.port);
						$('#svx_cal').val(profile.callsign);
						$('#svx_key').val(profile.key);
						$('#svx_clb').val(profile.beacon);
      				}
      				$('#sysmsg').iziModal('destroy');
      				$('#sysmsg').iziModal({ 
							title: 'Profile loaded!<br/>Click <b>Save</b> button to apply',
    						icon: "icon-check",
    						headerColor: "#00af66",
    						timeout: 3000,
    						timeoutProgressbar: true,
    						transitionIn: "fadeInUp",
    						transitionOut: "fadeOutDown",
    						bottom: 0,
    						autoOpen: 50,
    						zindex:1031,
    						overlay: false
					});
				}
			});
		}
    });

	// WiFi config
	$("#savewifi").click(function() {
		$('#savewifi').prop('disabled', true);
		$('#savewifi').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#savewifi').prop('disabled', false);
				$('#savewifi').fadeTo("fast", 1);
			}, 5000);
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
    					timeout: 3000,
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
      				}, 3200);
      			}
			}
		});
	});

	/*********** System functions ***********/
	
	// Power Off OS
	$("#halt").click(function() {
		$('#halt').prop('disabled', true);
		$('#halt').fadeTo("fast", 0.15);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	halt: 1 }
		});
	});
	
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
    					timeout: 3000,
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
      				}, 3200);
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
    					timeout: 3000,
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
      				}, 3200);
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
			}, 6000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	switchHostName: 1 },
			success: function(data) {
				if(data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 3000,
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
      				}, 3200);
      			}
			}
		});
	});
	
	// Switch file system state (RW <-> RO)
	$("#changeFS").click(function() {
		$('#changeFS').prop('disabled', true);
		$('#changeFS').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#changeFS').prop('disabled', false);
				$('#changeFS').fadeTo("fast", 1);
			}, 2000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	changeFS : $('#changeFS').val() },
			success: function(data) {
				if(data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 3000,
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
      				}, 3200);
      			}
			}
		});
	});

	// Update Dashboard
	$("#updateDash").click(function() {
		$('#updateDash').prop('disabled', true);
		$('#updateDash').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#updateDash').prop('disabled', false);
				$('#updateDash').fadeTo("fast", 1);
			}, 9000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	updateDash : 1 },
			success: function(data) {
				if(data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 3000,
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
      				}, 3500);
      			}
			}
		});
	});
	
	// RoLink update
	$("#updateRoLink").click(function() {
		$('#sysmsg').iziModal('destroy');
		$('#sysmsg').iziModal({
				title: 'Updating, please wait...',
				width: "40vh",
				icon: "icon-warning",
				headerColor: "#941919",
				autoOpen: 5,
				timeout: 30000,
				timeoutProgressbar: true,
				closeOnEscape: false,
				closeButton: false,
				overlayClose: false,
				overlay: true
		});
		$('#updateRoLink').prop('disabled', true);
		$('#updateRoLink').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#updateRoLink').prop('disabled', false);
				$('#updateRoLink').fadeTo("fast", 1);
			}, 15000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	updateRoLink : 1 },
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

	// Make Read-only
	$("#makeRO").click(function() {
		$('#sysmsg').iziModal('destroy');
		$('#sysmsg').iziModal({
				title: 'Working, please wait...',
				width: "40vh",
				icon: "icon-warning",
				headerColor: "#941919",
				autoOpen: 5,
				timeout: 75000,
				timeoutProgressbar: true,
				closeOnEscape: false,
				closeButton: false,
				overlayClose: false,
				overlay: true
		});
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: {	makeRO : 1 },
			success: function(data) {
				if (data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
						icon: "icon-check",
						headerColor: "#00af66",
						timeout: 3000,
						timeoutProgressbar: true,
						transitionIn: "fadeInUp",
						transitionOut: "fadeOutDown",
						bottom: 0,
						zindex:1031,
						autoOpen: 50
					});
					$("#makeRO").unbind().prop('innerText', 'Click me to reboot!').prop('id', 'reboot');
					var rebootNow = setInterval(blinkMyButton, 1000);
					function blinkMyButton() { $('#reboot').fadeOut().fadeIn(); }
					$("#reboot").click(function() {
						$("#reboot").hide();
						clearInterval(rebootNow);
						$.ajax({ type: 'POST', url: "ajax/sys.php", data: {	reboot: 1 } });
					});
				}
			}
		});
	});
	
	// Configuration values
	$("#cfgSave").click(function() {
		$('#cfgSave').prop('disabled', true);
		$('#cfgSave').fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#cfgSave').prop('disabled', false);
				$('#cfgSave').fadeTo("fast", 1);
			}, 5000);
		$.ajax({
			type: 'POST',
			url: "ajax/sys.php",
			data: { cfgPttPin: $('#cfgPttPin').val(),
					cfgTty: $('#cfgTty').val(),
					cfgHostname: $('#cfgHostname').prop('checked'),
					cfgUptime: $('#cfgUptime').prop('checked'),
					cfgCpuStats: $('#cfgCpuStats').prop('checked'),
					cfgPublicIp: $('#cfgPublicIp').prop('checked'),
					cfgSsid: $('#cfgSsid').prop('checked'),
					cfgNetworking: $('#cfgNetworking').prop('checked'),
					cfgSvxStatus: $('#cfgSvxStatus').prop('checked'),
					cfgRefNodes: $('#cfgRefNodes').prop('checked'),
					cfgCallsign: $('#cfgCallsign').prop('checked'),
					cfgDTMF: $('#cfgDTMF').prop('checked')
			},
			success: function(data) {
				if(data) {
					$('#sysmsg').iziModal('destroy');
					$('#sysmsg').iziModal({ 
						title: data,
    					icon: "icon-check",
    					headerColor: "#00af66",
    					timeout: 3000,
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
      				}, 3200);
      			}
			}
		});
	});

	// DTMF Sender
	 $('button[id^="sendDTMF"]').click(function() {
	 	var bcID	= $(this).attr("id");
	 	var bcIDVal	= $(this).attr("value");
		$('#' + bcID).prop('disabled', true);
		$('#' + bcID).fadeTo("fast", 0.15);
			setTimeout(function() {
				$('#' + bcID).prop('disabled', false);
				$('#' + bcID).fadeTo("fast", 1);
			}, 500);
		if (bcIDVal != undefined) {
			var dtmfData = bcIDVal;
		} else {
			var dtmfData = $('#dtmfCommand').val();
		}
		if (!dtmfData) return;
		$.ajax({
			type: 'POST',
			url: "ajax/svx.php",
			data: {	dtmfCommand: dtmfData },
			success: function(data) {
				if(data) {
					$("#dtmfConsole").fadeIn("fast").append(data + "<br/>");
      			}
			}
		});
	});

	// Mixer control
	$(function() {
	    var prevValue = 0;
	    $(document).on('input change', 'input[id^="vac_"]', function() {
	    	var volumeSlider = $(this).attr("id");
	    	$('#'+ volumeSlider + 'cv').html('(' + $(this).val() + '%)');
	    });
	    // mouse down to check for previous value
	    $('input[id^="vac_"]').on('mousedown touchstart', function(e) {
	        var volumeSlider = $(this).attr("id");
	        var prevValue = $(this).val();
	    });
	    // mouse up when the mouse up from the slider with end value
	    $('input[id^="vac_"]').on('mouseup touchend', function() {
	        var volumeSlider = $(this).attr("id");
	        var newValue = $(this).val();
	        if (newValue !== prevValue) {
	            console.log(volumeSlider + ' / ' + newValue);
	            $('#'+ volumeSlider + 'cv').html('(' + $(this).val() + '%)');
				$.ajax({
					type: 'POST',
					url: "ajax/sys.php",
					data: {	mctrl:volumeSlider, mval:newValue },
					success: function(data) {}
				});
	        }
	    });
	});

	// Display a log file in real time
	if (window.location.search.match(/\=log/)) {
		var selectedLogType = sessionStorage.getItem("logtype");
		
		if(selectedLogType != undefined || selectedLogType != null){
    		$("select").first().find(":selected").removeAttr("selected");
    		$("select").find("option").each(function () {
				if ($(this).val() == selectedLogType) {
					$(this).attr("selected", true);
				}
			});
		}
		
		$("select").on("change", function () {
			sessionStorage.setItem("logtype", $("select").first().val());
			var url = "?p=log&t=" + $(this).val();
    	      if (url) {
    	          window.location = url;
    	      }
    	      return false;
		});
		
 		connectToServer(0, selectedLogType);

		function connectToServer(linenum, selval) {
 			$.ajax({
 				dataType: "json",
 				url: "ajax/log.php",
 				data: {n:linenum, t:selval},
 				timeout: 119000,
 				success:function(data) {
 					if (data == null){
 						connectToServer(0,selval);
 						$("#log_data").html("<br />Error, reloading...");
 					} else {
 						$("#new_log_line").fadeIn("fast");
 						var items = [];
 						var count = parseInt(data.count);
 						if (count<0) {
 							connectToServer(linenum,selval);
 						}
 						var loglines = data.loglines;
 						loglines.reverse();
 						var l = 0;
 						$.each( loglines, function(key, val) {
 							l = l+1;
 							items.push("<br />"+val.toString());
 						});
 						var newlines = items.join( "" );
 						$("#log_data").prepend(newlines);
 						$("#new_log_line").fadeOut(375);
 						connectToServer(count,selval);
 					}
 				},
 				error: function(request, status, err) {
 					if(status == "timeout") {
 						connectToServer(0,selval);
 						$("#log_data").html("<br />Local timeout, reloading...");
 					}
 				}
 			});
		}
	}
});
