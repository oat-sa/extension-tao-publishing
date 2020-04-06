# TAO _taoPublishing_ extension

![TAO Logo](https://github.com/oat-sa/taohub-developer-guide/raw/master/resources/tao-logo.png)

![GitHub](https://img.shields.io/github/license/oat-sa/extension-tao-publishing.svg)
![GitHub release](https://img.shields.io/github/release/oat-sa/extension-tao-publishing.svg)
![GitHub commit activity](https://img.shields.io/github/commit-activity/y/oat-sa/extension-tao-publishing.svg)

> Synchronize deliveries, tests and interactions to other tao platforms during the delivery creation.


## Installation instructions

These instructions assume that you have already a TAO installation on your system. If you don't, go to
[package/tao](https://github.com/oat-sa/package-tao) and follow the installation instructions.


Add the extension to your TAO composer and to the autoloader:
```bash
$ composer require oat-sa/extension-tao-publishing ^2.1 --prefer-source
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

To be able to synchronize, you need to set up the platforms that you want to sync. You can do it by:
 - Clicking on Settings Icon (top corner right side) -> Remote environments -> Add Platform.
 - Label: Fill the label with a good name of your choice.
 - Publish Actions: Currently, the Delivery can be synchronized During two events, When creating a delivery (
  DeliveryCreatedEvent) or when updating a delivery (DeliveryUpdatedEvent). You can disable one of the events by removing
  them from config/default/PublishingService.conf.php. Be aware that by disabling the DeliveryCreatedEvent you will 
  not be able to use the  DeliveryUpdatedEvent because the DeliveryCreatedEvent is the only event who sends the test
  to the TAO Platform. But it's possible to disable DeliveryUpdatedEvent with no issues. If you're using
  "extension-tao-task-queue", please don't forget to restart the Queue Consumer after disabling an event.
 - Root URL should be the protocol + hostname of the other TAO platform (Ex: https://demo.taotesting.com/).
 - Auth type: In case of Basic Auth, just use a username and password of a real user in the system that you want to synchronize. Please make sure that user has proper permissions.


## Extension Wiki
<!-- remove this also, when the wiki is empty -->
You can find the [extension wiki here](https://github.com/oat-sa/extension-tao-publishing/wiki).
