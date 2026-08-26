This is the new version of the FrontendForms module, which can be downloaded for testing purposes. It is planned to replace the current version of the FrontendForms module with this version once it has been thoroughly tested.

Please do not use this version on live sites at the moment!

To test this version, please follow these steps:

1. Download the ZIP file from GitHub and unzip it on your computer.
2. Rename the unzipped folder from FrontendForms3_main to FrontendForms.
3. Copy the folder to your site/modules folder and click the module refresh button. If you have the default version of FrontendForms installed, the migration script should run, update all files to the latest version, and remove all orphaned files from the old version.
4. Start trying out and testing the new module version.
5. Report any bugs or issues directly here on GitHub.

After successful testing, this will become the new default version.
This new version has been tested with more than 1,700 unit tests, so it should work very well. However, it also needs to be tested manually.

All of the huge classes have been split into several smaller classes.

A lot of security vulnerabilities have been fixed (e.g. ZIP bombs, MIME type spoofing, etc.).

In general, the code is now more scalable and testable.

I will provide more information about the new validation rules and features that have been added in the coming days.
