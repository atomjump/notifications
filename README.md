<img src="https://atomjump.com/images/logo80.png">

__WARNING: this project has now moved to https://src.atomjump.com/atomjump/notifications.git__

# notifications
Send notifications to the iPhone/Android AtomJump messaging app

# Requirements

AtomJump Messaging Server >= 0.8.0
AtomJump Messaging app


# Installation

From within your Loop Server directory:

```
cd plugins
git clone https://git.atomjump.com/notifications.git
cd notifications
cp config/configORIGINAL.json config/config.json
nano config/config.json								[enter your own parameters:
					serverPath is your Messaging Server file path.
					apiKey is the Google GCM apiKey.
					staging is true/false for which Loop Server config to use.]
cp pushcertSAMPLE.pem pushcert.pem				[You will need your own Apple push certicate in here]
php install.php
```

At the user end, the Android/iPhone app at https://src.atomjump.com/atomjump/messaging needs to be installed (this can be built with Cordova, or this is available on the Android and iPhone app-stores as 'AtomJump Messaging')


# AtomJump's own notification system

This notification system option does not depend on any certificates from Android or Apple, but instead lets you connect to one or more installations of the MedImage Cloud Server >= 1.8.7. More details are available here: http://medimage.co.nz/download/

In your config.json file you should switch 'atomjumpNotifications.use' to 'true' to enable this type of notification system.

**Current Limitations**: Android app notifications will appear up to 30 seconds apart. iPhone app notifications will do the same, if the app is in the foreground on the phone.

To configure multiple MedImage Cloud Servers, and be notified if the load on your notification servers is getting too high, you will need to add a cron job to your server to be run once per day:

```
sudo crontab -e  
0 0 * * *	/usr/bin/php /yourserverpath/plugins/notifications/check-load.php
```

and some other cron-jobs to clear out any unused, empty, folders, which can be run once per week. Note: This is still undergoing testing (use with caution):
```
sudo crontab -e 
5 8 * * 0    /usr/bin/find /yourserverpath/plugins/notifications/outgoing/ -empty -type d -delete
```

On the server holding the MedImage Server software, you can do something similar. Note: This is still undergoing testing (use with caution):
```
sudo crontab -e 
5 8 * * 0    /usr/bin/find $(npm prefix -global)/lib/node_modules/medimage/photos -empty -type d -delete
```

You may also need to manually add an outgoing folder in the notifications plugin directory that can be written to by the 'www-data' or Apache user:
```
sudo mkdir outgoing
sudo chmod 777 outgoing
```


# Certificate Updates

You will **need to update** your installation once a year if you're using Apple push notifications as the certificate runs out.
Please **log this in your own personal calendar** for a reminder, since the software will not automatically notify you when the certificate runs out.


# TODO

* Time-slots for accepting messages (although Android and iOS handle this themselves fairly well now. So it would mainly be for browser notifications)
* iPhone pictures in the popup
* Automatic iOS certificate updates
