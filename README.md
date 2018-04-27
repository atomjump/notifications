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
git clone https://github.com/atomjump/notifications.git
cd notifications
cp config/configORIGINAL.json config/config.json
nano config/config.json								[enter your own parameters. serverPath is your Loop Server file path. apiKey is the Google GCM apiKey. staging is true/false for which Loop Server config to use.]
php install.php
```

At the user end, the Android/iPhone app at https://github.com/atomjump/messaging needs to be installed (this can be built in build.phonegap.com, or this is available on the Android and iPhone app-stores as 'AtomJump Messaging')



# TODO

* Handle multiple devices per user (probably as a json array inside the same database field)
* Time-slots for accepting messages
* Multiple messages from the same forum can be grouped
