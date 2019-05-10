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

$status = get_the_terms($post->ID, 'ticket_status');
$system = get_the_terms($post->ID, 'ticket_system');
$type = get_the_terms($post->ID, 'ticket_type');
$priority = get_the_terms($post->ID, 'ticket_priority');
$solvedStatus = absint($wordpress_helpdesk_options['defaultSolvedStatus']);

get_header();
?>
<div class="wordpress-helpdesk">
	<div id="main-content" class="main-content">
		<div class="container">
			<div class="container_inner default_template_holder clearfix page_container_inner">
				<div class="wordpress-helpdesk-row">
					<?php

			        $checks = array('none', 'only_faq');
			        if(in_array($wordpress_helpdesk_options['supportSidebarDisplay'], $checks)) {
			            echo '<div class="wordpress-helpdesk-col-sm-12">';
			        } else {
			            echo '<div class="wordpress-helpdesk-col-sm-8 ' . $contentClass . '">';
			        }
			        ?>
						<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

						    <div <?php post_class() ?> id="post-<?php the_ID(); ?>">

				    			<div class="wordpress-helpdesk-row">
				    				<div class="wordpress-helpdesk-col-sm-6">
						    			<?php
								        $supportMyTicketsPage = $wordpress_helpdesk_options['supportMyTicketsPage'];
								        if (!empty($supportMyTicketsPage)) {
								            $redirect_base = get_permalink($supportMyTicketsPage);
								            echo '<a href="' . $redirect_base . '" id="wordpress_helpdesk_back_to_my_tickets" class="wordpress_helpdesk_back_to_my_tickets">' 
								            . __('< Back to My Tickets', 'wordpress-helpdesk') . 
								            '</a>';
								        }
						    			?>
					    			</div>
					    			<div class="wordpress-helpdesk-col-sm-6">
					    				<?php if(!empty($solvedStatus) && ($status[0]->term_id !== $solvedStatus)) { ?>
										<form action="<?php echo esc_url($_SERVER['REQUEST_URI']) ?>" class="wordpress-helpdesk-ticket-solved" method="POST">
											<input type="hidden" name="helpdesk_ticket_solved">
											<input type="hidden" name="helpdesk_ticket" value="<?php echo $post->ID ?>">
											<input type="submit" class="wordpress-helpdesk-ticket-solved-btn" value="<?php echo __('Close ticket', 'wordpress-helpdesk') ?>">
										</form>
										<?php } ?>
				    				</div>
					    			<div class="wordpress-helpdesk-col-sm-12">
						        		<h1 class="wordpress-helpdesk-single-title"><?php the_title(); ?></h1>
										<div class="wordpress-helpdesk-meta-information">
								            <?php
								            if (!empty($status)) {
								                $status_color = get_term_meta($status[0]->term_id, 'wordpress_helpdesk_color');
								                if (isset($status_color[0]) && !empty($status_color[0])) {
								                    $status_color = $status_color[0];
								                } else {
								                    $status_color = '#000000';
								                }
								                echo '<span class="wordpress-helpdesk-my-tickets-status label wordpress-helpdesk-status-' . $status[0]->slug . '" style="background-color: ' . $status_color . '">' . $status[0]->name . '</span> ';
								            }

								            if (!empty($system)) {
								                $system_color = get_term_meta($system[0]->term_id, 'wordpress_helpdesk_color');
								                if (isset($system_color[0]) && !empty($system_color[0])) {
								                    $system_color = $system_color[0];
								                } else {
								                    $system_color = '#000000';
								                }
								                echo '<span class="wordpress-helpdesk-my-tickets-system label wordpress-helpdesk-system-' . $system[0]->slug . '" style="background-color: ' . $system_color . '">' . $system[0]->name . '</span> ';
								            }

								            if (!empty($type)) {
								                $type_color = get_term_meta($type[0]->term_id, 'wordpress_helpdesk_color');
								                if (isset($type_color[0]) && !empty($type_color[0])) {
								                    $type_color = $type_color[0];
								                } else {
								                    $type_color = '#000000';
								                }
								                echo '<span class="wordpress-helpdesk-my-tickets-type label wordpress-helpdesk-type-' . $type[0]->slug . '" style="background-color: ' . $type_color . '">' . $type[0]->name . '</span> ';
								            }

								            if (!empty($priority)) {
								                $priority_color = get_term_meta($priority[0]->term_id, 'wordpress_helpdesk_color');
								                if (isset($priority_color[0]) && !empty($priority_color[0])) {
								                    $priority_color = $priority_color[0];
								                } else {
								                    $priority_color = '#000000';
								                }
								                echo '<span class="wordpress-helpdesk-my-tickets-priority label wordpress-helpdesk-priority-' . $priority[0]->slug . '" style="background-color: ' . $priority_color . '">' . __('Priority', 'wordpress-helpdesk') . ': ' . $priority[0]->name . '</span> ';
								            }
								            ?>
							            </div>
									</div>
								</div>
								<div class="wordpress-helpdesk-row">
									<div class="wordpress-helpdesk-col-xs-6">
										<div class="wordpress-helpdesk-reporter-box">
											<div class="wordpress-helpdesk-row">
												<div class="wordpress-helpdesk-col-sm-3">
													<?php echo get_avatar($post->post_author, 100); ?>
							                	</div>
												<div class="wordpress-helpdesk-col-sm-9">
													<h4 class="wordpress-helpdesk-reporter-box-title"><?php echo sprintf( __('Reporter: %s', 'wordpress-helpdesk'), get_the_author()) ?></h4>
													<?php
											        echo  __('Created on:', 'wordpress-helpdesk') . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->post_date)) .'<br>';
											        echo __('Email:', 'wordpress-helpdesk') . ' ' . get_the_author_meta('email')
								                ?>
												</div>
						                	</div>
						                </div>
									</div>
				                	<div class="wordpress-helpdesk-col-xs-6">
										<div class="wordpress-helpdesk-agent-box">
											<?php 
											$agentID = get_post_meta($post->ID, 'agent', true);
											if(empty($agentID)) {
											?>
												<div class="wordpress-helpdesk-row">
													<div class="wordpress-helpdesk-col-sm-3">
														<?php echo get_avatar(NULL, 100); ?>
								                	</div>
													<div class="wordpress-helpdesk-col-sm-9">
														<h4 class="wordpress-helpdesk-agent-box-title"><?php echo __('No Agent assigned yet.', 'wordpress-helpdesk') ?></h4>
													</div>
							                	</div>
											<?php
											} else {
												$agent = get_userdata($agentID)->data;
												?>
												<div class="wordpress-helpdesk-row">
													<div class="wordpress-helpdesk-col-sm-3">
														<?php echo get_avatar($agentID, 100); ?>
								                	</div>
													<div class="wordpress-helpdesk-col-sm-9">
													
													<h4 class="wordpress-helpdesk-agent-box-title"><?php echo sprintf( __('Agent: %s', 'wordpress-helpdesk'), $agent->display_name) ?></h4>
													<?php
												        echo __('Created on:', 'wordpress-helpdesk') . ' ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($post->post_date)) .'<br>';
												        echo __('Email:', 'wordpress-helpdesk') . ' ' . $agent->user_email
									                ?>
													</div>
							                	</div>
						                	<?php
						                	}
						                	?>
						                </div>
									</div>
					        	</div>

						        <div class="wordpress-helpdesk-row">
									<div class="wordpress-helpdesk-col-sm-12">
										<div class="entry">
											<h3 class="wordpress-helpdesk-single-description-title"><?php echo __('Description') ?></h3>
						            		<?php the_content(); ?>
						            	</div>
					            	</div>
						        </div><hr>
						        <?php
						        $attachment_ids = get_posts(array(
						            'post_type' => 'attachment',
						            'numberposts' => -1,
						            'post_parent' => $post->ID,
						        ));
						        
						        if (isset($attachment_ids) && !empty($attachment_ids)) {
						        	echo '<div class="wordpress-helpdesk-row">';
							            echo '<div class="wordpress-helpdesk-ticket-attachments">';
							            echo '<div class="wordpress-helpdesk-col-sm-12">';
							            	echo '<h3 class="wordpress-helpdesk-ticket-attachments-title">' . __('Attachments', 'wordpress-helpdesk') . '</h3>';
							            echo '</div>';
							            foreach ($attachment_ids as $attachment_id) {
							                $attachment_id = $attachment_id->ID;
							                $full_url = wp_get_attachment_url($attachment_id);
							                $thumb_url = wp_get_attachment_thumb_url($attachment_id);

							                echo '<div class="wordpress-helpdesk-col-sm-3"><a href="' . $full_url . '" target="_blank">';
							                    echo '<img src="' . $thumb_url . '" alt="">';
							                echo '</a></div>';
							            }
							            echo '</div>';
						            echo '</div><hr>';
						        }
						        ?>
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
					$checks = array('both', 'only_ticket');
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