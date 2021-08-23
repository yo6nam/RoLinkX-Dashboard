# RoLinkX Dashboard
## Web dashboard for OrangePi Zero & SVXLink (RoLink)
 
Purpose : Make your life easier<br>
Development : pre-beta<br>
Features :
- System overview
- System control (Reboot / Wi-Fi)
- Wi-Fi configuration editor
- SVXLink (RoLink) client configuration (with profiles) editor
- SA818(S)V/U programming

Requirements (hardware):<br>
- OrangePi Zero LTS 256/512MB
- SA818 radio module

Requirements (software):<br>
- Armbian Buster (mainline based kernel 5.10.y)
- lighttpd & php-cgi
- SVXLink compiled as RoLink ( install it using ***bash <(curl -s https://svx.439100.ro/install.sh)*** )

 Steps to install :<br>
 1. Clone to your device ( $git clone https://github.com/yo6nam/RoLinkX-Dashboard )
 2. Change dir to RoLinkX-Dashboard ( $cd RoLinkX-Dashboard/ )
 3. Execute setup.sh ( $sudo ./setup.sh )<br>

Or, single line : ***git clone https://github.com/yo6nam/RoLinkX-Dashboard;cd RoLinkX-Dashboard/;sudo ./setup.sh***
 
 Some basic checks and automagic mods are applied by the setup script but please note that<br>
 "Network Manager **WILL BE** disabled"  
 
 Once installed, open a browser window and try your luck using :<br>
 **http://device_hostname/rolink**<br>
 or<br>
 **http://<device_ip>/rolink**
 
 You should be greeted with something like this
 
 ![](https://i.imgur.com/gZzvBKv.png) 
 
 On a fresh install, you will need to reboot about three times
 1. After installing the RoLink client (custom precompiled SVXLink) & if the sound card is not configured
 2. If Network Manager is found to be enabled and running upon executing *setup.sh*
 3. After saving your first pair of SSID/Key using the Wi-Fi page

 *Remember, this is a pre-beta release, don't expect it to work flawlessly.*
 
 **Good luck!**
 
 //Razvan YO6NAM
 
 # Credits
[RaspAp] (https://github.com/RaspAP/raspap-webgui)  
[Rémy Sanchez] for php_serial.class.php
