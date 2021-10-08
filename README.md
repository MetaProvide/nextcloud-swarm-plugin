# Nextcloud Swarm Plugin

This is a plugin for bridging [Nextcloud](https://nextcloud.com) and [Swarm](https://www.ethswarm.org/). The goal with the plugin is to have a way to interact with Swarm storage directly in Nextcloud Files using the external storage feature in Nextcloud.

<img width="1045" alt="Desired view when enabling the plugin" src="https://user-images.githubusercontent.com/3958329/136574298-d87d320f-b3c3-46e8-95f0-2a17974d48f7.png">
<em>Desired file view when using the plugin in Nextcloud</em>

## General Architecture

The whole system needed for the plugin consists of three parts, A Nextcloud instance, a Swarm node and the plugin itself. The plugin itself will contain two distinct parts, the external storage portion and the settings portion.

### External storage

The external storage portion of the plugin will be written in PHP and will handle the actual file operations. As Nextcloud can't directly interact with the Swarm network, everything has to go through a Swarm node. The Swarm node communicates over HTTP, so the individual file system operations will primarily consist of HTTP requests made to the Swarm node using cURL.

The actual file system will be based on a common storage backend class available in Nextcloud, which is intended as an abstraction layer to allow for easier implementation of many different storage backends for Nextcloud. The storage backend class provides the functions Nextcloud expects, and we then need to overwrite those function with an implementation that works with Swarm.

The class can be found here:

https://github.com/nextcloud/server/blob/master/lib/private/Files/Storage/Common.php

and it implements the following interfaces:

* https://github.com/nextcloud/server/blob/master/lib/public/Files/Storage/IStorage.php
* https://github.com/nextcloud/server/blob/master/lib/public/Files/Storage/IWriteStreamStorage.php
* https://github.com/nextcloud/server/blob/master/lib/public/Files/Storage/ILockingStorage.php


File metadata, like with all files in Nextcloud, should be stored in the database. This includes any extra information, for example the swarm encryption key that is used for each encrypted file. A new database table will be needed for any data that can't be discovered anew through the Swarm node, like the before mentioned encryption keys. The rest of the metadata should be stored in the normal filecache table.

The external storage should accept configuration options from two places, the normal external storage settings, as well as the plugin settings. Configuration options that are unique to Swarm should be handled through the settings view and stored in plugin settings, where more common external storage settings should be handled through the external storage settings.

The best documentation we have for how to implement external storage in Nextcloud, is the files_external app included with Nextcloud server:

https://github.com/nextcloud/server/tree/master/apps/files_external

### Settings

The settings portion will be a new section available in the admin settings in Nextcloud. It will utilise the Swarm TypeScript library for handling communication with the Swarm node and use Vue.js for the UI. The goal for the settings page is to allow configuration of the Swarm node directly in Nextcloud, as well as seeing the current status of the Swarm node. The primary reason why this is necessary is to try and create a convenient way of handling the payment system in Swarm directly in Nextcloud. Every operation in Swarm costs money, so we need some way to view how much money is available to the Swarm node, as well as configuring how the external storage portion should deal with creating files in the Swarm network.

A more detailed list of what should be available in the UI can be seen in this issue:

https://github.com/MetaProvide/nextcloud-swarm-plugin/issues/4

The preferred way of storing the settings configured in the settings view is to use the Nextcloud settings API. But if a more advanced settings structure is needed, creating a new database table will be an option.

## Goals for v0.1.0

* Figure out what is needed for a bare-bones file system in Nextcloud and implement read and write file system operations through external storage in Nextcloud.
* Create settings view that allows you to see the status of the Swarm node and configure it.
* Support for toggling the Swarm encryption on and off (Should be on by default).
* Only support for one Swarm node per instance (only allow configuration by admins). Mounting Swarm storage should also only be allowed through the admin settings.

## License

This program is licensed under the AGPLv3 or later.
