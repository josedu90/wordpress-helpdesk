<?php

class FAQ_Topics extends WP_Widget {

	public function __construct() {
		$settings = array('description' => __('FAQ Topics', 'wordpress-helpdesk'));
		parent::__construct( false, 'FAQ Topics', $settings );
	}

	public function widget( $args, $instance ) {
		$title = apply_filters('widget_title', $instance['title'] );
		$orderby = $instance['orderby'];
		$order = $instance['order'];
		$max_topics = $instance['max_topics'];

		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];

        $topics = get_terms(array(
            'taxonomy'      => 'faq_topics',
            'hide_empty'    => false,
            'parent'        => 0,
            'orderby'       => $orderby,
            'order'         => $order,
            'number'		=> $max_topics,
        ));

		if(!empty($topics)) {
			echo '<ul class="wordpress-helpdesk-topic-list wordpress-helpdesk-faq-list">';
			foreach ($topics as $topic) {

				$topic_icon = get_term_meta($topic->term_id, 'wordpress_helpdesk_icon');
		        if (isset($topic_icon) && !empty($topic_icon)) {
		            $topic_icon = $topic_icon[0];
		        } else {
		            $topic_icon = 'fa fa-file-text-o';
		        }

				echo '<li><a href="' . get_term_link($topic->term_id) . '"><i class="' . $topic_icon . ' fa-1x" aria-hidden="true"></i>' . $topic->name . '</a></li>';
			}
        	echo '</ul>';
		}

		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) 
	{
		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['orderby'] = ( ! empty( $new_instance['orderby'] ) ) ? $new_instance['orderby'] : '';
		$instance['order'] = ( ! empty( $new_instance['order'] ) ) ? $new_instance['order'] : '';
		$instance['max_topics'] = ( ! empty( $new_instance['max_topics'] ) ) ? $new_instance['max_topics'] : '-1';

		return $instance;
	}

	public function form( $instance ) 
	{

		$title = isset($instance['title']) ? $instance['title'] : __('Topics', 'wordpress-helpdesk');
		echo '<p><label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wordpress-helpdesk') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr( $title ) . '" />';

		$max_topics = isset($instance['max_topics']) ? $instance['max_topics'] : 99;
		echo '<p><label for="' . $this->get_field_id('max_topics') . '">' . __('Number of max. Topics:') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('max_topics') . '" name="' . $this->get_field_name('max_topics') . '" type="number" value="' . $max_topics . '" />';

		$orderbys = array(
			'order' => __('Menu Order', 'wordpress-helpdesk'),
			'name' => __('Name', 'wordpress-helpdesk'),
			'slug' => __('Slug', 'wordpress-helpdesk'),
			'term_group' => __('Term_group', 'wordpress-helpdesk'),
			'term_id' => __('Term_id', 'wordpress-helpdesk'),
			'id' => __('Id', 'wordpress-helpdesk'),
			'description' => __('Description', 'wordpress-helpdesk'),
			'parent' => __('Parent', 'wordpress-helpdesk'),
			'count' => __('Count', 'wordpress-helpdesk'),
		);

		echo '<p><label for="' . $this->get_field_id('orderby') . '">' . __('Order By:', 'wordpress-helpdesk') . '</label>';
		echo '<select name="' . $this->get_field_name('orderby') . '" class="widefat">';
		echo '<option value="">Select a Order Key</option>';
		$selectedOrderby = isset($instance['orderby']) ? $instance['orderby'] : 'order';

		foreach ($orderbys as $key => $orderby) {
			$selected = "";
			if($selectedOrderby == $key) {
				$selected = 'selected="selected"';
			}

			echo '<option value="' . $key . '" ' . $selected . '>' . $orderby . '</option>';
		}

		echo '</select></p>';

		$orders = array(
			'ASC' => 'ASC',
			'DESC' => 'DESC',
		);

		echo '<p><label for="' . $this->get_field_id('order') . '">' . __('Order:', 'wordpress-helpdesk') . '</label>';
		echo '<select name="' . $this->get_field_name('order') . '" class="widefat">';
		echo '<option value="">Select a Order</option>';
		$selectedOrder = isset($instance['order']) ? $instance['order'] : 'ASC';

		foreach ($orders as $key => $order) {
			$selected = "";
			if($selectedOrder == $key) {
				$selected = 'selected="selected"';
			}

			echo '<option value="' . $key . '" ' . $selected . '>' . $order . '</option>';
		}

		echo '</select></p>';
	}
}
