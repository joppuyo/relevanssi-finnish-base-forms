<?php

/*
Plugin Name: Relevanssi Finnish Base Forms
Plugin URI: https://github.com/joppuyo/relevanssi-finnish-base-forms
Description: Relevanssi plugin to add Finnish base forms in search index
Version: 1.1.0
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: relevanssi-finnish-base-forms
*/

defined('ABSPATH') or die('I wish I was using a real MVC framework');

// Check if we are using local Composer
if (file_exists(__DIR__ . '/vendor')) {
    require 'vendor/autoload.php';
}

class FinnishBaseForms {

    // This is used in the admin UI
    private $plugin_name = 'Relevanssi';

    // This is used for option keys etc.
    private $plugin_slug = 'relevanssi';

    public function __construct()
    {
        // Add settings link on the plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
            $settings_link = "<a href=\"options-general.php?page={$this->plugin_slug}_finnish_base_forms\">" . __('Settings', "{$this->plugin_slug}_finnish_base_forms") . '</a>';
            array_push($links, $settings_link);
            return $links;
        });

        // Ajax endpoint to test that lemmatization works
        add_action("wp_ajax_{$this->plugin_slug}_finnish_base_forms_test", function () {
            $api_type = $_POST['api_type'];
            if ($api_type === 'binary' || $api_type === 'command_line') {
                $baseforms = $this->voikkospell(['käden'], $api_type);
            } else {
                $baseforms = $this->web_api(['käden'], $_POST['api_root']);
            }
            if (count($baseforms) && $baseforms === ['käsi']) {
                wp_die();
            } else {
                wp_die('', '', ['response' => 500]);
            }
        });

        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook !== "settings_page_{$this->plugin_slug}_finnish_base_forms") {
                return;
            }
            wp_enqueue_script("{$this->plugin_slug}-finnish-base-forms-js", plugin_dir_url(__FILE__) . '/js/script.js');
        });

        // If plugin is installed, pass all content through lemmatization process
        if (get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line'])) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_indexer_pre_process_content', function ($content) {
                    return $this->lemmatize($content);
                });
            } else if ($this->plugin_slug === 'relevanssi') {
                add_filter('relevanssi_post_content_before_tokenize', function ($content) {
                    return $this->lemmatize($content);
                });
                add_filter('relevanssi_post_title_before_tokenize', function ($content) {
                    return $this->lemmatize($content);
                });
                add_filter('relevanssi_custom_field_value', function ($content) {
                    return [$this->lemmatize($content[0])];
                });
            }
        }

        // If "lemmatize search query" option is set, pass user query through lemmatization
        if ((get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line'])) && get_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query")) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_pre_search_terms', function ($terms, $engine) {
                    $terms = implode(' ', $terms);
                    $terms = $this->lemmatize($terms);
                    $terms = explode(' ', $terms);
                    $terms = array_unique($terms);

                    return $terms;
                }, 10, 2);

                // Double amount of maximum search terms just to be sure (the default is 6)
                add_filter('searchwp_max_search_terms', function ($max_terms, $engine) {
                    return get_option('searchwp_finnish_base_forms_split_compound_words') ? 24 : 12;
                }, 10, 2);

                // By default SearchWP will try AND logic first and after that OR logic if there are no results.
                // Because we have the same search term multiple times, we want to always use OR logic
                add_filter('searchwp_and_logic', '__return_false');
            } else if ($this->plugin_slug === 'relevanssi') {
                add_filter('relevanssi_search_filters', function ($parameters) {
                    $parameters['q'] = $this->lemmatize($parameters['q']);
                    return $parameters;
                });
            }
        }

        // Add plugin to WordPress admin menu
        add_action('admin_menu', function () {
            add_submenu_page(
                null,
                __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
                __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
                'manage_options',
                "{$this->plugin_slug}_finnish_base_forms",
                [$this, 'settings_page']
            );
        });
    }

    /**
     * Append lemmatized words to the original text
     * @param $content
     * @return string
     * @throws Exception
     */
    private function lemmatize($content)
    {
        $tokenized = $this->tokenize(strip_tags($content));

        $api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type") ? get_option("{$this->plugin_slug}_finnish_base_forms_api_type") : 'binary';

        if ($api_type === 'binary' || $api_type === 'command_line') {
            $extra_words = $this->voikkospell($tokenized, $api_type);
        } else {
            $api_root = get_option("{$this->plugin_slug}_finnish_base_forms_api_url");
            $extra_words = $this->web_api($tokenized, $api_root);
        }

        $content = trim($content . ' ' . implode(' ', $extra_words));

        return $content;
    }

    /**
     * Simple white space tokenizer. Breaks either on whitespace or on word
     * boundaries (ex.: dots, commas, etc) Does not include white space or
     * punctuations in tokens.
     *
     * Based on NlpTools (http://php-nlp-tools.com/) under WTFPL license.
     *
     * @param $str
     * @return mixed
     */
    function tokenize($str)
    {
        $arr = [];
        // for the character classes
        // see http://php.net/manual/en/regexp.reference.unicode.php
        $pat
            = '/
                ([\pZ\pC]*)       # match any separator or other
                                  # in sequence
                (
                    [^\pP\pZ\pC]+ # match a sequence of characters
                                  # that are not punctuation,
                                  # separator or other
                )
                ([\pZ\pC]*)       # match a sequence of separators
                                  # that follows
            /xu';
        preg_match_all($pat, $str, $arr);

        return $arr[2];
    }

    /**
     * Render WordPress plugin settings page
     */
    public function settings_page()
    {
        $updated = false;
        if (!empty($_POST)) {
            check_admin_referer("{$this->plugin_slug}_finnish_base_forms");
            update_option("{$this->plugin_slug}_finnish_base_forms_api_url", $_POST['api_url']);

            update_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query", !empty($_POST['lemmatize_search_query']) && $_POST['lemmatize_search_query'] === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words", !empty($_POST['split_compound_words']) && $_POST['split_compound_words']  === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_api_type", in_array($_POST['api_type'], ['binary', 'command_line', 'web_api']) ? $_POST['api_type'] : 'command_line');
            $updated = true;
        }

        $api_url = get_option("{$this->plugin_slug}_finnish_base_forms_api_url");
        $api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type") ? get_option("{$this->plugin_slug}_finnish_base_forms_api_type") : 'binary';

        echo '<div class="wrap">';
        echo '    <h1>' . __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms") . '</h1>';
        echo '    <div class="js-finnish-base-forms-admin-notices"></div>';
        if ($updated) {
            echo '    <div class="notice notice-success">';
            echo '        <p>' . __('Options have been updated', "{$this->plugin_slug}_finnish_base_forms") . '</p>';
            echo '    </div>';
        }
        echo '    <form method="post" class="js-finnish-base-forms-form" data-slug="' . $this->plugin_slug . '">';
        echo '    <table class="form-table">';
        echo '        <tbody>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label for="api_url">' . __('API type', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <p><input type="radio" id="binary" name="api_type" value="binary" ' . checked($api_type, 'binary', false) . '><label for="binary">Voikko binary (bundled)</label></p>';
        echo '                <p><input type="radio" id="web_api" name="api_type" value="web_api" ' . checked($api_type, 'web_api', false) . '><label for="web_api">Web API</label></p>';
        echo '                <p><input type="radio" id="command_line" name="api_type" value="command_line" ' . checked($api_type, 'command_line', false) . '><label for="command_line">Voikko command line</label></p>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr class="js-finnish-base-forms-api-url">';
        echo '                <th scope="row">';
        echo '                    <label for="api_url">' . __('Web API URL', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input name="api_url" type="url" id="api_url" value="' . esc_url($api_url) . '" class="regular-text">';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th colspan="2">';
        echo '                <span style="font-weight: 400">Note: "Voikko command line" option requires voikkospell command line application installed on the server.</span>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr class="js-finnish-base-forms-split-compound-words">';
        echo '                <th scope="row">';
        echo '                    <label>' . __('Split compound words', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input type="checkbox" name="split_compound_words" id="split_compound_words" value="checked" ' . checked(get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words"), '1', false) . ' />';
        echo '                <label for="split_compound_words">Enabled</label>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label>' . __('Add base forms to search query', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input type="checkbox" name="lemmatize_search_query" id="lemmatize_search_query" value="checked" ' . checked(get_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query"), '1', false) . ' />';
        echo '                <label for="lemmatize_search_query">Enabled</label>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th colspan="2">';
        echo '                <span style="font-weight: 400">Note: if you enable "Add base forms to search query", Voikko will be called every time a search if performed, this might have performance implications.</span>';
        echo '                </td>';
        echo '            </tr>';
        echo '        </tbody>';
        echo '    </table>';
        echo '    <p class="submit">';
        echo '        <input class="button-primary js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
        echo '    </p>';
        wp_nonce_field("{$this->plugin_slug}_finnish_base_forms");
        echo '    </form>';
        echo '</div>';
    }

    /**
     * Split compound words into word bases
     * @param $wordbases
     * @return array
     */
    function parse_wordbases($wordbases)
    {
        $baseforms = [];
        foreach ($wordbases as $wordbase) {
            preg_match_all('/\(([^+].*?)\)/', $wordbase, $matches);
            foreach ($matches[1] as $match) {
                array_push($baseforms, str_replace('=', '', $match));
            }
        }
        return $baseforms;
    }

    /**
     * @param $words
     * @param $api_type 'binary' or 'command_line'
     * @return array
     * @throws Exception
     */
    function voikkospell($words, $api_type)
    {
        $binaryPath = null;
        if ($api_type === 'binary') {
            $path = plugin_dir_path(__FILE__);
            $this->ensure_permissions("{$path}bin/voikkospell");
            $binaryPath = "{$path}bin/voikkospell -p {$path}bin/dictionary";
        } else {
            $binaryPath = 'voikkospell';
        }

        $process = new \Symfony\Component\Process\Process('locale -a | grep -i "utf-\?8"');
        $process->run();
        $locale = strtok($process->getOutput(), "\n");

        $process = new \Symfony\Component\Process\Process("$binaryPath -M", null, [
            'LANG' => $locale,
            'LC_ALL' => $locale,
        ]);
        $process->setInput(implode($words, "\n"));
        $process->run();

        if ($process->getErrorOutput()) {
            throw new Exception($process->getErrorOutput());
        }

        preg_match_all('/BASEFORM=(.+)$/m', $process->getOutput(), $matches);
        $baseforms = $matches[1];

        $wordbases = [];

        if (get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words")) {
            preg_match_all('/WORDBASES=(.+)$/m', $process->getOutput(), $matches);
            $wordbases = $this->parse_wordbases($matches[1]);
        }

        return array_unique(array_merge($baseforms, $wordbases));
    }

    function web_api($tokenized, $api_root)
    {
        $client = new \GuzzleHttp\Client();

        $extra_words = [];

        $split_compound_words = get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words");

        $requests = function () use ($client, $tokenized, $api_root) {
            foreach ($tokenized as $token) {
                yield function () use ($client, $token, $api_root) {
                    return $client->getAsync(trailingslashit($api_root) . 'analyze/' . $token);
                };
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(), [
            'concurrency' => 10,
            'fulfilled' => function ($response) use (&$extra_words, $split_compound_words) {
                $response = json_decode($response->getBody()->getContents(), true);
                if (count($response)) {
                    $baseforms = array_column($response, 'BASEFORM');
                    $wordbases = [];
                    if ($split_compound_words) {
                        $wordbases = $this->parse_wordbases(array_column($response, 'WORDBASES'));
                    }
                    $extra_words = array_unique(array_merge($extra_words, $baseforms, $wordbases));
                }
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $extra_words;
    }

    /**
     * Make sure binary is executable
     * @param string $path
     */
    function ensure_permissions($path) {
        $permissions = substr(sprintf('%o', fileperms($path)), -4);
        if ($permissions !== '0755') {
            chmod($path, 0755);
        }
    }

}

new FinnishBaseForms();