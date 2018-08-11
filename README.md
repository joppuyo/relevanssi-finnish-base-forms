# Relevanssi Finnish Base Forms

[![Latest Stable Version](https://poser.pugx.org/joppuyo/relevanssi-finnish-base-forms/v/stable)](https://packagist.org/packages/joppuyo/relevanssi-finnish-base-forms)

Relevanssi plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

## What does it do?

This plugin allows you to add base forms of Finnish words in Relevanssi search index. For example for word *koirillekin*, tokens *koirillekin* and *koira* are saved in the index during indexing. Learn mode by reading [this article](https://www.creuna.com/fi/ajankohtaista/wordpress-haku-searchwp-voikko/) (in Finnish).

## Options

### API type

You can use bundled **voikkospell binary** (on a linux x64 system) to lemmatize the indexed terms.

There is also an option to use a system-wide **voikkospell command line application** if you have Voikko installed on your system.

It's also possible to set up an external **Node.js API** to access Voikko over HTTP. Using the binary or CLI application is much faster because it doesn't have the overhead of performing a HTTP request.

Special thanks to [siiptuo](https://github.com/siiptuo) for contributing voikkospell support for this plugin!

### Add base forms to search query

Enable this option to add base forms to search queries entered by users.

### Split compound words

Enable this option to split compound words during indexing (and for user queries if the above option is enabled). For example, the word *kerrostaloille* is transformed into tokens *kerrostaloille*,  *kerrostalo*, *kerros* and *talo* in the search index.

## Requirements

* Relevanssi 4.0.4 or later
* PHP 5.5.9 or later
* One of the following:
  * A x64 Linux server 
  * A server with voikkospell command line application installed
  * A server with  Node.js and about 1GB of spare RAM

## Installation

1. **Download** latest version from the [releases](https://github.com/joppuyo/relevanssi-finnish-base-forms/releases) tab
2. **Unzip** the plugin into your `wp-content/plugins` directory
3. **Activate** SearchWP Finnish Base Forms from your Plugins page

### Bundled voikkospell binary

1. Go on the Plugins page, find the plugin, click **Settings**. For **API Type** select **Voikko binary (bundled)**.

### Voikkospell command line

1. Install voikkospell on your server. On Ubuntu/Debian this can be done with `apt install libvoikko-dev voikko-fi`
2. Go on the Plugins page, find the plugin, click **Settings**. For **API Type** select **Voikko command line**.

### Node.js web API

1. Install and start [Voikko Node.js web API](https://github.com/joppuyo/voikko-node-web-api).
2. Go on the Plugins page, find the plugin, click **Settings** and enter the Node API URL there

After installation, remember to re-index the site from Relevanssi settings page.

