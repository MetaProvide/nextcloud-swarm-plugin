# **Hejbit - Nextcloud Swarm Plugin**

_Bring decentralized, sovereign cloud storage to Nextcloud with the Hejbit Swarm plugin!_

This plugin integrates [Swarm](https://www.ethswarm.org/) (a decentralized, blockchain-connected file storage system) directly into your [Nextcloud ](https://nextcloud.com/)instance.

![File view when using the plugin in Nextcloud](https://github.com/user-attachments/assets/24f89fd9-f8eb-47e3-97ac-e1da41e48072)

## Table of Contents

-   [Dependencies](#dependencies)
-   [Setup and Installation](#setup-and-installation)
-   [Usage](#usage)
    -   [Settings](#settings)
    -   [Using Hejbit Swarm](#using-hejbit-swarm)
    -   [Additional Integration with Moodle](#additional-integration-with-moodle)
-   [Get Hejbit License](#get-hejbit-license)
-   [License](#license)

## Dependencies

Before using the plugin, ensure you have the following:

-   An active Nextcloud instance (version 27,28 or 29)
-   A valid license and URL to activate the service
    -   [Request Your Free Nextcloud Swarm Plugin Trial License](https://metaprovide.org/hejbit/start)

## Setup and Installation

Follow these steps to configure Hejbit Swarm in Nextcloud:

1. Ensure the "External Storage" plugin is enabled.
2. Install the "External Storage: Hejbit Swarm" app from the Nextcloud App Store.
3. Navigate to External Storage Administration: Profile menu -> Settings -> External Storage.
4. Create a new external storage with a folder name of your choice and select 'hejbit-swarm' as the storage type. Then, enter the license key and URL configuration.

## Usage

Once installed, Hejbit Swarm integrates directly with Nextcloud’s "Files" section, allowing seamless file operations:

-   **Upload:** Transfer files to decentralized storage.
-   **Download:** Retrieve and view files from decentralized storage.
-   **Copy/Move:** Shift files between any external storage and decentralized storage

![View, download, and file view actions in Nextcloud](https://github.com/user-attachments/assets/3bee08e4-7a9e-4b44-a904-821359cc3e7b)

### Settings

Users with administrative credentials can configure the plugin through the standard External Storage settings. Here, you can input your license key and URL for Hejbit Swarm.

![Basic Hejbit Swarm setup in External Storage in Nextcloud administration](https://github.com/user-attachments/assets/47e0cdda-5c1a-4464-a752-cf1eb5eeb19d)

### Usage

Once setup and configured, the Decentralized Storage is ready to use.

-   Access the Decentralized Storage folder under "All Files" or via the "Files" menu under "External Storage."
-   Upload files as you would in Nextcloud.

![Upload file to swarm in Nextcloud](https://github.com/user-attachments/assets/aadd664e-26ca-470a-a27b-af8d94351e52)

-   Once uploaded, it can be viewed internally or downloaded. It can also be copied to another storage.

![View, download, and file view actions in Nextcloud](https://github.com/user-attachments/assets/596b72ed-d97f-48ba-bcb9-0ee5ff581a3c)

-   Files from another Nextcloud storage can also be copied/moved to the decentralized storage.. Choose the decentralized storage folder as the target Folder

![Copying or moving files to decentralized storage folder](https://github.com/user-attachments/assets/ceed3585-f7e6-4f16-b371-d61402e9f1e9)

-   Right-click on a Swarm file to copy the Swarm reference (hash) to your clipboard. Alternatively, click the three dots in the Actions menu and select 'Copy Swarm Reference.' The Swarm reference is the unique address of the file on the Swarm network.

![Copy swarm reference to clipboard](https://github.com/user-attachments/assets/cc73282b-e32e-411f-a94b-a2ac3313f60b)

### Additional Integration with Moodle

You can also enhance your experience by integrating the Hejbit Swarm plugin with the **Moodle Nextcloud plugin**. This integration allows users to access decentralized storage directly within the Moodle environment, providing a seamless experience for educational content management. For more information on how to set up this integration, visit the [Moodle Nextcloud repository documentation](https://docs.moodle.org/405/en/Nextcloud_repository).

For detailed guidance on using the integrated features, please refer to the [Metaprovide Learning Platform](https://learning.metaprovide.org/login/index.php).

## Get Hejbit License

Experience the future of data storage with 5GB of free, decentralized storage on the Ethereum Swarm network. Our plugin seamlessly integrates with your existing Nextcloud interface, providing true data sovereignty without the complexity.

[Get your free licence here!](https://metaprovide.org/hejbit/start) .

## License

This program is licensed under the AGPLv3 or later.
