![](https://i.imgur.com/qJcJAua.jpg) 
## Web dashboard for OrangePi Zero & SVXLink (RoLink)
 
Purpose : Make your life easier<br>
Development : beta<br>
Features :
- System overview (Uptime / CPU Stats / Networking)
- System control (Reboot / Wi-Fi / SVXLink)
- Wi-Fi network manager & scanner
- SVXLink (RoLink) client configuration editor (with profiles)
- DTMF Sender (commands to control SVXLink status / TGs / etc.)
- SA818(S)V/U programming
- Display stations connected to current reflector
- Display logs (Syslog or SVXLink) in real time

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
Execute the installation steps again, or if you kept the cloned folder, navigate to it (usually it's in %home% directory) and use
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

2. Converting the OS to read-only state (in development) can be achieved using the following script
```
wget https://svx.439100.ro/data/xro && chmod +x ./xro && ./xro
```
Remember that in RO state, you will not be able to save WiFi data, SVXLink details/profiles (for now)
To switch between read-only and read-write, use the aliases rw and ro on your terminal.  

3. If you're on YO7GQZ's image, you need to fix something before attempting to install the dashboard  
Make your card RW using the rw command, paste the lines below followed by a CR then reboot
```
tee -a /usr/lib/tmpfiles.d/lighttpd.tmpfile.conf << EOF
f /var/log/lighttpd/error.log 0644 www-data www-data -
f /var/log/lighttpd/access.log 0644 www-data www-data -
EOF
```

**Good luck!**

*Remember, this is a beta release, don't expect it to work flawlessly.*
 
//Razvan YO6NAM @ xpander.ro
 
 # Credits
[RaspAp] (https://github.com/RaspAP/raspap-webgui)  
[Rémy Sanchez] for php_serial.class.php
