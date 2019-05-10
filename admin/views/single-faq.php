<?php
global $post, $wordpress_helpdesk_options;

$sidebarClass = '';
$contentClass = '';
if($wordpress_helpdesk_options['supportSidebarPosition'] == "left") {
	$sidebarClass = 'wordpress-helpdesk-pull-left';
	$contentClass = 'wordpress-helpdesk-pull-right';
} elseif($wordpress_helpdesk_options['supportSidebarPosition'] == "right") {
	$sidebarClass = 'wordpress-helpdesk-pull-right';
	$contentClass = 'wordpress-helpdesk-pull-left';
}

get_header();
?>
<div class="clearfix"></div>
<div class="wordpress-helpdesk">
	<div id="main-content" class="main-content">
		<div class="container">
			<div class="container_inner default_template_holder clearfix page_container_inner">
				<div class="wordpress-helpdesk-row">
					<?php
			        $checks = array('none', 'only_ticket');
			        if(in_array($wordpress_helpdesk_options['supportSidebarDisplay'], $checks)) {
			            echo '<div class="wordpress-helpdesk-col-sm-12">';
			        } else {
			            echo '<div class="wordpress-helpdesk-col-sm-8 ' . $contentClass . '">';
			        }
			        ?>
						<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

						    <div <?php post_class() ?> id="post-<?php the_ID(); ?>">

						    	<div class="wordpress-helpdesk-row">
						    		<div class="wordpress-helpdesk-col-sm-12">
						        		<h1 class="wordpress-helpdesk-single-title"><?php the_title(); ?></h1>
							            <div class="wordpress-helpdesk-meta-information">
								        <?php

								        // Topics
										$topics = get_the_terms($post->ID, 'faq_topics');
								        if (!empty($topics)) {							        
									        // Topics
									        foreach ($topics as $topic) {
									            $topic_color = get_term_meta($topic->term_id, 'wordpress_helpdesk_color');
									            if (isset($topic_color) && !empty($topic_color)) {
									                $topic_color = $topic_color[0];
									            } else {
									                $topic_color = '#000000';
									            }
									            echo '<a href="' . get_term_link($topic->term_id) . '">'
									                    . '<span class="wordpress-helpdesk-topics label wordpress-helpdesk-topic-' . $topic->slug . '" style="background-color: ' . $topic_color . '">'
									                        . $topic->name .
									                    '</span>' .
									                '</a> ';
									        }
								        }

								        // Views
								        if($wordpress_helpdesk_options['FAQShowViews'] === "1") {
									        $count = get_post_meta($post->ID, 'faq_popularity', true);
									        echo ' <span class="wordpress-helpdesk-viewed label" style="background-color: #03A9F4">' . sprintf(__('Viewed: %s', 'wordpress-helpdesk'), $count) . '</span>';
								        }
								        
								        // Rating System
								        if($wordpress_helpdesk_options['FAQRatingEnable'] === "1") {

								        	$likes = get_post_meta($post->ID, 'faq_likes', true);
							        		if(!$likes) {
							        			$likes = 0;
							        		}
								        	echo '<div class="wordpress-helpdesk-faq-rating">';

									        		echo '<a class="wordpress-helpdesk-faq-rating-like" data-post_id="' . $post->ID . '" href="#">';
									        			echo '<i class="fa fa-thumbs-up"></i> <span id="wordpress-helpdesk-faq-rating-like-count">' . $likes . '</span>';
									        		echo '</a>';
												
									        	if($wordpress_helpdesk_options['FAQRatingDisableDislikeButton'] === "0") {

									        		$dislikes = get_post_meta($post->ID, 'faq_dislikes', true);
									        		if(!$dislikes) {
									        			$dislikes = 0;
									        		}
								        			echo '<a class="wordpress-helpdesk-faq-rating-dislike" data-post_id="' . $post->ID . '" href="#">';
								        				echo '<i class="fa fa-thumbs-down"></i> <span id="wordpress-helpdesk-faq-rating-dislike-count">' . $dislikes . '</span>';
								        			echo '</a>';
							        			}
						        			echo '</div>';
								        }
								        ?>
							            </div>
									</div>
								</div>
						        <div class="wordpress-helpdesk-row">
									<div class="wordpress-helpdesk-col-sm-12">
										<div class="entry">
						            		<?php the_content(); ?>
						            	</div>
					            	</div>
						        </div>

								<div class="wordpress-helpdesk-row">
									<div class="wordpress-helpdesk-col-sm-12">
										<div class="wordpress-helpdesk-comments">
					            			<?php comments_template(); ?>
					            		</div>
					            	</div>
						        </div>
						    </div>
					    <?php endwhile; endif; ?>
					</div>
					<?php
					$checks = array('both', 'only_faq');
					if(in_array($wordpress_helpdesk_options['supportSidebarDisplay'], $checks)) {
					?>
					<div class="wordpress-helpdesk-col-sm-4 wordpress-helpdesk-sidebar <?php echo $sidebarClass ?>">
						<?php dynamic_sidebar('helpdesk-sidebar'); ?>
					</div>
					<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();