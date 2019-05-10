<?php

class WordPress_Helpdesk_FAQ_Post_Type extends WordPress_Helpdesk
{
    protected $plugin_name;
    protected $version;

    /**
     * Construct FAQ Post Type Class
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param [type] $plugin_name [description]
     * @param [type] $version     [description]
     */
    public function __construct($plugin_name, $version, $stop_words)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->stop_words = $stop_words;
    }

    /**
     * Init FAQ Post type Class if enabled
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

        if (!$this->get_option('enableFAQ')) {
            return false;
        }

        $this->register_faq_post_type();
        $this->register_faq_taxonomy();
        $this->add_custom_meta_fields();

        add_action('post_submitbox_start', array( $this, 'show_copy_button' ));
        add_action('admin_action_copy_ticket_to_faq', array( $this, 'copy_ticket_to_faq' ));

        add_shortcode('knowledge_base', array( $this, 'get_knowledge_base' ));
        add_shortcode('faq', array( $this, 'get_faq' ));
        add_shortcode('faqs', array( $this, 'get_faqs' ));
        add_shortcode('faq_search', array( $this, 'get_shortcode_search' ));
        
    }

    /**
     * Get Knowledge Base Shortcode Output 
     * [knowledge_base columns="3" max_faqs="5" orderby="order" order="ASC"]
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param  [type] $atts [description]
     * @return [type]       [description]
     */
    public function get_knowledge_base($atts)
    {
        $args = shortcode_atts(array(
            'columns' => $this->get_option('FAQColumns'),
            'max_faqs' => 5,
            'orderby' => 'order',
            'order' => 'ASC',
        ), $atts);

        $columns = $args['columns'];
        if(empty($columns)) {
            $columns = 2;
        }

        $max_faqs = $args['max_faqs'];
        $orderby = $args['orderby'];
        $order = $args['order'];
        $topicsLoggedInOnly = is_array($this->get_option('FAQTopicsLoggedInOnly')) ? $this->get_option('FAQTopicsLoggedInOnly') : array();

        $topics = get_terms(array(
            'taxonomy'      => 'faq_topics',
            'hide_empty'    => false,
            'parent'        => 0,
            'orderby'       => $orderby,
            'order'         => $order,
        ));

        if (empty($topics)) {
            return __('<h2>No Topics created so far!</h2>', 'wordpress-helpdesk');
        }

        foreach ($topics as $key => $topic) {
            if(in_array($topic->term_id, $topicsLoggedInOnly) && !is_user_logged_in()) {
                unset($topics[$key]);
            }
        }

        $masonry = $this->get_option('FAQMasonry');
        if(!$masonry) {
            $topics = array_chunk($topics, $columns);
        }

        $columns = floor( 12 / intval($columns) );
        $max_faqs = intval($max_faqs);

        $sidebarClass = '';
        $contentClass = '';
        if($this->get_option('supportSidebarPosition') == "left") {
            $sidebarClass = 'wordpress-helpdesk-pull-left';
            $contentClass = 'wordpress-helpdesk-pull-right';
        } elseif($this->get_option('supportSidebarPosition') == "right") {
            $sidebarClass = 'wordpress-helpdesk-pull-right';
            $contentClass = 'wordpress-helpdesk-pull-left';
        }
        
        ob_start();
        ?>
        <div class="wordpress-helpdesk wordpress-helpdesk-faq">
            <div class="wordpress-helpdesk-row">
                <?php
                $checks = array('none', 'only_ticket');
                if(in_array($this->get_option('supportSidebarDisplay'), $checks)) {
                    echo '<div class="wordpress-helpdesk-col-sm-12">';
                } else {
                    echo '<div class="wordpress-helpdesk-col-sm-8 ' . $contentClass . '">';
                }

                if ($this->get_option('FAQShowSearch')) {
                    $this->get_search();
                }
                foreach ($topics as $topic) {

                    if(is_array($topic)) {
                        echo '<div class="wordpress-helpdesk-row">';
                        foreach ($topic as $_topic) {
                            $this->get_faq_column($_topic, $columns, $max_faqs);
                        }
                        echo '</div>';
                    } else {
                        $this->get_faq_column($topic, $columns, $max_faqs);
                    }
                }
                ?>
                </div>
            <?php
            $checks = array('both', 'only_faq');
            if(in_array($this->get_option('supportSidebarDisplay'), $checks)) {
            ?>
            <div class="wordpress-helpdesk-col-sm-4 wordpress-helpdesk-sidebar <?php echo $sidebarClass ?>">
                <?php dynamic_sidebar('helpdesk-sidebar'); ?>
            </div>
            <?php
            }
            ?>
        </div>
        <?php
        $output_string = ob_get_contents();
        ob_end_clean();
        return $output_string;
    }

    /**
     * Get FAQ Column
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param   [type]                       $topic    [description]
     * @param   [type]                       $columns  [description]
     * @param   [type]                       $max_faqs [description]
     * @return  [type]                                 [description]
     */
    private function get_faq_column($topic, $columns, $max_faqs)
    {
        $layout = $this->get_option('FAQLayout');
        $loggedInOnlyFAQs = $this->get_option('FAQLoggedInOnly');
        $loggedInHideInKnowledgeBase = $this->get_option('FAQLoggedInHideInKnowledgeBase');
        $topic_icon = get_term_meta($topic->term_id, 'wordpress_helpdesk_icon');

        if (isset($topic_icon) && !empty($topic_icon)) {
            $topic_icon = $topic_icon[0];
        } else {
            $topic_icon = 'fa fa-file-text-o';
        }
        ?>
        <div class="wordpress-helpdesk-faq-column wordpress-helpdesk-col-sm-<?php echo $columns ?>">
            <?php if($layout == "list") { ?>
                <a href="<?php echo get_term_link($topic->term_id) ?>">
                    <h3 class="wordpress-helpdesk-faq-title"><?php echo $topic->name ?></h3>
                </a>
                <hr class="wordpress-helpdesk-faq-divider">
                <ul class="wordpress-helpdesk-faq-list">
                <?php
                $args = array(
                    'post_type' => 'faq',
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'hierarchical' => false,
                    'posts_per_page' => $max_faqs,
                    'tax_query' => array(
                        array(
                        'taxonomy' => 'faq_topics',
                        'field' => 'id',
                        'terms' => $topic->term_id, // Where term_id of Term 1 is "1".
                        'include_children' => false
                        )
                    )
                );
                $faqs = get_posts($args);
                foreach ($faqs as $faq) {
                    if($loggedInHideInKnowledgeBase == "1" && !is_user_logged_in()){
                        continue;
                    }

                    if(is_array($loggedInOnlyFAQs) && in_array($faq->ID, $loggedInOnlyFAQs) && !is_user_logged_in()) {
                        continue;
                    }
                    echo '<li><a href="' . get_permalink($faq->ID) . '"><i class="' . $topic_icon . ' fa-1x" aria-hidden="true"></i>' . $faq->post_title . '</a></li>';
                }
                ?>
                </ul>
                <a href="<?php echo get_term_link($topic->term_id) ?>" class="wordpress-helpdesk-faq-list-count">
                    <?php echo sprintf(_n( 'View %s article', 'View %s articles', $topic->count, 'wordpress-helpdesk' ), $topic->count) ?>
                </a>
            <?php } else { ?>
                 <a href="<?php echo get_term_link($topic->term_id) ?>">
                    <div class="wordpress-helpdesk-faq-boxed">
                        <i class="<?php echo $topic_icon ?> fa-4x" aria-hidden="true"></i>
                        <h3 class="wordpress-helpdesk-faq-boxed-title"><?php echo $topic->name ?></h3>
                        <p class="wordpress-helpdesk-faq-boxed-description"><?php echo $topic->description ?></p>
                        <p class="wordpress-helpdesk-faq-boxed-count"><?php echo sprintf(_n( 'View %s article', 'View %s articles', $topic->count, 'wordpress-helpdesk' ), $topic->count) ?></p>
                    </div>
                </a>
            <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get FAQ search
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    private function get_search()
    {
        ?>
        <div class="wordpress-helpdesk-row">
                <div class="wordpress-helpdesk-col-sm-10 wordpress-helpdesk-col-sm-offset-1">
                    <form method="get" class="wordpress-helpdesk-faq-searchform" action="<?php echo site_url('/'); ?>" autocomplete="off">
                        <input style="display:none" type="text" name="fakeusernameremembered"/>
                        <input style="display:none" type="password" name="fakepasswordremembered"/>
                        <input type="search" class="wordpress-helpdesk-faq-searchterm form-control" name="s" autocomplete="off" placeholder="<?php echo __('Search FAQs', 'wordpress-helpdesk') ?>">
                        <input type="hidden" name="post_type" value="faq" />
                        <button type="submit" class="searchform-submit">
                            <span class="fa fa-search" aria-hidden="true"></span><span class="screen-reader-text"><?php echo __('Search FAQs', 'wordpress-helpdesk') ?></span>
                        </button>
                        <div class="wordpress-helpdesk-faq-live-search-results" style="display: none;"></div>
                    </form>
                </div>
            </div>
        <?php
    }

    public function get_shortcode_search()
    {
        ob_start();
        ?>
        <div class="wordpress-helpdesk">
            <?php $this->get_search(); ?>
        </div>
        <?php
        $output_string = ob_get_contents();
        ob_end_clean();
        return $output_string;
    }

    /**
     * Get single FAQ shortcode output 
     * [faq id="X" excerpt="true" content="false" link="true"]
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param  [type] $atts [description]
     * @return [type]       [description]
     */
    public function get_faq($atts)
    {
        $args = shortcode_atts(array(
            'id' => '',
            'excerpt' => 'true',
            'content' => 'false',
            'link' => 'true',
        ), $atts);

        $content = $args['content'];
        $excerpt = $args['excerpt'];
        $link = $args['link'];
        $id = $args['id'];

        if (empty($id)) {
            return __('No FAQ ID set.', 'wordpress-helpdesk');
        }

        $faq = get_post($id);

        if(!isset($faq->post_content)) {
            return __('No FAQ found.', 'wordpress-helpdesk');
        }

        $content = $content == 'true' ?  $content = $faq->post_content :  $content = '';
        $excerpt = $excerpt == 'true' ?  $excerpt = $this->get_excerpt($faq->post_content) :  $excerpt = '';
        $link = $link == 'true' ?  $link = get_permalink($faq->ID) :  $link = '';

        $loggedInOnlyFAQs = $this->get_option('FAQLoggedInOnly');
        $loggedInHideInKnowledgeBase = $this->get_option('FAQLoggedInHideInKnowledgeBase');
        if($loggedInHideInKnowledgeBase == "1" && !is_user_logged_in()){
            return;
        }

        if(is_array($loggedInOnlyFAQs) && in_array($faq->ID, $loggedInOnlyFAQs) && !is_user_logged_in()) {
            return;
        }

        ob_start();
        echo '
        <div class="wordpress-helpdesk-faq">
            <div class="wordpress-helpdesk-row">
                <div class="wordpress-helpdesk-col-sm-12">
                    <h3 class="wordpress-helpdesk-faq-title">' . $faq->post_title . '</h3>
                    <hr class="wordpress-helpdesk-faq-divider">';
                    if(!empty($excerpt)) {
                        echo '<div class="wordpress-helpdesk-faq-excerpt">' . $excerpt . '</div>';
                    }
                    if(!empty($content)) {
                        echo '<div class="wordpress-helpdesk-faq-content">' . $content . '</div>';
                    }
                    echo '<div class="wordpress-helpdesk-faq-link"><a href="' . $link . '">>' . __('View Article', 'wordpress-helpdesk') . '</a></div>
                </div>
            </div>
        </div>';
        $output_string = ob_get_contents();
        ob_end_clean();
        return $output_string;
    }

    /**
     * Get mutiple FAQs by Topic 
     * [faqs topic="ID" content="false" max_faqs="-1" excerpt="true" link="true" show_children="false" show_child_categories="true" columns="2" max_faqs="-1" order="ASC"
     * orderby="menu_order"]
     * If empty topic all FAQs will be rendered
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param  [type] $atts [description]
     * @return [type]       [description]
     */
    public function get_faqs($atts)
    {
        $args = shortcode_atts(array(
            'topic' => '',
            'content' => 'false',
            'excerpt' => 'true',
            'link' => 'true',
            'max_faqs' => '-1',
            'show_children' => 'false',
            'show_child_categories' => 'true',
            'columns' => $this->get_option('FAQColumns'),
            'order' => 'ASC',
            'orderby' => 'menu_order',
        ), $atts);

        $content = $args['content'];
        $excerpt = $args['excerpt'];
        $link = $args['link'];
        $topic = $args['topic'];
        $order = $args['order'];
        $orderby = $args['orderby'];
        $max_faqs = $args['max_faqs'];
        $show_children = $args['show_children'] === 'true' ? true: false;
        $show_child_categories = $args['show_child_categories'] === 'true' ? true: false;
        $columns = $args['columns'];
        $topicsLoggedInOnly = is_array($this->get_option('FAQTopicsLoggedInOnly')) ? $this->get_option('FAQTopicsLoggedInOnly') : array();

        $columns = floor( 12 / intval($columns) );
        $max_faqs = intval($max_faqs);

        $args = array(
            'post_type' => 'faq',
            'orderby' => $orderby,
            'order' => $order,
            'hierarchical' => false,
            'posts_per_page' => $max_faqs,
        );

        if (!empty($topic)) {
            if (!is_numeric($topic)) {
                return __('Topic ID not an ID', 'wordpress-helpdesk');
            }
            $args['tax_query'] = array(
                array(
                'taxonomy' => 'faq_topics',
                'field' => 'id',
                'terms' => $topic,
                'include_children' => $show_children
                )
            );

            $topic_icon = get_term_meta($topic, 'wordpress_helpdesk_icon');
            if (isset($topic_icon) && !empty($topic_icon)) {
                $topic_icon = $topic_icon[0];
            } else {
                $topic_icon = 'fa fa-file-text-o';
            }
        }

        ob_start();

        if(in_array($topic, $topicsLoggedInOnly) && !is_user_logged_in()) {
            echo sprintf(__('Please <a href="%s" title="Login">login to view this topic.</a>', 'wordpress-helpdesk'), wp_login_url(get_permalink()));
            return;
        }
        


        echo '<div class="wordpress-helpdesk wordpress-helpdesk-faq">';

        if ($this->get_option('FAQShowSearch')) {
            $this->get_search();
        }
        
        if($show_child_categories) {
            $children = get_term_children( $topic, 'faq_topics');
            if(!empty($children)) {
                echo '<div class="wordpress-helpdesk-row" style="margin-bottom: 20px;">';
                foreach ($children as $child) {
                    if(in_array($child, $topicsLoggedInOnly) && !is_user_logged_in()) {
                        continue;
                    }
                    $topic_child = get_term($child);
                    $this->get_faq_column($topic_child, $columns, $max_faqs);
                }
                echo '</div>';
            }
        }

        $faqs = get_posts($args);

        if(empty($faqs)) {
            return '<b>' . __('No Articles found.', 'wordpress-helpdesk') . '</b></div>';
        }


        $FAQItemLayout = $this->get_option('FAQItemLayout');
        $FAQItemColumns = $this->get_option('FAQItemColumns');

        $masonry = $this->get_option('FAQItemMasonry');
        if(!$masonry) {
            $faqs = array_chunk((array) $faqs, $FAQItemColumns);
        }

        $FAQItemColumns = floor( 12 / intval($FAQItemColumns) );

        foreach ($faqs as $faq) {

            if(is_array($faq)) {
                echo '<div class="wordpress-helpdesk-row">';

                foreach ($faq as $faq_row) {
                    $this->get_single_faq_column($faq_row, $FAQItemColumns, $topic_icon, $content, $excerpt, $link);
                }

                echo '</div>';
                continue;
            } else {
                $this->get_single_faq_column($faq, $FAQItemColumns, $topic_icon, $content, $excerpt, $link);
            }
        }
        echo '</div>';

        $output_string = ob_get_contents();
        ob_end_clean();
        return $output_string;
    }

    public function get_single_faq_column($faq, $column, $topic_icon, $content, $excerpt, $link)
    {
        $loggedInOnlyFAQs = $this->get_option('FAQLoggedInOnly');
        $loggedInHideInKnowledgeBase = $this->get_option('FAQLoggedInHideInKnowledgeBase');

        $the_content = $content == 'true' ?  $faq->post_content :  '';
        $the_excerpt = $excerpt == 'true' ?  $this->get_excerpt($faq->post_content) : '';
        $the_link = $link == 'true' ? get_permalink($faq->ID) : '';

        if($loggedInHideInKnowledgeBase == "1" && !is_user_logged_in()){
            return false;
        }

        if(is_array($loggedInOnlyFAQs) && in_array($faq->ID, $loggedInOnlyFAQs) && !is_user_logged_in()) {
            return false;
        }
        echo '
        <div class="wordpress-helpdesk-col-sm-' . $column . '">
            <div class="wordpress-helpdesk-faq">
                <a href="' . $the_link . '">
                    <h3 class="wordpress-helpdesk-faq-title"><i class="' . $topic_icon . '" aria-hidden="true"></i> ' . $faq->post_title . '</h3>
                </a>
                <hr class="wordpress-helpdesk-faq-divider">';
                if(!empty($the_excerpt)) {
                    echo '<div class="wordpress-helpdesk-faq-excerpt">' . $the_excerpt . '</div>';
                }
                if(!empty($the_content)) {
                    echo '<div class="wordpress-helpdesk-faq-content">' . $the_content . '</div>';
                }
                echo '<div class="wordpress-helpdesk-faq-link"><a href="' . $the_link . '">> ' . __('View Article', 'wordpress-helpdesk') . '</a></div>
            </div>
        </div>';
    }

    /**
     * Register FAQ Post Type
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function register_faq_post_type()
    {
        $redirect_base = "";
        $FAQKnowledgeBasePage = $this->get_option('FAQKnowledgeBasePage');
        if (!empty($FAQKnowledgeBasePage)) {
            $redirect_base = get_post_field('post_name', $FAQKnowledgeBasePage) . '/';
        }

        $singular = __('FAQ', 'wordpress-helpdesk');
        $plural = __('FAQs', 'wordpress-helpdesk');

        $labels = array(
            'name' => __('FAQs', 'wordpress-helpdesk'),
            'all_items' => sprintf(__('All %s', 'wordpress-helpdesk'), $plural),
            'singular_name' => $singular,
            'add_new' => sprintf(__('New %s', 'wordpress-helpdesk'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'wordpress-helpdesk'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'wordpress-helpdesk'), $singular),
            'new_item' => sprintf(__('New %s', 'wordpress-helpdesk'), $singular),
            'view_item' => sprintf(__('View %s', 'wordpress-helpdesk'), $singular),
            'search_items' => sprintf(__('Search %s', 'wordpress-helpdesk'), $plural),
            'not_found' => sprintf(__('No %s found', 'wordpress-helpdesk'), $plural),
            'not_found_in_trash' => sprintf(__('No %s found in trash', 'wordpress-helpdesk'), $plural),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'exclude_from_search' => false,
            'show_ui' => true,
            'menu_position' => 70,
            'rewrite' => array(
                'slug' => $redirect_base . 'faq',
                'with_front' => false
            ),
            'query_var' => 'faqs',
            'supports' => array('title', 'editor', 'author', 'revisions', 'thumbnail', 'comments', 'page-attributes'),
            'menu_icon' => 'dashicons-welcome-learn-more',
            'capability_type'     => array('faq','faqs'),
            'capabilities' => array(
                'publish_posts' => 'publish_faqs',
                'edit_posts' => 'edit_faqs',
                'edit_others_posts' => 'edit_others_faqs',
                'delete_posts' => 'delete_faqs',
                'delete_others_posts' => 'delete_others_faqs',
                'delete_published_posts' => 'delete_published_faqs',
                'read_private_posts' => 'read_private_faqs',
                'edit_post' => 'edit_faq',
                'delete_post' => 'delete_faq',
                'read_post' => 'read_faq',
                'edit_published_posts' => 'edit_published_faqs'
            ),
            'map_meta_cap' => true,
            'taxonomies' => array('product_cat'),
        );

        register_post_type('faq', $args);
    }

    /**
     * Register FAQ Categories and FAQ Filter Taxonomies.
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function register_faq_taxonomy()
    {
        $redirect_base = "";
        $FAQKnowledgeBasePage = $this->get_option('FAQKnowledgeBasePage');
        if (!empty($FAQKnowledgeBasePage)) {
            $redirect_base = get_post_field('post_name', $FAQKnowledgeBasePage) . '/';
        }

        // FAQ Category
        $singular = __('Topic', 'wordpress-helpdesk');
        $plural = __('Topics', 'wordpress-helpdesk');

        $labels = array(
            'name' => $plural,
            'singular_name' => $singular,
            'search_items' => sprintf(__('Search %s', 'wordpress-helpdesk'), $plural),
            'all_items' => sprintf(__('All %s', 'wordpress-helpdesk'), $plural),
            'parent_item' => sprintf(__('Parent %s', 'wordpress-helpdesk'), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', 'wordpress-helpdesk'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'wordpress-helpdesk'), $singular),
            'update_item' => sprintf(__('Update %s', 'wordpress-helpdesk'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'wordpress-helpdesk'), $singular),
            'new_item_name' => sprintf(__('New %s Name', 'wordpress-helpdesk'), $singular),
            'menu_name' => $plural,
        );

        $args = array(
                'labels' => $labels,
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'sort' => true,
                'update_count_callback' => '_update_post_term_count',
                'query_var' => true,
                'rewrite' => array('slug' => $redirect_base . 'topics', 'with_front' => false),
                'capabilities' => array(
                    'manage_terms' => 'manage_faq_topics',
                    'edit_terms' => 'edit_faq_topics',
                    'delete_terms' => 'delete_faq_topics',
                    'assign_terms' => 'assign_faq_topics',
                ),
        );

        register_taxonomy('faq_topics', 'faq', $args);
    }

    /**
     * Show Copy to FAQ Button on Tickets
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function show_copy_button()
    {
        global $post;

        if (!$this->get_option('enableFAQ')) {
            return false;
        }

        if (! is_object($post)) {
            return;
        }

        if ($post->post_type != 'ticket') {
            return;
        }

        if (isset($_GET['post'])) {
            $notifyUrl = wp_nonce_url(admin_url("edit.php?action=copy_ticket_to_faq&post=" . absint($_GET['post'])), 'wordpress_helpdesk_copy_' . $_GET['post']);
            ?>
           <a class="button button-primary button-large copy-to-faq" href="<?php echo esc_url($notifyUrl); ?>"><?php _e('Create FAQ from this Ticket', 'wordpress-helpdesk'); ?></a>
            <?php
        }
    }

    /**
     * Copy a ticket content to an FAQ
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function copy_ticket_to_faq()
    {
        if (empty($_REQUEST['post'])) {
            wp_die(__('No ticket to duplicate has been supplied!', 'wordpress-helpdesk'));
        }

        // Get the original page
        $id = isset($_REQUEST['post']) ? absint($_REQUEST['post']) : '';

        check_admin_referer('wordpress_helpdesk_copy_' . $id);

        $post = get_post($id);

        if (! empty($post)) {
            unset($post->ID);
            $post->post_type = 'faq';
            $post->post_author = wp_get_current_user()->ID;

            $new_post_id = wp_insert_post($post);

            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit();
        } else {
            wp_die(__('FAQ creation failed, could not find original ticket: ', 'wordpress-helpdesk') . ' ' . $id);
        }
    }


    /**
     * Add Custom Meta Field Icon to FAQ Topics
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     */
    public function add_custom_meta_fields()
    {
        $prefix = 'wordpress_helpdesk_';
        $custom_taxonomy_meta_config = array(
            'id' => 'faq_meta_box',
            'title' => 'FAQ Meta Box',
            'pages' => array('faq_topics'),
            'context' => 'side',
            'fields' => array(),
            'local_images' => false,
            'use_with_theme' => false,
        );

        $custom_taxonomy_meta_fields = new Tax_Meta_Class($custom_taxonomy_meta_config);
        $custom_taxonomy_meta_fields->addText($prefix.'icon', array('name'=> __('Font Awesome Icon.', 'tax-meta'), 'std' => 'fa fa-file-text-o fa-1x', 'desc' => 'Learn more here: http://fontawesome.io/icons/'));
        $custom_taxonomy_meta_fields->Finish();
    }

    /**
     * AJAX search FAQs
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function ajax_search_faqs()
    {
        $FAQSearchMaxResults = $this->get_option('FAQSearchMaxResults');

        $term = $_POST['term'];
        
        $term = filter_var($term, FILTER_SANITIZE_STRING);

        $words = array_count_values(str_word_count(strtolower($term), 1));
        $words = array_diff_key($words, array_flip($this->stop_words));
        $words = array_keys($words);

        $search_words = implode(' ', $words);
        $search_words_key = str_replace(' ', '-', $search_words);

        $args = array(
            'post_type' => 'faq',
            'post_status' => 'publish',
            's' => $search_words,
            'posts_per_page' => $FAQSearchMaxResults
        );
        $search = new WP_Query($args);

        $response = array(
            'count' => 0,
            'message' => '',
        );

        ob_start();
        $content = "";
        if ($search->have_posts()) {
            $response['count'] = count($search->posts);

            echo '<header class="wordpress-helpdesk-faq-live-search-header">';
                echo '<div class="wordpress-helpdesk-faq-live-search-header-title">' . sprintf(__('Search Results for: %s', 'wordpress-helpdesk'), $term) . '</div>';
            echo '</header>';

            while ($search->have_posts()) :
                $search->the_post();

                echo '<a href="' . get_the_permalink() . '" class="wordpress-helpdesk-faq-live-search-result">';
                    echo '<div class="wordpress-helpdesk-faq-live-search-result-title">' . get_the_title() . '</div>';
                    echo '<div class="wordpress-helpdesk-faq-live-search-result-content">' . $this->get_excerpt( get_the_content() ) . '</div>';
                echo '</a>';
            endwhile;

            if($response['count'] == $FAQSearchMaxResults) {
                echo '<footer class="wordpress-helpdesk-faq-live-search-footer">';
                    echo '<div class="wordpress-helpdesk-faq-live-search-footer-found-more">' . sprintf( __('We found more than %d results ...', 'wordpress-helpdesk'), $FAQSearchMaxResults) . '</div>';
                    echo '<a href="' . get_home_url() . '?post_type=faq&s=' . $term . '" class="wordpress-helpdesk-faq-live-search-footer-see-all">' . __('Click here to see all') . '</a>';
                echo '</footer class="wordpress-helpdesk-faq-live-search-footer">';
            }

        } else {
            echo '<header class="wordpress-helpdesk-faq-live-search-header">';
                echo '<div class="wordpress-helpdesk-faq-live-search-header-title">' . sprintf(__('Could not find anything for: %s', 'wordpress-helpdesk'), $term) . '</div>';
            echo '</header>';
        }
        
        $content = ob_get_clean();
        
        $search_words_options = get_option('helpdesk_faq_search_words');
        if(empty($search_words_options)) {
            $search_words_options[$search_words_key] = array(
                'term' => $search_words,
                'count' => 1,
                'found' => $response['count']
            );
            update_option('helpdesk_faq_search_words', $search_words_options);
        } else {
            if(isset($search_words_options[$search_words_key])) {
                $search_words_options[$search_words_key]['count'] = $search_words_options[$search_words_key]['count'] + 1;
                $search_words_options[$search_words_key]['found'] = $response['count'];
            } else {
                $search_words_options[$search_words_key] = array(
                    'term' => $search_words,
                    'count' => 1,
                    'found' => $response['count']
                );
            }
            update_option('helpdesk_faq_search_words', $search_words_options);
        }

        $response['message'] = $content;
        die(json_encode($response));
    }

    /**
     * Count FAQ views and save views into faq_popularity meta key
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function count_views()
    {
        global $post;

        if(empty($post)) {
            return false;
        }

        if ($post->post_type !== "faq") {
            return false;
        }

        if (!is_single()) {
            return false;
        }

        $count_key = 'faq_popularity';
        $count = get_post_meta($post->ID, $count_key, true);

        if (!empty($count) || ($count === "0")) {
            $count++;
        } else {
            $count = 0;
        }
        update_post_meta($post->ID, $count_key, $count);
    }

    /**
     * Load custom FAQ Topics Template
     * Override this via a file in your theme called archive-faq_topic.php
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param   [type]                       $template [description]
     * @return  [type]                                 [description]
     */
    public function faq_templates( $template ) 
    {
        global $post;

        if($this->get_option('useThemesTemplate')) {
            return $template;
        }

        $queried_object = get_queried_object();
        if(is_archive()) {
            if(isset($queried_object->taxonomy) && $queried_object->taxonomy == "faq_topics") {
                $theme_files = array('archive-faq_topic.php', 'wordpress-helpdesk/archive-faq_topic.php');
                $exists_in_theme = locate_template($theme_files, false);
                if ( $exists_in_theme != '' ) {
                    return $exists_in_theme;
                } else {
                    return plugin_dir_path(__FILE__) . 'views/archive-faq_topic.php';
                }
            }
        }
        if(is_single()) {
            if($post->post_type == "faq") {
                $theme_files = array('single-faq.php', 'wordpress-helpdesk/single-faq.php');
                $exists_in_theme = locate_template($theme_files, false);
                if ( $exists_in_theme != '' ) {
                    return $exists_in_theme;
                } else {
                    return plugin_dir_path(__FILE__) . 'views/single-faq.php';
                }
            }
        }
        return $template;
    }

    /**
     * Get excerpt from string
     * 
     * @param String $str String to get an excerpt from
     * @param Integer $startPos Position int string to start excerpt from
     * @param Integer $maxLength Maximum length the excerpt may be
     * @return String excerpt
     */
    private function get_excerpt($str, $startPos=0, $maxLength=250) {

        $excerpt = strip_tags( do_shortcode($str) );
        
        if(strlen($excerpt) > $maxLength) {
            $excerpt   = substr($excerpt, $startPos, $maxLength-3);
            $lastSpace = strrpos($excerpt, ' ');
            $excerpt   = substr($excerpt, 0, $lastSpace);
            $excerpt  .= '...';
        } else {
            $excerpt = $str;
        }

        $excerpt = strip_shortcodes( preg_replace("/\[[^\]]+\]/", '', $excerpt) );

        return strip_tags( $excerpt );
    }

    /**
     * Count FAQ likes and save likes into faq_likes meta key
     * @author Daniel Barenkamp
     * @version 1.1.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function count_likes()
    {
        $post_id = $_POST['post_id'];
        $post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);

        if(empty($post_id)) {
            return false;
        }

        $post = get_post($post_id);

        if ($post->post_type !== "faq") {
            return false;
        }

        $ips_key = 'faq_ips';
        $count_key = 'faq_likes';
        $count = get_post_meta($post->ID, $count_key, true);

        if (!empty($count) || ($count == 1)) {
            $count++;
        } else {
            $count = 1;
        }

        $users_ip = (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
        if(!empty($users_ip)) {
            $ips = get_post_meta($post->ID, 'faq_ips', true);
            if(empty($ips)) {
                $ips = array(
                    $users_ip
                );
            } else {
                if(!in_array($users_ip, $ips)) {
                    $ips[] = $users_ip;
                } else {
                    $count--;
                }
            }

            update_post_meta($post->ID, $ips_key, $ips);
        }

        update_post_meta($post->ID, $count_key, $count);

        die(json_encode($count));
    }

    /**
     * Count FAQ dislikes and save dislikes into faq_dislikes meta key
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.1.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function count_dislikes()
    {
        $post_id = $_POST['post_id'];
        $post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);

        if(empty($post_id)) {
            return false;
        }

        $post = get_post($post_id);

        if ($post->post_type !== "faq") {
            return false;
        }

        $ips_key = 'faq_ips';
        $count_key = 'faq_dislikes';
        $count = get_post_meta($post->ID, $count_key, true);

        if (!empty($count) || ($count == 1)) {
            $count++;
        } else {
            $count = 1;
        }
        

        $users_ip = (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
        if(!empty($users_ip)) {
            $ips = get_post_meta($post->ID, 'faq_ips', true);
            if(empty($ips)) {
                $ips = array(
                    $users_ip
                );
            } else {
                if(!in_array($users_ip, $ips)) {
                    $ips[] = $users_ip;
                } else {
                    $count--;
                }
            }

            update_post_meta($post->ID, $ips_key, $ips);
        }

        update_post_meta($post->ID, $count_key, $count);

        die(json_encode($count));
    }

    /**
     * Only show FAQ content to logged in users
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.1.0
     * @link    https://plugins.db-dzine.com
     * @return  [type]                       [description]
     */
    public function only_logged_in($content)
    {
        global $post;

        if($this->get_option('FAQSingleLoggedIn')) {
            if ( $post->post_type == 'faq' && !is_user_logged_in()) {
                $content = sprintf(__('Please <a href="%s" title="Login">login to view this faq.</a>', 'wordpress-helpdesk'), wp_login_url(get_permalink()));    
            }
        }

        $loggedInOnlyFAQs = $this->get_option('FAQLoggedInOnly');
        if(is_array($loggedInOnlyFAQs) && in_array($post->ID, $loggedInOnlyFAQs) && !is_user_logged_in()) {
            $content = sprintf(__('Please <a href="%s" title="Login">login to view this faq.</a>', 'wordpress-helpdesk'), wp_login_url(get_permalink()));    
        }

        return $content;
    }  


    public function add_faq_term_page()
    {
        add_submenu_page(
            'edit.php?post_type=ticket',
            __('FAQ Terms', 'wordpress-helpdesk'),
            __('FAQ Terms', 'wordpress-helpdesk'),
            'manage_options',
            'helpdesk-faq-terms',
            array($this, 'get_faq_terms_table')
        );
    }

    public function get_faq_terms_table()
    {

        $search_words_options = get_option('helpdesk_faq_search_words');
        if(empty($search_words_options) || !is_array($search_words_options)) {
            echo __('No Search Terms found yet', 'wordpress-helpdesk');
        }

        usort($search_words_options, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        echo '<h2>' . __('FAQ Terms', 'wordpress-helpdesk') . '</h2>';

        echo 
        '<table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <td>' . __('Term', 'wordpress-helpdesk') . '</td>
                    <td>' . __('Search Counts', 'wordpress-helpdesk') . '</td>
                    <td>' . __('Articles Found', 'wordpress-helpdesk') . '</td>
                </tr>
            </thead>
            <tbody>';
        foreach ($search_words_options as $search_word) {
            echo '<tr>' .
                '<td>' . $search_word['term'] . '</td>' .
                '<td>' . $search_word['count'] . '</td>' .
                '<td>' . $search_word['found'] . '</td>' .
            '</tr>';
        }   
        echo 
            '</tbody>
        </table>';
    }
}