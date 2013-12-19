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

Local stage is used to deploy Syrup to devel server.
Production is used to deploy to 2 production servers - syrup-a and syrup-b.

To deploy to production server run:

	cap production deploy

This will install the newset release and create a symlink "latest" that points to this release.

You can test this latest release on the url:

Production

	Latest: https://syrup-latest.keboola.com (https://syrup-latest-a-02.keboola.com or https://syrup-latest-b-02.keboola.com respectively)
	Current: https://syrup.keboola.com (https://syrup-a-02.keboola.com or https://syrup-b-02.keboola.com)

Devel:

	Latest: http://syrup-testing.kbc-devel-02.keboola.com
	Current: http://syrup-current.kbc-devel-02.keboola.com


When everything is working fine, run:

	cap production deploy:create_symlink

To let the "current" symlink point to the latest realease.

Enjoy!

