# TAO _taoPublishing_ extension

![TAO Logo](https://github.com/oat-sa/taohub-developer-guide/raw/master/resources/tao-logo.png)

![GitHub](https://img.shields.io/github/license/oat-sa/extension-tao-publishing.svg)
![GitHub release](https://img.shields.io/github/release/oat-sa/extension-tao-publishing.svg)
![GitHub commit activity](https://img.shields.io/github/commit-activity/y/oat-sa/extension-tao-publishing.svg)

> Publish deliveries, tests and interactions to other tao platforms.


## Installation instructions

These instructions assume that you have already a TAO installation on your system. If you don't, go to
[package/tao](https://github.com/oat-sa/package-tao) and follow the installation instructions.


Add the extension to your TAO composer and to the autoloader:
```bash
$ composer require oat-sa/extension-tao-publishing ^3 --prefer-source
```

Install the extension on the CLI from the project root:

**Linux:**
```bash
sudo php tao/scripts/installExtension.php taoPublishing
```

**Windows:**
```bash
php tao\scripts\installExtension.php taoPublishing
```

**Docker**
```bash
$ docker container exec -it {YOUR-TAO-INSTANCE-PHP-CONTAINER} php tao/scripts/installExtension.php taoPublishing
```

After the install just log out and log in again on the TAO Platform.

As a system administrator you can also install it through the TAO Extension Manager:
- Settings (the gears on the right hand side of the menu) -> Extension manager
- Select _taoPublishing_ on the right hand side, check the box and hit _install_

## Set up deliveries on TAO Platform

To be able to publish, you need to set up the publication targets that you want to publish. You can do it by:
 - Clicking on Settings Icon (top corner right side) -> Publication Targets -> Add Button.
 - Label: Fill the label with a good name of your choice.
 - Root URL should be the protocol + hostname of the publication target (other TAO platform). Ex: https://demo.taotesting.com/.
 - Auth type: In case of Basic Auth, just use an username and password of a real user in the system that you want to publish. Please make sure that user has proper permissions.

After this steps, we're able to see a new button called publish on the delivery dialog.
To do a publication, just click on it and follow the proper steps on the publication dialog.