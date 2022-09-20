# Nextcloud Swarm Plugin

This is a plugin for bridging [Nextcloud](https://nextcloud.com) and [Swarm](https://www.ethswarm.org/).
The goal with the plugin is to interact seamlessly with Swarm decentralized storage directly in Nextcloud using its common External storage feature.

<img width="1045" alt="View when enabling the plugin" src="https://user-images.githubusercontent.com/3958329/136574298-d87d320f-b3c3-46e8-95f0-2a17974d48f7.png">
<em>File view when using the plugin in Nextcloud</em>

## Table of Contents

- [General Architecture](#general-architecture)
- [External storage](#external-storage)
- [Settings](#settings)
- [Setup / Installation](#setup/installation)
- [Using the Swarm](#using-the-swarm)
- [Limitations](#limitations)
- [License](#license)

## General Architecture

The whole system for the plugin consists of three parts: A Nextcloud instance, a Swarm node and the plugin itself.
The plugin itself contains two distinct parts, the External Storage portion and the Settings portion.

### External storage

File handling operations involving the swarm node are integrated in the External Storage ("Files") section of Nextcloud.

<img alt="View file and action menu" src="/assets/images/swarm_Files.png">
<em>View, download, and file view actions in Nextcloud</em>


Using our plugin, the main file operations that are available in Nextcloud are:

- Upload file(s) to a swarm node
- Download and view files
- Copy/Move files from any External Storage to a swarm node
- Copy files from a swarm node to any External Storage

File metadata, like with all files in Nextcloud, are stored in the Nextcloud database, such as the swarm reference that is used to uniquely identify each file and its mimetype, file size etc. The rest of the metadata is stored in the normal filecache table which is common to all External File storages.

The backend of the plugin is written in PHP which essentially allows communication over HTTP in the form of HTTP requests made to the Swarm node using [cURL](https://github.com/curl/curl).

### Settings

Available to users with administrative credentials, the external storage accepts configuration options from two places, the normal External Storage settings, which allows a user to configure the basic connection properties of a Swarm node:

<img alt="Setup Swarm External Storage" src="/assets/images/swarm_Setup_ExtStorage.png">
<em>Basic Swarm setup in External Storage in Nextcloud administration</em>


Since every operation in Swarm costs money, it is important to view how much money is available to the Swarm node directly and also provide a convenient way of handling the payment system in Swarm - all managed directly in Nextcloud. This is in a specific section with more advanced configuration for the Swarm node, where the following settings can be viewed and edited:

- Configure which Swarm node to manage
- View current status of the Swarm node
- Option to toggle encryption on and off
- How much BZZ is available to the node
- Any purchased stamp batches and the remaining balances
- Have the option to purchase a new batch of stamps
- Make active/inactive the batch to be used for uploading files

<img alt="Setup Swarm External Storage" src="/assets/images/swarm_Setup_Ethswarm_buyStamp1.png">
<em>Advanced configuration of Swarm in Nextcloud administration</em>

It utilises the [ethersphere bee-js](https://github.com/ethersphere/bee-js) Javascript client library for handling communication with the Swarm decentralized storage and uses Vue.js for the UI.

## Setup/Installation

First it is necessary to have a Swarm node running.
For Swarm installation instructions, follow the offical [ethswarm documentation](https://www.ethswarm.org/build#run)

The following steps explain how to configure a Swarm node in NextCloud.

- Install the "External Storage: Swarm" app from the Nextcloud App store
- Navigate to External Storage Administration (Profile menu -> Settings -> External Storage)
- Add the connection settings for a new Swarm node
- Navigate to Ethswarm Storage Administration (Profile menu -> Settings -> Ethswarm Storage)
- Configure the Swarm node - by default, encryption is active
- To use a swarm node, it is necessary to [Purchase a Batch of new stamps](https://docs.ethswarm.org/docs/access-the-swarm/keep-your-data-alive) from the funds in the chequebook

- Once the new batch is purchased, a unique batchId is generated. Once your batch has been purchased, it will take a few minutes for other Bee nodes in the Swarm to catch up and register your batch. Allow some time for your batch to propagate in the network before proceeding to the next step. This is indicated by the checkbox "Usable".
- Uploading files to the swarm costs Bzz, so it is necessary to select a Batch as "Active". Only 1 batch is allowed to be Active for a given swarm node. Then click "Save Settings".

## Using the Swarm

Once setup and configured, the swarm node is ready to use.
- Navigate to "Files" option on the menu -> "External Storage" and then the name of the Swarm node.
- Click to Upload file(s):

<img alt="Upload file" src="/assets/images/swarm_Files_UploadFile.png">
<em>Upload file to swarm in Nextcloud</em>

- Once uploaded, it can be viewed internally or downloaded. It can also be copied to another storage.

<img alt="View file and action menu" src="/assets/images/swarm_Files_ViewFile.png">
<em>View, download, and file view actions in Nextcloud</em>


- Files from another Nextcloud storage can also be copied/moved to the Swarm node. Choose the Swarm node as the target Folder

<img alt="Upload file" src="/assets/images/swarm_CopyMove.png">
<em>Upload file to swarm in Nextcloud</em>

## Limitations

Not all adminstrative operations are available in the plugin. For an up-to-date list of available apps, please consult the [documentation](https://www.ethswarm.org/build#run)

## License

This program is licensed under the AGPLv3 or later.
