<?php

// Removes WordPress Editor
add_action('init', 'remove_post_editor_init');

function remove_post_editor_init() {
	remove_post_type_support('post', 'editor');
}

// Removes WordPress Editor
add_action('init', 'remove_page_editor_init');

function remove_page_editor_init() {
    remove_post_type_support('page', 'editor');
}

/**
 * Calls the class on the post edit screen
 */
function call_redactor() {
    return new redactor();
}


add_action( 'init', 'call_redactor' );

/** 
 * Main Redactor Class
 */
class redactor {
    const LANG = 'some_textdomain';

    public function __construct() {
        add_action( 'add_meta_boxes', array( &$this, 'add_redactor_meta_box' ) );
    }

    /**
     * Adds the meta box container
     */
    public function add_redactor_meta_box() {
        add_meta_box( 
             'redactor_editor'
            ,__( 'Redactor WP-Editor', self::LANG )
            ,array( &$this, 'render_meta_box_content' )
            ,'post'
            ,'advanced'
            ,'high'
        );

        add_meta_box( 
             'redactor_editor'
            ,__( 'Redactor WP-Editor', self::LANG )
            ,array( &$this, 'render_meta_box_content' )
            ,'page' 
            ,'advanced'
            ,'high'
        );
    }
    
    /**
     * Render Meta Box content
     */
    public function render_meta_box_content() { 
    	global $post;
    	?>
    	<div>
    		<input type="hidden" id="post-id" value="<?= $post->ID ?>" />
    		<textarea id="content" name="content"><?= $post->post_content ?></textarea>
    	</div>
    	<?php
    }
}