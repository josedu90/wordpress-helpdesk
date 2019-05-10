<?php
global $wordpress_helpdesk_options;

$queried_object = get_queried_object();
get_header();
?>
<div class="container">
	<div class="container_inner default_template_holder clearfix page_container_inner">
		<div class="wordpress-helpdesk-row">
			<?php

			$sidebarClass = '';
			$contentClass = '';
			if($wordpress_helpdesk_options['supportSidebarPosition'] == "left") {
				$sidebarClass = 'wordpress-helpdesk-pull-left';
				$contentClass = 'wordpress-helpdesk-pull-right';
			} elseif($wordpress_helpdesk_options['supportSidebarPosition'] == "right") {
				$sidebarClass = 'wordpress-helpdesk-pull-right';
				$contentClass = 'wordpress-helpdesk-pull-left';
			}

	        $checks = array('none', 'only_ticket');
	        if(in_array($wordpress_helpdesk_options['supportSidebarDisplay'], $checks)) {
	            echo '<div class="wordpress-helpdesk-col-sm-12">';
	        } else {
	            echo '<div class="wordpress-helpdesk-col-sm-8 ' . $contentClass . '">';
	        }
	        ?>
				<?php echo do_shortcode('[faqs topic="' . $queried_object->term_id . '" show_children="false" show_child_categories="true" max_faqs="-1"]'); ?>
			</div>
			<?php
			$checks = array('both', 'only_faq');
			if(in_array($wordpress_helpdesk_options['supportSidebarDisplay'], $checks)) {
			?>
			<div class="wordpress-helpdesk-col-sm-4 wordpress-helpdesk-pull-right wordpress-helpdesk-sidebar <?php echo $sidebarClass ?>">
				<?php dynamic_sidebar('helpdesk-sidebar'); ?>
			</div>
			<?php
			}
			?>
		</div>
	</div>
</div>

<?php
get_footer();