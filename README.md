# notifications
Send notifications to the iPhone/Android AtomJump messaging app

This Loop Server plugin is currently at a 'demo' stage, but is not ready for a public release.

# Requirements

AtomJump Loop Server >= 0.7.6
AtomJump Messaging app


# Installation

From within your Loop Server directory:

```
cd plugins
git clone https://github.com/atomjump/notifications.git
cd notifications
cp config/configORIGINAL.json config/config.JSON
nano config/config.json								[enter your own parameters. serverPath is your Loop Server file path. apiKey is the Google GCM apiKey. staging is true/false for which Loop Server config to use.]
php install.php
```

At the user end, the app at https://github.com/atomjump/messaging needs to be installed (this can be built in build.phonegap.com)



# TODO

* Handle multiple devices per user (probably as a json array inside the same database field)
* iPhone message formats (Android is currently supported)
* Time-slots for accepting messages
* Multiple messages from the same forum can be grouped
