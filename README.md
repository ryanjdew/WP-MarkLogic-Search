# WP-MarkLogic-Search

## Table of Contents
 - [Overview](#overview)
 - [How It Works](#how-it-works)
 - [Features](#features)
 - [Getting Help](#getting-help)
 - [Requirements](#requirements)
 - [Quickstart](#quickstart)

## Overview
This WordPress plugin adds [MarkLogic](http://www.marklogic.com/what-is-marklogic/) search to your WordPress site.  Speed up and improve accuracy of your search results by utilizing all the benfits of MarkLogic. If you manage multiple websites that are related and want to provide search against a master, centralized database on your WordPress site, this plugin can help you.

## How It Works
The plugin utilizes [MLPHP](https://github.com/marklogic/mlphp) - PHP Connector for MarkLogic REST API - to communicate with MarkLogic database.  After the plugin is installed and configured with a MarkLogic databaes instance, every time new content is created on your site - post, page, or any other content types - it is pushed to MarkLogic via REST API.  Any subsequent updates to existing content will be pushed to MarkLogic as well.

For existing content - i.e. you already have a lot of content on your site when you install this plugin - you can easily push them to MarkLogic with a click of a button.  

When a post or page is deleted, it is also deleted on MarkLogic database as well, making sure that the data is synced between your WordPress site and MarkLogic database.

## Features

### Bulk Insert
If you install this plugin on a site with existing content, you can insert them into MarkLogic in bulk.  It utilizes cron feature in WordPress so that once push button is clicked, you do not have to wait with your browser open.

### Automatic Sync
If you install this plugin on a site with existing content, you can insert them into MarkLogic in bulk.  It utilizes cron feature in WordPress so that once push button is clicked, you do not have to wait with your browser open.

### Faceted Search
Display [facets](https://developer.marklogic.com/blog/faceted-search) in your search results per content type and easily drill down on specific set of search results.


## Getting Help
To get help with this plugin,

* Create [git issues](https://github.com/seongbae/WP-MarkLogic-Search/issues)
* Read up on [MLPHP](https://github.com/marklogic/mlphp)
* Check out [MarkLogic tutorials](https://developer.marklogic.com/learn)
* Take free MarkLogic [training](http://www.marklogic.com/training/)


## Requirements
* MarkLogic 7 or 8  [Download](https://developer.marklogic.com/products)
* MLPHP [Download](https://github.com/marklogic/mlphp)
* PHP 5.6+
* WordPress 4+

## Quick Start
Coming soon...

## Contributing
Contributions are welcome.  Please feel free to clone/fork the project and subnmit pull requests.

## Contact
If you have any quesitons about this project, please contact Seong Bae at seong.bae@marklogic.com.

## Contributors
* [Chris Davis](https://github.com/chrisguitarguy) - wrote most of the WordPress plugin
* [Dave Cassel](https://github.com/dmcassel) - provided support with setting up search configs on MarkLogic


