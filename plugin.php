<?php

/*
Plugin Name: RedactorJS.com
Plugin URI: http://dominicmcphee.com/redactor-wp-editor
Description: Replaces the default WordPress editor (TinyMCE) with the much more elgant Redactor (redactorjs.com). Redactor is free to use on non-profit sites, but you do have to purchase a license if you use it for commercial projects.
Version: 0.2.0
Author: Dominic McPhee
Author URI: http://dominicmcphee.comww
*/

require_once('updater.php');

function github_plugin_updater_test_init() {
    include_once('redactor.inc.php');
    if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
        $config = array(
            'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
            'proper_folder_name' => 'redactor-js', // this is the name of the folder your plugin lives in
            'api_url' => 'https://api.github.com/repos/dfmcphee/wp-redactor-js', // the github API url of your github repo
            'raw_url' => 'https://raw.github.com/dfmcphee/wp-redactor-js/master', // the github raw url of your github repo
            'github_url' => 'https://github.com/dfmcphee/wp-redactor-js', // the github url of your github repo
            'zip_url' => 'https://github.com/dfmcphee/wp-redactor-js/zipball/master', // the zip url of the github repo
            'sslverify' => false ,// wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
            'requires' => '3.0', // which version of WordPress does your plugin require?
            'tested' => '3.4.1', // which version of WordPress is your plugin tested up to?
            'readme' => 'README.md' // which file to use as the readme for the version number
        );
        new WPGitHubUpdater($config);
    }
    add_action('init', 'github_plugin_updater_test_init');
}

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