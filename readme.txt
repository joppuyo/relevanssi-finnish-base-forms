=== Relevanssi Finnish Base Forms ===
Contributors: joppuyo
Tags: relevanssi, finnish, stem, stemming, lemmatization, lemmatisation
Requires at least: 4.9.4
Tested up to: 4.9.4
Requires PHP: 5.5.9 or greater
License: License: GPLv3 or later

Relevanssi plugin to add Finnish base forms in search index

== Description ==
Relevanssi plugin to add Finnish base forms in search index. Requires Node.js and Relevanssi 4.0.4 or later.

== Installation ==
1. Clone this plugin into **wp-content/plugins**
2. **Activate** Relevanssi Finnish Base Forms from your Plugins page
3. Either install Node.js application or voikkospell command line application
4. Configure plugin in **Plugins** and **Settings** under Relevanssi Finnish Base Forms

== Changelog ==
= 1.1.2 =
* Index multiple base forms also when using web API
* Change symfony/process version so it can be installed on both PHP 5 and PHP 7

= 1.1.1 =
* Fix issue where sometimes there were too few search results

= 1.1.0 =
* Allow using local voikkospell command line application instead of the Node web API

= 1.0.3 =
* Fix issue with “Add base forms to search query” option

= 1.0.2 =
* Add option to add base forms to search queries entered by users
* Normalize API URL trailing slash

= 1.0.1 =
* Allow using local plugin Composer or global Bedrock Composer

= 1.0.0 =
* Initial release
