# RoLinkX Dashboard
## Web dashboard for OrangePi Zero & SVXLink (RoLink)
 
Purpose : Make your life easier<br>
Development : pre-beta<br>
Requirements (hardware):<br>
- OrangePi Zero LTS 256/512MB
- SA818 radio module

Requirements (software):<br>
- Armbian Buster (mainline based kernel 5.10.y)
- lighttpd & php-cgi
 
 Steps to install :<br>
 1. Clone to your device ( $git clone https://github.com/yo6nam/RoLinkX-Dashboard )
 2. Change dir to RoLinkX-Dashboard ( $cd RoLinkX-Dashboard/ )
 3. Execute setup.sh ( $sudo ./setup.sh )
 
 Some basic checks are done by the script but usually the web application destination should be /var/www/html/  
 
 Once installed, open a browser window and try your luck using :<br>
 **http://device_hostname/rolink**<br>
 or<br>
 **http://<device_ip>/rolink**
 
 *Remember, this is a pre-beta release, don't expect it to work flawlessly.*
 
 **Good luck!**
 
 //Razvan YO6NAM
