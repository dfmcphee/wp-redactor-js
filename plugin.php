<?php

/*
Plugin Name: RedactorJS.com
Plugin URI: http://dominicmcphee.com/redactor-wp-editor
Description: Replaces the default WordPress editor (TinyMCE) with the much more elgant Redactor (redactorjs.com). Redactor is free to use on non-profit sites, but you do have to purchase a license if you use it for commercial projects.
Version: 0.1.2
Author: Dominic McPhee
Author URI: http://dominicmcphee.comww
*/

require_once('redactor.inc.php');

define('DEBUG', false);

class WpPluginAutoUpdate {
    # URL to check for updates, this is where the index.php script goes
    public $api_url;

    # Type of package to be updated
    public $package_type;

    public $plugin_slug;
    public $plugin_file;

    public function WpPluginAutoUpdate($api_url, $type, $slug) {
        $this->api_url = $api_url;
        $this->package_type = $type;
        $this->plugin_slug = $slug;
        $this->plugin_file = $slug .'/'. $slug . '.php';
    }

    public function print_api_result() {
        print_r($res);
        return $res;
    }

    public function check_for_plugin_update($checked_data) {
        if (empty($checked_data->checked))
            return $checked_data;
        
        $request_args = array(
            'slug' => $this->plugin_slug,
            'version' => $checked_data->checked[$this->plugin_file],
            'package_type' => $this->package_type,
        );

        $request_string = $this->prepare_request('basic_check', $request_args);
        
        // Start checking for an update
        $raw_response = wp_remote_post($this->api_url, $request_string);

        if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200)) {
            $response = unserialize($raw_response['body']);

            if (is_object($response) && !empty($response)) // Feed the update data into WP updater
                $checked_data->response[$this->plugin_file] = $response;
        }
        
        return $checked_data;
    }

    public function plugins_api_call($def, $action, $args) {
        if ($args->slug != $this->plugin_slug)
            return false;
        
        // Get the current version
        $plugin_info = get_site_transient('update_plugins');
        $current_version = $plugin_info->checked[$this->plugin_file];
        $args->version = $current_version;
        $args->package_type = $this->package_type;
        
        $request_string = $this->prepare_request($action, $args);
        
        $request = wp_remote_post($this->api_url, $request_string);
        
        if (is_wp_error($request)) {
            $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
        } else {
            $res = unserialize($request['body']);
            
            if ($res === false)
                $res = new WP_Error('plugins_api_failed', __('An unknown error occurred'), $request['body']);
        }
        
        return $res;
    }

    public function prepare_request($action, $args) {
        $site_url = site_url();

        $wp_info = array(
            'site-url' => $site_url,
            'version' => $wp_version,
        );

        return array(
            'body' => array(
                'action' => $action, 'request' => serialize($args),
                'api-key' => md5($site_url),
                'wp-info' => serialize($wp_info),
            ),
            'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
        );
    }
}

$wp_plugin_auto_update = new WpPluginAutoUpdate('http://www.dominicmcphee.com/plugins/redactor-js/', 'stable', basename(dirname(__FILE__)));

if (DEBUG) {
    // Enable update check on every request. Normally you don't need 
    // this! This is for testing only!
    set_site_transient('update_plugins', null);

    // Show which variables are being requested when query plugin API
    add_filter('plugins_api_result', array($wp_plugin_auto_update, 'print_api_result'), 10, 3);
}

// Take over the update check
add_filter('pre_set_site_transient_update_plugins', array($wp_plugin_auto_update, 'check_for_plugin_update'));

// Take over the Plugin info screen
add_filter('plugins_api', array($wp_plugin_auto_update, 'plugins_api_call'), 10, 3);

/**
 * Enqueue stylesheets
 */

function admin_include_css() {
    wp_register_style( 'admin_redactor_css', plugins_url('redactor/css/redactor.css', __FILE__ ));
    wp_enqueue_style( 'admin_redactor_css' );
}

add_action( 'admin_enqueue_scripts', 'admin_include_css' );

/**
 * Enqueue inline CSS
 */
function admin_inline_css() {
    ?>
    <style>
        #content {
            z-index:1;
            height:300px;
            width:100%;
            line-height:1.8em;
        }

        #TB_overlay, #TB_window {
            z-index: 999999999;
        }

        .redactor_box_fullscreen .redactor_toolbar {
            margin: 30px 0px 20px 0px !important
        }

        .redactor_toolbar {
            height:36px;
        }
    </style>
    <?php
}

add_action( 'admin_head', 'admin_inline_css' );

/**
 * Enqueue JavaScript in footer
 */
function admin_js() {
    wp_enqueue_script('redactor', plugins_url('redactor/redactor.js', __FILE__));
    wp_enqueue_script('redactor_main', plugins_url('/js/main.js', __FILE__));
}
add_action('admin_footer', 'admin_js');

/**
 * Action to save Redactor content to post meta through ajax
 */
function save_post_content() {
    $data = $_POST['data'];

    $post_id = $data['post_id'];
    $post_content = $data['content'];

    update_post_meta($post_id, 'redactor_post_content', $post_content);
    
    echo 'success';

    die();
}

add_action('wp_ajax_save_post_content', 'save_post_content');

/**
 * Action to bind media button to WordPress media picker
 */
add_action("admin_print_scripts", "media_embed_scripts");

function media_embed_scripts() {    
?>

    <script type="text/javascript">
    // Deals with calling the WordPress Media popup box
    function openMediaEmbed() {
        window.send_to_editor = function(html) {
            imgurl = jQuery('img',html).attr('src');
            jQuery('#redactor_file_link').val(imgurl);
            tb_remove();
        }

        formfield = jQuery('#upload_image').attr('name');
        tb_show('', '<?php echo admin_url(); ?>media-upload.php?type=image&tab=library&TB_iframe=true');
        return false;
    }
    </script>

<?php 
}