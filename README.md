# Relevanssi Finnish Base Forms

Relevanssi plugin to add Finnish base forms into search index using [Voikko](https://voikko.puimula.org/).

You can either use **Node.js application** to access Voikko over HTTP or use a locally installed **voikkospell command line application** to lemmatize the indexed terms. Special thanks to [siiptuo](https://github.com/siiptuo) for contributing voikkospell support for this plugin!

The CLI application is much faster because it doesn't have the overhead performing a HTTP request.

## Requirements

* Relevanssi 4.0.4 or later
* PHP 5.5.9
* A server with either Node.js and about 1GB of spare RAM or voikkospell command line application installed

## Installation

1. Clone this plugin into **wp-content/plugins**
2. Go to **wp-content/plugins/relevanssi-finnish-base-forms** and run **composer install**
3. **Activate** Relevanssi Finnish Base Forms from your Plugins page

### Node.js web API

1. Install and start [Voikko Node.js web API](https://github.com/joppuyo/voikko-node-web-api).
2. Go on the Plugins page, find the plugin, click **Settings** and enter the Node API URL there

### Voikkospell command line

1. Install voikkospell on your server. On Ubuntu/Debian this can be done with `apt install libvoikko-dev voikko-fi`
2. Go on the Plugins page, find the plugin, click **Settings**. For **API Type** select **Voikko command line**.

After installation, remember to re-index the site from Relevanssi settings page.

