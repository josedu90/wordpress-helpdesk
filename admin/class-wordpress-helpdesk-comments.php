<?php

class WordPress_Helpdesk_Comments extends WordPress_Helpdesk
{
    protected $plugin_name;
    protected $version;
    
    public $errors = array();
    public $success = array();
    public $comment_id = '';

    /**
     * Construct Comments Processor
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @param   string                         $plugin_name
     * @param   string                         $version
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Init Comments
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return [type] [description]
     */
    public function init()
    {
        global $wordpress_helpdesk_options;
        $this->options = $wordpress_helpdesk_options;
    }

    /**
     * Comment Editor Changes
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param   [type]                       $settings  [description]
     * @param   [type]                       $editor_id [description]
     * @return  [type]                                  [description]
     */
    public function comment_editor($settings, $editor_id)
    {
        if($editor_id !== "replycontent") {
            return $settings;  
        }

        $screen = get_current_screen();
        if($screen->post_type !== "ticket") {
            return $settings;
        }
        // $settings['tinymce'] = true;
        $settings['media_buttons'] = true;
        return $settings;
    }

    /**
     * Enable comment editor on frontend
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    public function enable_comment_editor($field)
    {
        if (!is_single() || !is_singular('ticket')) {
            return $field;
        }

        ob_start();
        $settings = array(
            'textarea_rows' => 15,
            'media_buttons' => false,
            'teeny' => true,
            'drag_drop_upload' => true,
        );
        wp_editor('', 'comment', $settings);
        $editor = ob_get_contents();
        ob_end_clean();

        //make sure comment media is attached to parent post
        $editor = str_replace('post_id=0', 'post_id=' . get_the_ID(), $editor);

        return $editor;
    }

    /**
     * Allow Comments for all tickets
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param  [type] $option [description]
     * @return [type]         [description]
     */
    public function allow_comments_for_all_ticket($option)
    {
        global $post;

        if(empty($post)) {
            return $option;
        }

        if ($post->post_type == "ticket") {
            return 0;
        }

        return $option;
    }
}
