<img src="https://atomjump.com/images/logo80.png">

# notifications
Send notifications to the iPhone/Android AtomJump messaging app

# Requirements

AtomJump Loop Server >= 0.8.0
AtomJump Messaging app


# Installation

From within your Loop Server directory:

```
cd plugins
git clone https://git.atomjump.com/notifications.git
cd notifications
cp config/configORIGINAL.json config/config.json
nano config/config.json								[enter your own parameters:
					serverPath is your Loop Server file path.
					apiKey is the Google GCM apiKey.
					staging is true/false for which Loop Server config to use.]
cp pushcertSAMPLE.pem pushcert.pem				[You will need your own Apple push certicate in here]
php install.php
```

At the user end, the Android/iPhone app at https://src.atomjump.com/atomjump/messaging needs to be installed (this can be built with Cordova, or this is available on the Android and iPhone app-stores as 'AtomJump Messaging')


# AtomJump's own notification system

This notification system option does not depend on any certificates from Android or Apple, but instead lets you connect to one or more installations of the MedImage Proxy Server >= 1.8.1. More details are available here: http://medimage.co.nz/download/

In your config.json file you should switch 'atomjumpNotifications.use' to 'true' to enable this type of notification system.

**Current Limitations**: Android app notifications will appear up to 30 seconds apart. iPhone app notifications will do the same, if the app is in the foreground on the phone. But otherwise messages may take up to 15 minutes or longer (this background iOS feature is in Beta, still).

To configure multiple MedImage Proxy Servers, and be notified if the load on your notification servers is getting too high, you will need to add a cron job to your server to be run once per day:

```
sudo crontab -e  
0 0 * * *	/usr/bin/php /yourserverpath/plugins/notifications/check-load.php
```

and another cron-job to clear out any unused, empty, folders, which can be run once per week. Note: This is still undergoing testing (use with caution):
```
sudo crontab -e 
5 8 * * 0    find /yourserverpath/plugins/notifications/outgoing/ -empty -type d -delete
```

You may also need to manually add an outgoing folder that can be written to by the 'www-data' or Apache user:
```
sudo mkdir outgoing
sudo chmod 777 outgoing
```


# Certificate Updates

You will **need to update** your installation once a year if you're using Apple push notifications as the certificate runs out.
Please **log this in your own personal calendar** for a reminder, since the software will not automatically notify you when the certificate runs out.


# TODO

* Handle multiple devices per user (probably as a json array inside the same database field)
* Time-slots for accepting messages
* iPhone pictures in the popup
* Automatic certificate updates
