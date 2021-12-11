![](https://i.imgur.com/qJcJAua.jpg) 
## Web dashboard for OrangePi Zero & SVXLink (RoLink)
 
Purpose : Make your life easier<br>
Development : v1.0<br>
Features :
- System overview (Uptime / CPU Stats / Networking)
- System control (Reboot / Wi-Fi / SVXLink)
- Wi-Fi network manager & scanner
- SVXLink (RoLink) client configuration editor (with profiles)
- DTMF Sender (commands to control SVXLink status / TGs / etc.)
- SA818(S)V/U programming
- Audio (alsamixer) controls
- File System switch (Read/Write <-> Read-only) <sup>See note 2</sup>
- Display stations connected to current reflector
- Display logs (Syslog or SVXLink) in real time
- Updates with a click of a button

Requirements (hardware):<br>
- OrangePi Zero LTS 256/512MB
- SA818 radio module

Requirements (software):<br>
- Armbian Buster / Bullseye (mainline based kernel 5.10.y)
- lighttpd & php-cgi
- SVXLink compiled as RoLink *(see below how to install it)*

## How to install RoLink
```
bash <(curl -s https://svx.439100.ro/install.sh)
```

## Steps to install RoLinkX Dashboard
 1. Clone to your device ( `git clone https://github.com/yo6nam/RoLinkX-Dashboard` )
 2. Change dir to RoLinkX-Dashboard ( `cd RoLinkX-Dashboard/` )
 3. Execute setup.sh ( `sudo ./setup.sh` )<br>

Or, single line : 
```
git clone https://github.com/yo6nam/RoLinkX-Dashboard;cd RoLinkX-Dashboard/;sudo ./setup.sh
```

Or, using RoLink init script:
```
/opt/rolink/scripts/init dash
```
## How to update
Use the "Update Dashboard" button from the Config page (most convenient) or execute the installation steps again.  
If you've kept the cloned folder, navigate to it (usually it's in %home% directory) and use
```
cd ~/RoLinkX-Dashboard/;git pull;sudo ./setup.sh
```

 Some basic checks and automagic mods are applied by the setup script but please note that<br>
 "Network Manager **WILL BE** disabled"  
 
 Once installed, open a browser window and try your luck using :<br>
 **http://device_hostname/rolink**    --*or*--    **http://<device_ip>/rolink**
 
 You should be greeted with something like this
 
 Desktop             |  Mobile
:-------------------------:|:-------------------------:
 ![](https://i.imgur.com/bO3lCaV.png) | ![](https://i.imgur.com/p5vm9OB.png)
 
 Page previews
 :---:
 ![](https://i.imgur.com/en9bU1D.gif)
 
 On a fresh OS (Armbian Buster) install, you will need to reboot about three times
 1. After installing the RoLink client (custom precompiled SVXLink) & if the sound card is not configured
 2. If Network Manager is found to be enabled and running upon executing *setup.sh*
 3. After saving your first pair of SSID/Key using the Wi-Fi page

# Notes
1. On SA818_V5.x FW version (check with AT+VERSION command) the serial port will only work on a cold boot
and stop responding after a reboot. A cause/fix remains to be determined.  

2. Converting the File System to read-only state can be achieved using the "Make FS read-only" button from the Config page or by using the script used for RoLink installation (Option #5)


**Good luck!**
 
//Razvan YO6NAM @ xpander.ro
 
 # Credits
[RaspAp] (https://github.com/RaspAP/raspap-webgui)  
[Rémy Sanchez] for php_serial.class.php
