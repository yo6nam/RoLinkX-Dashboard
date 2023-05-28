/*!
 * Start Bootstrap - Simple Sidebar v6.0.3 (https://startbootstrap.com/template/simple-sidebar)
 * Copyright 2013-2021 Start Bootstrap
 * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-simple-sidebar/blob/master/LICENSE)
 */
//
// Scripts
//

window.addEventListener('DOMContentLoaded', (event) => {
  // Enable Tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map( function(tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
      trigger : 'hover',
      html : true
    });
  });
  // Toggle the side navigation
  const sidebarToggle = document.body.querySelector('#sidebarToggle');
  if (sidebarToggle) {
    // Uncomment Below to persist sidebar toggle between refreshes
    // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
    //     document.body.classList.toggle('sb-sidenav-toggled');
    // }
    sidebarToggle.addEventListener('click', (event) => {
      event.preventDefault();
      document.body.classList.toggle('sb-sidenav-toggled');
      localStorage.setItem(
        'sb|sidebar-toggle',
        document.body.classList.contains('sb-sidenav-toggled')
      );
    });
  }
});

/*
 *   RoLinkX Dashboard v2.99b
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
 * jQuery stuff
 */

$(document).ready(function () {
  $('[data-bs-toggle="tooltip"').on("click", function () {
    $(this).tooltip("hide");
  });
  $.fn.showNotice = function (data, timeOutVal) {
    $(this).iziModal('destroy');
    $(this).iziModal({
      title: data,
      icon: 'icon-check',
      headerColor: '#00af66',
      timeout: timeOutVal,
      timeoutProgressbar: true,
      transitionIn: 'fadeInUp',
      transitionOut: 'fadeOutDown',
      bottom: 0,
      autoOpen: 50,
      zindex: 1031,
      overlay: false,
    });
  };

  // SA818 Programming
	$("#sa_grp").select2({ theme: "bootstrap-5" });
	$('#sa_grp').parent('div').children('span').children('span').children('span').css('height', ' calc(3.5rem + 2px)');
	$('#sa_grp').parent('div').children('span').children('span').children('span').children('span').css('margin-top', '18px');
	$('#sa_grp').parent('div').find('label').css('z-index', '1');
	$('#programm').click(function () {
	$(this).prop('disabled', true).fadeTo('fast', 0.15);
    $('#sysmsg').iziModal('destroy');
    $('#sysmsg').iziModal({
      title: 'Sending data, please wait...',
      width: '46vh',
      icon: 'icon-warning',
      headerColor: '#941919',
      autoOpen: 5,
      closeOnEscape: false,
      closeButton: false,
      overlayClose: false,
      overlay: true,
    });
    $.ajax({
      type: 'POST',
      url: 'ajax/trx.php',
      data: {
        grp: $('#sa_grp').val(),
        dev: $('#sa_dev').val(),
        tpl: $('#sa_tpl').val(),
        sql: $('#sa_sql').val(),
        vol: $('#sa_vol').val(),
        flt: $('#sa_flt').val(),
      },
      success: function (data) {
        if (data) {
		  $('#sysmsg').iziModal('destroy');
		  $('#programm').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3500);
          setTimeout(function () {
            location.reload();
          }, 10000);
        }
      }
    });
  });

  // SVXLink restore config
  $('#restore').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/svx.php',
      data: { restore: true },
      success: function (data) {
        if (data) {
		  $('#restore').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
          setTimeout(function () {
            location.reload(true);
          }, 3500);
        }
      }
    });
  });

  // SVXLink config
  $('#savesvxcfg').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/svx.php',
      data: {
        prn: $('#svx_prn').val(),
        ref: $('#svx_ref').val(),
        prt: $('#svx_prt').val(),
        cal: $('#svx_cal').val(),
        key: $('#svx_key').val(),
        clb: $('#svx_clb').val(),
        vop: $('#svx_vop').val(),
        sid: $('#svx_sid').val(),
        lid: $('#svx_lid').val(),
        tip: $('#svx_tip').val(),
        cbr: $('#svx_cbr').val(),
        rgr: $('#svx_rgr').val(),
        rxp: $('#svx_rxp').val(),
        txp: $('#svx_txp').val(),
        mtg: $('#svx_mtg').val(),
        tgt: $('#svx_tgt').val(),
        sqd: $('#svx_sqd').val(),
        txt: $('#svx_txt').val(),
        acs: $('#svx_acs').val(),
        rxe: $('#svx_rxe').val(),
        txe: $('#svx_txe').val(),
        mag: $('#svx_mag').val(),
        res: $('#svx_res').val(),
        lim: $('#svx_lim').val()
      },
      success: function (data) {
        if (data) {
          $('#savesvxcfg').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
          setTimeout(function () {
            location.reload(true);
          }, 5000);
        }
      }
    });
  });

  // SVXLink delete profile
  $('#delsvxprofile').click(function () {
    $.ajax({
      type: 'POST',
      url: 'ajax/svx.php',
      data: { prd: $('#svx_spn').val() },
      success: function (data) {
		if (data) {
			$('#sysmsg').showNotice(data, 2000);
			setTimeout(function () {
				location.reload(true);
			}, 3000);
		}
      }
    });
  });

  // Load selected SVX profile and populate fields
  $('#svx_spn').on('change', function (event) {
    var selection = $('#svx_spn').val();
    if (selection) {
      $.ajax({
        type: 'GET',
        url: 'ajax/svx.php',
        data: { lpn: selection },
        success: function (data) {
          if (data) {
			var autoConnect = $('#autoConnect').val();
            var profile = jQuery.parseJSON(data);
            $('#svx_prn').val(selection.split('.').slice(0,-1).join());
            $('#svx_ref').val(profile.reflector);
            $('#svx_prt').val(profile.port);
            $('#svx_cal').val(profile.callsign);
            $('#svx_key').val(profile.key);
            $('#svx_clb').val(profile.beacon);
            $('#svx_tip').val(profile.type);
            if (typeof profile.bitrate !== 'undefined') {
            	$('#svx_cbr').val(profile.bitrate)
            }
            if (typeof profile.rogerBeep !== 'undefined') {
            	$('#svx_rgr').val(profile.rogerBeep)
            }
            if (typeof profile.shortIdent !== 'undefined') {
            	$('#svx_sid').val(profile.shortIdent)
            }
            if (typeof profile.longIdent !== 'undefined') {
            	$('#svx_lid').val(profile.longIdent)
            }
            if (typeof profile.connectionStatus !== 'undefined') {
            	$('#svx_acs').val(profile.connectionStatus)
            }
            if (autoConnect === 'true') {
				$('#sysmsg').showNotice(
              		'Profile loaded!<br/>Auto connection started...',
              		3000
            	);
            	$('#savesvxcfg').trigger('click');
            	return true;
            }
            $('#sysmsg').showNotice(
              'Profile loaded!<br/>Click <b>Save</b> button to apply',
              3000
            );

          }
        }
      });
    }
  });

  // SVXLink Show/Hide password
  $("#show_hide").on('click', function(event) {
    var svxKey = $('#svx_key');
    var svxKeyType = svxKey.attr("type");
    if (svxKeyType === "text") {
      svxKey.attr('type', 'password');
      $('#show_hide i').toggleClass("icon-visibility icon-visibility_off");
    } else if (svxKeyType === "password") {
      svxKey.attr('type', 'text');
      $('#show_hide i').toggleClass("icon-visibility icon-visibility_off");
    }
  });

  // WiFi config
  $('#savewifi').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/wifi.php',
      data: {
        wn1: $('#wlan_network_1').val(),
        wk1: $('#wlan_authkey_1').val(),
        wn2: $('#wlan_network_2').val(),
        wk2: $('#wlan_authkey_2').val(),
        wn3: $('#wlan_network_3').val(),
        wk3: $('#wlan_authkey_3').val(),
        wn4: $('#wlan_network_4').val(),
        wk4: $('#wlan_authkey_4').val()
      },
      success: function (data) {
        if (data) {
          $('#savewifi').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
          if (data.match(/stored/)) {
			  setTimeout(function () {
				  location.reload(true);
			  }, 3200);
          }
        }
      }
    });
  });

  // Power Off OS
  $('#halt').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { halt: 1 }
    });
  });

  // Reboot OS
  $('#reboot').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { reboot: 1 }
    });
  });

  // Restart Wi-Fi
  $('#rewifi').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { rewifi: 1 },
      success: function (data) {
        if (data) {
          $('#rewifi').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice('Wi-Fi service restarted', 3000);
        }
      }
    });
  });

  // Restart SVXLink service
  $('#resvx').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { resvx: 1 },
      success: function (data) {
        if (data == true) {
          $('#resvx').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice('RoLink service has been (re)started', 3000);
          setTimeout(function () {
			$('#svxStatus').load('includes/status.php?svxStatus');
			$('#refContainer').load('includes/status.php?svxReflector');
          }, 1500);
        }
      }
    });
  });

  // Stop SVXLink service
  $('#endsvx').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { endsvx: 1 },
      success: function (data) {
        if (data == true) {
          $('#endsvx').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice('RoLink service has been stopped', 3000);
          setTimeout(function () {
			$('#svxStatus').load('includes/status.php?svxStatus');
			$('#refContainer').load('includes/status.php?svxReflector');
          }, 1500);
        }
      }
    });
  });

  // Switch Host Name (default -> callsign)
  $('#switchHostName').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { switchHostName: 1 },
      success: function (data) {
        if (data) {
          $('#switchHostName').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
		  if (data.match(/reboot/)) {
			setTimeout(function () {
			  location.reload(true);
			}, 3200);
          }
        }
      }
    });
  });

  // Latency check
  $('#latencyCheck').click(function () {
	$(this).html('<span role="status" class="spinner-border spinner-border-sm mx-2"></span>Please wait...');
    $('#latencyCheck').prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { latencyCheck: 1 },
      success: function (data) {
        if (data) {
			try {
    			networkData = JSON.parse(data);
			} catch (error) {
				$('#latencyCheck').prop('disabled', false).fadeTo('fast', 1, function() {
					$('#sysmsg').showNotice(data, 3000);
					$(this).html('<i class="icon-timer px-2" aria-hidden="true"></i>Run test');
  				});
    			return;
			}
			// Check if returned data is incomplete
			if (Object.keys(networkData).length < 5 || networkData[0] == null ||
				networkData[1] == null || networkData[2] == null ||
				networkData[3] == null || networkData[4] == null) {
				$('#latencyCheck').prop('disabled', false).fadeTo('fast', 1, function() {
					$('#sysmsg').showNotice('Incomplete data received<br/>Try again in 30 seconds', 3000);
					$(this).html('<i class="icon-timer px-2" aria-hidden="true"></i>Run test');
  				});
    			return;
			}
			var tcpBandwidth = networkData[0].match(/(\S+)\s(\MB|KB)/);
			validate('#tcp_bw', tcpBandwidth, 1)
			var tcpLatency = networkData[1].match(/(\S+)\s(\S+)/);
			validate('#tcp_lat', tcpLatency, 2)
			var udpTxBandwidth = networkData[2].match(/(\S+)\s(\MB|KB)/);
			validate('#udp_sbw', udpTxBandwidth, 1)
			var udpRxBandwidth = networkData[3].match(/(\S+)\s(\MB|KB)/);
			validate('#udp_rbw', udpRxBandwidth, 1)
			var udpLatency = networkData[4].match(/(\S+)\s(\S+)/);
			validate('#udp_lat', udpLatency, 2)
			$('#tcp_bw').val(networkData[0]);
			$('#tcp_lat').val(networkData[1]);
			$('#udp_sbw').val(networkData[2]);
			$('#udp_rbw').val(networkData[3]);
			$('#udp_lat').val(networkData[4]);
			$('#latencyCheck').prop('disabled', false).fadeTo('fast', 1, function() {
				$(this).html('<i class="icon-timer px-2" aria-hidden="true"></i>Run test');
				$('#sysmsg').showNotice('Completed successfully', 3000);
  			});
        }
      }
    });

	function validate(container, data, type) {
	    var status = [];
	    status['ok'] = 'bg-success text-white'
	    status['limit'] = 'bg-warning text-dark'
	    status['bad'] = 'bg-danger text-white'
	    switch (type) {
	        case 1:
	            if (data[2] == 'KB') {
	                if (data[1] < 350) {
	                    $(container).addClass(status['bad']);
	                } else if (data[1] >= 350 && data[1] <= 500) {
	                    $(container).addClass(status['limit']);
	                } else {
	                    $(container).addClass(status['ok']);
	                }
	            } else {
	                $(container).addClass(status['ok']);
	            }
	            break;
	        case 2:
	            if (data[2] == 'ms') {
	                if (data[1] > 150) {
	                    $(container).addClass(status['bad']);
	                } else if (data[1] >= 100 && data[1] <= 150) {
	                    $(container).addClass(status['limit']);
	                } else {
	                    $(container).addClass(status['ok']);
	                }
	            } else {
	                $(container).addClass(status['bad']);
	            }
	            break;
	        default:
	            return;
	    }
	}
  });

  // Switch file system state (RW <-> RO)
  $('#changeFS').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { changeFS: $('#changeFS').val() },
      success: function (data) {
        if (data) {
          $('#changeFS').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
          setTimeout(function () {
            location.reload();
          }, 3200);
        }
      }
    });
  });

  // Expand file system
  $('#expandFS').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
	$('#sysmsg').iziModal('destroy');
    $('#sysmsg').iziModal({
      title: 'Expanding file system!<br/>It might take a few minutes...',
      width: '40vh',
      icon: 'icon-warning',
      headerColor: '#941919',
      autoOpen: 5,
      timeout: 90000,
      timeoutProgressbar: true,
      closeOnEscape: false,
      closeButton: false,
      overlayClose: false,
      overlay: true,
    });
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { expandFS: 1 },
      success: function (data) {
        if (data) {
          $('#expandFS').prop('disabled', false).fadeTo('fast', 1);
          $('#sysmsg').showNotice(data, 3000);
          setTimeout(function () {
          	location.reload();
          }, 4000);
        }
      }
    });
  });

  // Update Dashboard
  $('#updateDash').click(function () {
    $(this).text("Please wait...");
    $('#cfgSave, #updateDash, #updateRoLink').prop('disabled', true).fadeTo('fast', 0.15);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { updateDash: 1 },
      success: function (data) {
		if (data) {
			$('#sysmsg').showNotice(data, 3000);
			$('#cfgSave, #updateDash, #updateRoLink').prop('disabled', false).fadeTo('fast', 1)
			setTimeout(function () {
				location.reload();
			}, 4000);
		}
      }
    });
  });

  // Download voices
  $('#getVoices').click(function () {
    $('#sysmsg').iziModal('destroy');
    $('#sysmsg').iziModal({
      title: 'Downloading, please wait!<br/>It might take a few minutes...',
      width: '40vh',
      icon: 'icon-warning',
      headerColor: '#941919',
      autoOpen: 5,
      timeout: 90000,
      timeoutProgressbar: true,
      closeOnEscape: false,
      closeButton: false,
      overlayClose: false,
      overlay: true,
    });

    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { getVoices: 1 },
      success: function (data) {
        if (data) {
			$('#sysmsg').showNotice(data, 3000)
			setTimeout(function () {
				location.reload(true);
			}, 5000);
        }
      }
    });
  });

  // RoLink update
  $('#updateRoLink').click(function () {
	$('#cfgSave, #updateDash, #updateRoLink').prop('disabled', true).fadeTo('fast', 0.15);
    $('#sysmsg').iziModal('destroy');
    $('#sysmsg').iziModal({
      title: 'Updating, please wait!<br/>It might take a few minutes...',
      width: '40vh',
      icon: 'icon-warning',
      headerColor: '#941919',
      autoOpen: 5,
      timeout: 90000,
      timeoutProgressbar: true,
      closeOnEscape: false,
      closeButton: false,
      overlayClose: false,
      overlay: true,
    });

    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { updateRoLink: 1 },
      success: function (data) {
        if (data) {
			$('#sysmsg').showNotice(data, 3000)
			$('#cfgSave, #updateDash, #updateRoLink').prop('disabled', false).fadeTo('fast', 1)
			setTimeout(function () {
				window.location.href = "/rolink";
			}, 5000);
        }
      }
    });
  });

  // Make Read-only
  $('#makeRO').click(function () {
    $('#sysmsg').iziModal('destroy');
    $('#sysmsg').iziModal({
      title: 'Working, please wait...',
      width: '40vh',
      icon: 'icon-warning',
      headerColor: '#941919',
      autoOpen: 5,
      timeout: 75000,
      timeoutProgressbar: true,
      closeOnEscape: false,
      closeButton: false,
      overlayClose: false,
      overlay: true,
    });
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: { makeRO: 1 },
      success: function (data) {
        if (data) {
          $('#sysmsg').showNotice(data, 3000);
          $('#makeRO')
            .unbind()
            .prop('innerText', 'Click me to reboot!')
            .prop('id', 'reboot');
          var rebootNow = setInterval(blinkMyButton, 1000);
          function blinkMyButton() {
            $('#reboot').fadeOut().fadeIn();
          }
          $('#reboot').click(function () {
            $('#reboot').hide();
            clearInterval(rebootNow);
            $.ajax({ type: 'POST', url: 'ajax/sys.php', data: { reboot: 1 } });
          });
        }
      }
    });
  });

  // Configuration values
  $('#cfgSave').click(function () {
    $(this).prop('disabled', true).fadeTo('fast', 0.15);
    setTimeout(function () {
      $('#cfgSave').prop('disabled', false).fadeTo('fast', 1);
    }, 3000);
    $.ajax({
      type: 'POST',
      url: 'ajax/sys.php',
      data: {
        cfgPttPin: $('#cfgPttPin').val(),
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
        cfgDTMF: $('#cfgDTMF').prop('checked'),
        cfgKernel: $('#cfgKernel').prop('checked'),
        cfgDetectSa: $('#cfgDetectSa').prop('checked'),
        cfgFreeSpace: $('#cfgFreeSpace').prop('checked'),
        cfgTempOffset: $('#cfgTempOffset').prop('checked'),
        cfgAutoConnect: $('#cfgAutoConnect').val(),
        timezone: $('#timezone').val(),
      },
      success: function (data) {
        if (data) {
          $('#sysmsg').showNotice(data, 3000);
          setTimeout(function () {
            location.reload(true);
          }, 3200);
        }
      }
    });
  });

  // DTMF Sender
  $('button[id^="sendDTMF"]').click(function () {
    var bcID = $(this).attr('id');
    var bcIDVal = $(this).attr('value');
    $('#' + bcID)
      .prop('disabled', true)
      .fadeTo('fast', 0.15);
    setTimeout(function () {
      $('#' + bcID)
        .prop('disabled', false)
        .fadeTo('fast', 1);
    }, 500);
    if (bcIDVal != undefined) {
      var dtmfData = bcIDVal;
    } else {
      var dtmfData = $('#dtmfCommand').val();
    }
    if (!dtmfData) return;
    $.ajax({
      type: 'POST',
      url: 'ajax/svx.php',
      data: { dtmfCommand: dtmfData },
      success: function (data) {
        if (data) {
          $('#dtmfConsole')
            .fadeIn('fast')
            .append(data + '<br/>');
        }
      }
    });
  });

  // Mixer control
  $(function () {
    var prevValue = 0;
    $(document).on('input change', 'input[id^="vac_"]', function () {
      var volumeSlider = $(this).attr('id');
      $('#' + volumeSlider + 'cv').html('(' + $(this).val() + '%)');
    });
    // mouse down to check for previous value
    $('input[id^="vac_"]').on('mousedown touchstart', function (e) {
      var volumeSlider = $(this).attr('id');
      var prevValue = $(this).val();
    });
    // mouse up when the mouse up from the slider with end value
    $('input[id^="vac_"]').on('mouseup touchend', function () {
      var volumeSlider = $(this).attr('id');
      var newValue = $(this).val();
      if (newValue !== prevValue) {
        console.log(volumeSlider + ' / ' + newValue);
        $('#' + volumeSlider + 'cv').html('(' + $(this).val() + '%)');
        $.ajax({
          type: 'POST',
          url: 'ajax/sys.php',
          data: { mctrl: volumeSlider, mval: newValue },
          success: function (data) {},
        });
      }
    });
  });

  // Mic1 Boost enable
  $(document).bind('keypress', function (e) {
    if (window.location.search.match(/\=cfg/) && e.which == 109 || e.which == 77) {
      $('#vac_mb').prop('disabled', (i, v) => !v);
    }
  });

  // Display a log file in real time
  if (window.location.search.match(/\=log/)) {
    var selectedLogType = sessionStorage.getItem('logtype');

    if (selectedLogType != undefined || selectedLogType != null) {
      $('select').first().find(':selected').removeAttr('selected');
      $('select')
        .find('option')
        .each(function () {
          if ($(this).val() == selectedLogType) {
            $(this).attr('selected', true);
          }
        });
    }

    $('select').on('change', function () {
      sessionStorage.setItem('logtype', $('select').first().val());
      var url = '?p=log&t=' + $(this).val();
      if (url) {
        window.location = url;
      }
      return false;
    });

    connectToServer(0, selectedLogType);

    function connectToServer(linenum, selval) {
      $.ajax({
        dataType: 'json',
        url: 'ajax/log.php',
        data: { n: linenum, t: selval },
        timeout: 119000,
        success: function (data) {
          if (data == null) {
            connectToServer(0, selval);
            $('#log_data').html('<br />Error, reloading...');
          } else {
            $('#new_log_line').fadeIn('fast');
            var items = [];
            var count = parseInt(data.count);
            if (count < 0) {
              connectToServer(linenum, selval);
            }
            var loglines = data.loglines;
            loglines.reverse();
            var l = 0;
            $.each(loglines, function (key, val) {
              l = l + 1;
              items.push('<br />' + val.toString());
            });
            var newlines = items.join('');
            $('#log_data').prepend(newlines);
            $('#new_log_line').fadeOut(375);
            connectToServer(count, selval);
          }
        },
        error: function (request, status, err) {
          if (status == 'timeout') {
            connectToServer(0, selval);
            $('#log_data').html('<br/>Local timeout, reloading...');
          }
        },
      });
    }
  }
});
