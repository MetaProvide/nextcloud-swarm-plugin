# **HejBit - Nextcloud Swarm Plugin**

_Bring decentralized, sovereign cloud storage to Nextcloud with the HejBit Swarm plugin!_

This plugin integrates [Swarm](https://www.ethswarm.org/) (a decentralized, blockchain-connected file storage system) directly into your [Nextcloud ](https://nextcloud.com/)instance.

![File view when using the plugin in Nextcloud](https://github.com/user-attachments/assets/8e773a9f-fb3d-4303-a6be-df2fbe8a25fb)

## Table of Contents

-   [Dependencies](#dependencies)
-   [Setup and Installation](#setup-and-installation)
-   [Usage](#usage)
    -   [Settings](#settings)
    -   [Using HejBit Swarm](#using-hejbit-swarm)
    -   [Additional Integration with Moodle](#additional-integration-with-moodle)
-   [HejBit Free Trial](#hejbit-free-trial)
-   [License](#license)

## Dependencies

Before using the plugin, ensure you have the following:

-   An active Nextcloud instance (version 30 or 31)
-   A valid Access Key and URL to activate the service
    -   [Get Your Free Trial for the Nextcloud Swarm Plugin](https://metaprovide.org/hejbit/start)

## Setup and Installation

Follow these steps to configure HejBit Swarm in Nextcloud:

1. Ensure the "External Storage" plugin is enabled.
2. Install the "External Storage: HejBit Swarm" app from the Nextcloud App Store.
3. Navigate to External Storage Administration: Profile menu -> Settings -> External Storage.
4. Create a new external storage with a folder name of your choice and select 'HejBit-Swarm' as the storage type. Then, enter the Access Key and URL configuration.

## Usage

Once installed, HejBit Swarm integrates directly with Nextcloud’s "Files" section, allowing seamless file operations:

-   **Upload:** Transfer files to decentralized storage.
-   **Download/View:** Retrieve and view files from decentralized storage.
-   **Copy/Move:** Shift files between any external storage and decentralized storage.
-   **Rename:** Change file names as needed.
-   **Archive/Restore:** Manage your files by archiving or restoring them.
-   **Hide/Show:** Control the visibility of files in your storage.
-   **View Swarm Reference:** Access the unique Swarm reference for each file.
-   **Export all Swarm References:** Backup all your Swarm references easily.

Additionally, we have introduced a HejBit Feedback button that appears in the bottom left corner when you are inside a HejBit folder. This feature allows users to easily send feedback regarding general inquiries, ideas, or issues directly from the plugin.


### Settings

Users with administrative credentials can configure the plugin through the standard External Storage settings. Here, you can input your Access Key and URL for HejBit Swarm.

![Basic HejBit Swarm setup in External Storage in Nextcloud administration](https://github.com/user-attachments/assets/3e80f664-86b9-4cfa-bbc5-6f9f1fbeb94a)


### Usage

Once setup and configured, the Decentralized Storage is ready to use.

-   Access the Decentralized Storage folder under "All Files" or via the "Files" menu under "External Storage."
-   Upload files as you would in Nextcloud.

![Upload file to swarm in Nextcloud](https://github.com/user-attachments/assets/ac3ce8b2-56f4-4729-be05-242cd9bec729)

-   Once uploaded, it can be viewed internally or downloaded. It can also be copied to another storage.

![View, download, and file view actions in Nextcloud](https://github.com/user-attachments/assets/db4746a4-1dcf-4264-82d0-ff869a2b183e)

-   Files from another Nextcloud storage can also be copied/moved to the decentralized storage. Choose the decentralized storage folder as the target Folder.

![Copying or moving files to decentralized storage folder](https://github.com/user-attachments/assets/5b9ece14-07e6-4d44-b707-8468c04b26d4)

-   To access a file's Swarm reference (its unique swarm network address), you have two options: right-click directly on the file and select the reference option, or use the three-dot Actions menu and choose 'View Swarm Reference.' Both methods will display the hash, which you can then copy to your clipboard for sharing or future access.

![Copy swarm reference to clipboard](https://github.com/user-attachments/assets/3e5de911-cfb0-4d4e-8a46-a2138c2cc254)

-   To effectively organize your files, you can utilize the Archive and Restore features for both folders and individual files, allowing for better management of your storage.

![Archive and restore files in decentralized storage](https://github.com/user-attachments/assets/3b8e2232-7f99-44fa-8589-8f48c473dcc1)

-   For a comprehensive backup of all your Swarm hashes, you can easily export all Swarm references directly from the menu located in the root HejBit Swarm folder.

![Export all swarm references from HejBit Swarm folder](https://github.com/user-attachments/assets/3aa10f33-6caf-4fac-a9a2-fcf9bdb17ae3)

-   We encourage you to share your thoughts and experiences with us! A **HejBit Feedback** button is conveniently located in the bottom left corner when you are inside a HejBit folder. This feature allows you to provide feedback on any inquiries, suggestions, or issues you may encounter, helping us enhance your experience and improve the plugin for all users.

![Send Feedback directly from the plug-in](https://github.com/user-attachments/assets/3ecf2c98-a1ec-4677-b3a2-a9d596dfb11a)

### Additional Integration with Moodle

You can also enhance your experience by integrating the HejBit Swarm plugin with the **Moodle Nextcloud plugin**. This integration allows users to access decentralized storage directly within the Moodle environment, providing a seamless experience for educational content management. For more information on how to set up this integration, visit the [Moodle Nextcloud repository documentation](https://docs.moodle.org/405/en/Nextcloud_repository).

For detailed guidance on using the integrated features, please refer to the [Metaprovide Learning Platform](https://learning.metaprovide.org/login/index.php).

## HejBit Free Trial

Experience the future of data storage with 5GB of free, decentralized storage on the Ethereum Swarm network. This offer is available as a 14-day free trial, allowing you to explore the capabilities of our plugin without any commitment. HejBit seamlessly integrates with your existing Nextcloud interface, providing true data sovereignty without the complexity. Enjoy the benefits of decentralized storage and take control of your data today!

[Get your Free Trial here!](https://metaprovide.org/hejbit/start)

## License

This program is licensed under the AGPLv3 or later.
