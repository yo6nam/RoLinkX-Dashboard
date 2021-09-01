![](https://i.imgur.com/qJcJAua.jpg) 
## Web dashboard for OrangePi Zero & SVXLink (RoLink)
 
Purpose : Make your life easier<br>
Development : pre-beta<br>
Features :
- System overview
- System control (Reboot / Wi-Fi / SVXLink)
- Wi-Fi network manager & scanner
- SVXLink (RoLink) client configuration editor (with profiles)
- SA818(S)V/U programming
- Display logs (Syslog or SVXLink) in real time

Requirements (hardware):<br>
- OrangePi Zero LTS 256/512MB
- SA818 radio module

Requirements (software):<br>
- Armbian Buster (mainline based kernel 5.10.y)
- lighttpd & php-cgi
- SVXLink compiled as RoLink *(see below how to install it)*
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
 
 Some basic checks and automagic mods are applied by the setup script but please note that<br>
 "Network Manager **WILL BE** disabled"  
 
 Once installed, open a browser window and try your luck using :<br>
 **http://device_hostname/rolink**    --*or*--    **http://<device_ip>/rolink**
 
 You should be greeted with something like this
 
 ![](https://i.imgur.com/yYVb9C8.png) 
 
 On a fresh OS (Armbian Buster) install, you will need to reboot about three times
 1. After installing the RoLink client (custom precompiled SVXLink) & if the sound card is not configured
 2. If Network Manager is found to be enabled and running upon executing *setup.sh*
 3. After saving your first pair of SSID/Key using the Wi-Fi page

# Notes
If you're on YO7GQZ's image, you need to fix something before attempting to install the dashboard  
```
sudo chmod -R guo+rw /var/log/lighttpd
```

**Good luck!**

*Remember, this is a pre-beta release, don't expect it to work flawlessly.*
 
//Razvan YO6NAM @ xpander.ro
 
 # Credits
[RaspAp] (https://github.com/RaspAP/raspap-webgui)  
[Rémy Sanchez] for php_serial.class.php
