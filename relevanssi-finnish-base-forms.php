<?php
/*
Plugin Name: Relevanssi Finnish Base Forms
Plugin URI: https://github.com/joppuyo/relevanssi-finnish-base-forms
Description: Relevanssi plugin to add Finnish base forms in search index
Version: 1.0.0
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: relevanssi-finnish-base-forms
*/

defined('ABSPATH') or die('I wish I was using a real MVC framework');

// Check if we are using local Composer
if (file_exists(__DIR__ . '/vendor')) {
    require 'vendor/autoload.php';
}

if (get_option('relevanssi_finnish_base_forms_api_url') || get_option('relevanssi_finnish_base_forms_api_type') === 'command_line') {
    add_filter('relevanssi_post_content_before_tokenize', function ($content) {
        return relevanssi_finnish_base_forms_lemmatize($content);
    });
    add_filter('relevanssi_post_title_before_tokenize', function ($content) {
        return relevanssi_finnish_base_forms_lemmatize($content);
    });
    add_filter('relevanssi_custom_field_value', function ($content) {
        return [relevanssi_finnish_base_forms_lemmatize($content[0])];
    });
}

if ((get_option('relevanssi_finnish_base_forms_api_url') || get_option('relevanssi_finnish_base_forms_api_type') === 'command_line') && get_option('relevanssi_finnish_base_forms_lemmatize_search_query')) {
    add_filter('relevanssi_search_filters', function ($parameters) {
        $parameters['q'] = relevanssi_finnish_base_forms_lemmatize($parameters['q']);
        return $parameters;
    });
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="options-general.php?page=relevanssi_finnish_base_forms">' . __('Settings', 'relevanssi_finnish_base_forms') . '</a>';
    array_push($links, $settings_link);
    return $links;
});

function relevanssi_finnish_base_forms_settings_page()
{
    $updated = false;
    if (!empty($_POST)) {
        check_admin_referer('relevanssi_finnish_base_forms');
        update_option('relevanssi_finnish_base_forms_api_url', $_POST['api_url']);
        update_option('relevanssi_finnish_base_forms_lemmatize_search_query', $_POST['lemmatize_search_query'] === 'checked' ? 1 : 0);
        update_option('relevanssi_finnish_base_forms_api_type', $_POST['api_type'] === 'web_api' ? 'web_api' : 'command_line');
        $updated = true;
    }

    $apiUrl = get_option('relevanssi_finnish_base_forms_api_url');
    $apiType = get_option('relevanssi_finnish_base_forms_api_type') ? get_option('relevanssi_finnish_base_forms_api_type') : 'web_api';

    echo '<div class="wrap">';
    echo '    <h1>' . __('Relevanssi Finnish Base Forms', 'relevanssi_finnish_base_forms') . '</h1>';
    echo '    <div class="js-relevanssi-finnish-base-forms-admin-notices"></div>';
    if ($updated) {
        echo '    <div class="notice notice-success">';
        echo '        <p>' . __('Options have been updated', 'relevanssi_finnish_base_forms') . '</p>';
        echo '    </div>';
    }
    echo '    <form method="post" class="js-relevanssi-finnish-base-forms-form">';
    echo '    <table class="form-table">';
    echo '        <tbody>';
    echo '            <tr>';
    echo '                <th scope="row">';
    echo '                    <label for="api_url">' . __('API type', 'relevanssi_finnish_base_forms') . '</label>';
    echo '                </th>';
    echo '                <td>';
    echo '                <p><input type="radio" id="web_api" name="api_type" value="web_api" ' . checked($apiType, 'web_api', false) . '><label for="web_api">Web API</label></p>';
    echo '                <p><input type="radio" id="command_line" name="api_type" value="command_line" ' . checked($apiType, 'command_line', false) . '><label for="command_line">Voikko command line</label></p>';
    echo '                </td>';
    echo '            </tr>';
    echo '            <tr class="js-relevanssi-finnish-base-forms-api-url">';
    echo '                <th scope="row">';
    echo '                    <label for="api_url">' . __('Web API URL', 'relevanssi_finnish_base_forms') . '</label>';
    echo '                </th>';
    echo '                <td>';
    echo '                <input name="api_url" type="url" id="api_url" value="' . esc_url($apiUrl) . '" class="regular-text">';
    echo '                </td>';
    echo '            </tr>';
    echo '            <tr>';
    echo '                <th colspan="2">';
    echo '                <span style="font-weight: 400">Note: "Voikko command line" option requires voikkospell command application installed on the server.</span>';
    echo '                </td>';
    echo '            </tr>';
    echo '            <tr>';
    echo '                <th scope="row">';
    echo '                    <label>' . __('Add base forms to search query', 'relevanssi_finnish_base_forms') . '</label>';
    echo '                </th>';
    echo '                <td>';
    echo '                <input type="checkbox" name="lemmatize_search_query" id="lemmatize_search_query" value="checked" ' . checked(get_option('relevanssi_finnish_base_forms_lemmatize_search_query'), '1', false) . ' />';
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
    echo '        <input class="button-primary js-relevanssi-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
    echo '    </p>';
    wp_nonce_field('relevanssi_finnish_base_forms');
    echo '    </form>';
    echo '</div>';
}

// Simple white space tokenizer. Breaks either on whitespace or on word
// boundaries (ex.: dots, commas, etc) Does not include white space or
// punctuations in tokens.
//
// Based on NlpTools (http://php-nlp-tools.com/) under WTFPL license.
function relevanssi_finnish_base_forms_tokenize($str)
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

function relevanssi_finnish_base_forms_voikkospell($words)
{
    $process = new \Symfony\Component\Process\Process('voikkospell -M', null, [
      'LANG' => 'en_US.UTF-8'
    ]);
    $process->setInput(implode($words, "\n"));
    $process->run();
    preg_match_all('/BASEFORM=(.*)$/m', $process->getOutput(), $matches);
    return $matches[1];
}

function relevanssi_finnish_base_forms_web_api($tokenized, $apiRoot)
{
    $client = new \GuzzleHttp\Client();

    $extraWords = [];

    $requests = function () use ($client, $tokenized, $apiRoot) {
        foreach ($tokenized as $token) {
            yield function () use ($client, $token, $apiRoot) {
                return $client->getAsync(trailingslashit($apiRoot) . 'analyze/' . $token);
            };
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(), [
      'concurrency' => 10,
      'fulfilled' => function ($response) use (&$extraWords) {
          $response = json_decode($response->getBody()->getContents(), true);
          if (count($response)) {
              $baseforms = array_map(function ($item) {
                  return $item['BASEFORM'];
              }, $response);
              $extraWords = array_values(array_merge($extraWords, $baseforms));
          }
      },
    ]);

    $promise = $pool->promise();
    $promise->wait();

    return $extraWords;
}

function relevanssi_finnish_base_forms_lemmatize($content)
{
    $tokenized = relevanssi_finnish_base_forms_tokenize(strip_tags($content));

    $apiType = get_option('relevanssi_finnish_base_forms_api_type') ? get_option('relevanssi_finnish_base_forms_api_type') : 'web_api';

    if ($apiType === 'command_line') {
        $extraWords = relevanssi_finnish_base_forms_voikkospell($tokenized);
    } else {
        $apiRoot = get_option('relevanssi_finnish_base_forms_api_url');
        $extraWords = relevanssi_finnish_base_forms_web_api($tokenized, $apiRoot);
    }

    $content = trim($content . ' ' . implode(' ', $extraWords));

    return $content;
}

add_action('wp_ajax_relevanssi_finnish_base_forms_test', function () {
    $apiType = $_POST['api_type'];
    if ($apiType === 'command_line') {
        $baseforms = relevanssi_finnish_base_forms_voikkospell(['käden']);
    } else {
        $baseforms = relevanssi_finnish_base_forms_web_api(['käden'], $_POST['api_root']);
    }
    if ($baseforms === ['käsi']) {
        wp_die();
    } else {
        wp_die('', '', ['response' => 500]);
    }
});

add_action('admin_menu', function () {
    add_submenu_page(
      null,
      __('Relevanssirelevanssi Finnish Base Forms', 'relevanssi_finnish_base_forms'),
      __('Relevanssi Finnish Base Forms', 'relevanssi_finnish_base_forms'),
      'manage_options',
      'relevanssi_finnish_base_forms',
      'relevanssi_finnish_base_forms_settings_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'settings_page_relevanssi_finnish_base_forms') {
        return;
    }
    wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . '/js/script.js');
});
