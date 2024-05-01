# Rivendell Logmanager online

**This will not be maintained any more. I working on a complete new system avalible over here : https://github.com/olsson82/rivendellweb**

This script was created by **Brian McGlynn** for to use with Rivendell Radio Automation, so all credits goes to him.
The original script can be found here: https://github.com/bpm1992/rivendell/tree/rdweb/web/rdphp

It has not been maintained for about 4 years so, i took my time and start working it out.

Now it's work with the latest version of **Rivendell** (version 4)
Also with PHP version over **PHP5.1**

## Video
Made a simple video to show how it's working
https://youtu.be/bk9BWWZLXJg

## Importaint info
To be able to use voicetrack recording. You need HTTPS protocol on your server.

You can setup an Reverse Proxy server and have the protocol on that one (This is how i do!)

I recommend reverse proxy!

## Just so you know
Right now i only made it work, not done any big changes or redesign on the system.

You need to have this script on the same server as you have your Rivendell server

## Todo
* Fix bug that shows if there are a service without any clocks or stuff like that.
* Go over the code to see if we can do some better changes in the code.
* Maby some new functions

## Report bug
If you find something that i have missed, let me know!

## How to install (Debian system)
First login as root in the terminal with **su -l** and enter your root password.

Then do apt update & apt upgrade to update the machine.

Apache is already installed on the rivendell machine so we need to install php

**apt install php php-{common,mysql,xml,xmlrpc,curl,gd,imagick,cli,dev,imap,mbstring,opcache,soap,zip,intl}** 

Do a restart of apache **systemctl restart apache2**

Then go to the apache folder **cd /var/www/html** and delete everything inside.

Now clone the git folder: **git clone https://github.com/olsson82/rdphp.git .** (Don't forget the last . so it will clone inside html folder.

Now it should be up and running
