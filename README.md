Syrup
========================

Syrup is a framework for rapid development of Keboola Connection components (i.e. extractors).
It is based on Symfony2 framework.

1) Installing Syrup
----------------------------------

When it comes to installing the Syrup, you have the
following options.

- Clone from BitBucket (*recommended*)
- Download an Archive File

Create app/config/parameters.yml from paramters.yml.dist file.

Run "composer install" from command line.

Setup virtual host.


2) Deployment
-------------------------------------

Deployment to production is server is managed by Capifony.

There are 2 stages:

- Local
- Production

To deploy to production server run:

	cap production deploy

This will install the newset release and create a symlink "latest".

You can test this latest release on the url:

https://syrup-latest.keboola.com (https://syrup-latest-a-02.keboola.com or https://syrup-latest-b-02.keboola.com respectively)

When everything is working fine, simply run:

	cap production deploy:create_symlink

To let the "current" symlink point to the latest realease.

Enjoy!

