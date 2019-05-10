<?php

class WordPress_Helpdesk_Attachments extends WordPress_Helpdesk
{
    protected $plugin_name;
    protected $version;

    /**
     * Construct Attachments Class
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

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
    }

    /**
     * Init Attachments
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
     * Add attachment fields to comment for
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function add_attachment_fields($fields)
    {
        global $post;

        if($post->post_type !== "ticket") {
            return $fields;
        }

        $ticketSource = get_post_meta( $post->ID, 'source', true);
        if (in_array($ticketSource, array('Simple', 'WooCommerce', 'Envato')) && !$this->get_option('fields' . $ticketSource . 'Attachments')) {
            return false;
        }

        echo    '<p class="wordpress-helpdesk-attachments">' .
                    '<label for="author">' . __('Attachments', 'wordpress-helpdesk') . '</label>' .
                    '<input name="helpdesk-attachments[]" type="file" accept="image/*" multiple>' .
                '</p>';

        return $fields;
    }

    /**
     * Save comment attachments
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function save_comment_attachments($id, $comment)
    {
        if (!isset($comment->comment_post_ID) || empty($comment->comment_post_ID)) {
            return false;
        }

        $postID = $comment->comment_post_ID;
        $post = get_post($postID);

        if ($post->post_type !== "ticket") {
            return false;
        }

        $attachment_ids = array();
        if (isset($_FILES['helpdesk-attachments']) && !empty($_FILES['helpdesk-attachments'])) {
            
            $files =  $this->diverse_array($_FILES['helpdesk-attachments']);
            $upload_overrides = array( 'test_form' => false );

            foreach ($files as $file) {
                $movefile = wp_handle_upload($file, $upload_overrides);
                if ($movefile && ! isset($movefile['error'])) {
                    $attachment_ids[] = $this->insert_attachment($movefile['file'], $postID);
                }
            }
        }
        
        if (!empty($attachment_ids)) {
            update_comment_meta($comment->comment_ID, 'wordpress_helpdesk_attachments', $attachment_ids);
        }
        return $attachment_ids;
    }

    /**
     * Save post / ticket attachments
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  boolean
     */
    public function save_ticket_attachments($post_id, $post, $update)
    {
        if ($post->post_type !== "ticket") {
            return false;
        }

        $attachment_ids = array();
        if (isset($_FILES['helpdesk-attachments']) && !empty($_FILES['helpdesk-attachments'])) {
            $files =  $this->diverse_array($_FILES['helpdesk-attachments']);
            $upload_overrides = array( 'test_form' => false );

            foreach ($files as $file) {
                $movefile = wp_handle_upload($file, $upload_overrides);

                if ($movefile && ! isset($movefile['error'])) {
                    $attachment_ids[] = $this->insert_attachment($movefile['file'], $post_id);
                }
            }
        }

        if (!empty($attachment_ids)) {
            update_post_meta($post_id, 'wordpress_helpdesk_attachments', $attachment_ids);
        }
        return true;
    }

    /**
     * Diverse array of $_FILES
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  array
     */
    private function diverse_array($files)
    {
        $result = array();
        foreach ($files as $key1 => $value1) {
            foreach ($value1 as $key2 => $value2) {
                $result[$key2][$key1] = $value2;
            }
        }
        return $result;
    }

    /**
     * Insert attachments
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  attachment_id
     */
    private function insert_attachment($filename, $post_id)
    {
        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($filename), null);

        // Get the path to the upload directory.
        $wp_upload_dir = wp_upload_dir();

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $wp_upload_dir['url'] . '/' . basename($filename),
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment($attachment, $filename, $post_id);

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $attach_data);

        set_post_thumbnail($post_id, $attach_id);

        return $attach_id;
    }

    /**
     * Show Comment Attachments
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @return  array
     */
    public function show_comment_attachments($comment_text, $comment)
    {
        if(get_post_type($comment->post_id) === "ticket") {
            $attachment_ids = get_comment_meta($comment->comment_ID, 'wordpress_helpdesk_attachments');
            
            if (isset($attachment_ids[0]) && !empty($attachment_ids[0])) {
                $html = '<div class="wordpress-helpdesk-comment-attachments">';

                $attachment_ids = $attachment_ids[0];
                foreach ($attachment_ids as $attachment_id) {
                    $full_url = wp_get_attachment_url($attachment_id);
                    $thumb_url = wp_get_attachment_thumb_url($attachment_id);

                    $html .= '<a href="' . $full_url . '" target="_blank">';
                        $html .= '<img src="' . $thumb_url . '" alt="">';
                    $html .= '</a>';
                }
                
                $html .= '</div>';
                $comment_text = $comment_text . $html;
            }
        }

        return $comment_text;
    }
}