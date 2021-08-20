<div class="wrap about-wrap wu-about-wrap full-width-layout">
		
    <h1><?php _e('You are using WP Ultimo!', 'wp-ultimo'); ?></h1>

		<p class="about-text">
      <?php _e('Thank you for using WP Ultimo to build your premium network! This page explains the motives behing WP Ultimo and lists some useful information for you!', 'wp-ultimo'); ?>
    </p>

		<div class="wp-badge wu-badge"><?php printf(__('Version %s', 'wp-ultimo'), WP_Ultimo()->version); ?></div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="about.php" class="nav-tab nav-tab-active"><?php _e('About WP Ultimo', 'wp-ultimo'); ?></a>
			<!-- <a href="credits.php" class="nav-tab"><?php _e('Credits', 'wp-ultimo'); ?></a>
			<a href="credits.php" class="nav-tab"><?php _e('Changelog', 'wp-ultimo'); ?></a> -->
    </h2>
    
    <div class="inline-svg full-width">
      &nbsp;
		</div>

		<div class="floating-header-section">
			<div class="section-header">
				<h2 style="position: static; top: auto; bottom: auto; width: auto;"><?php _e('The Goal:', 'wp-ultimo'); ?> <br><?php _e('A <strong>Complete</strong> Solution', 'wp-ultimo'); ?></h2>
			</div>

			<div class="section-content">
				<div class="section-item section-item-full-width">
					<!-- <h3>Draft and Schedule Site Design Customizations</h3> -->
					<p><?php _e("Despite the number of solutions around, creating and maintaining a premium network of sites, like <strong>WordPress.com</strong> for example, can be really complicated. <strong>WP Ultimo</strong> was developed as a new way to achieve this goal. A <strong>simpler</strong> and <strong>better way</strong>.", 'wp-ultimo'); ?></p>
          <p><?php _e("In WP Ultimo you will find everything you need to have a premium network up and running in less than 5 minutes. After all, WordPress became famous because of its ease of installation - we believe that WP Ultimo should be like that as well.", 'wp-ultimo'); ?></p>

          <p><?php _e("All the core functionality - often delegated to add-ons by other solutions -, such as domain mapping, for example, is present in our core code. We do offer add-ons, but only for non-crucial features and to enhance your network functionality. Enjoy!", 'wp-ultimo'); ?></p>
				</div>
		  </div>
    </div>

		<div class="floating-header-section">
			<div class="section-header">
				<h2 style="position: static; top: auto; bottom: auto; width: auto;"><?php _e('Community & Support', 'wp-ultimo'); ?></h2>
			</div>

			<div class="section-content">

				<div class="section-item">
					<h3><?php _e('Join our Forums!', 'wp-ultimo'); ?></h3>
					<p><?php _e('Discuss your ideas and questions with other members creating their own networks!', 'wp-ultimo'); ?></p>
					<p><a href="<?php echo WU_Links()->get_link('forums'); ?>" target="_blank"><?php _e('Visit the Forums &rarr;', 'wp-ultimo'); ?></a></p>
        </div>

        <div class="section-item">
					<h3><?php _e('Read our Knowledge Base', 'wp-ultimo'); ?></h3>
					<p><?php _e('We have a pretty extensive list of tutorials available to help you get started!', 'wp-ultimo'); ?></p>
					<p><a href="<?php echo WU_Links()->get_link('documentation'); ?>" target="_blank"><?php _e('Visit the Knowledge Base &rarr;', 'wp-ultimo'); ?></a></p>
        </div>

        <div class="section-item">
					<h3><?php _e('Roadmap', 'wp-ultimo'); ?></h3>
					<p><?php _e('Our roadmap is public and you get to suggest and vote for features you would like to see implemented!', 'wp-ultimo'); ?></p>
					<p><a href="<?php echo WU_Links()->get_link('roadmap'); ?>" target="_blank"><?php _e('View the Roadmap &rarr;', 'wp-ultimo'); ?></a></p>
        </div>

        <div class="section-item">
					<h3><?php _e('Contact Us', 'wp-ultimo'); ?></h3>
					<p><?php _e('If you are having an issue with WP Ultimo that you are not being able to sort out, send us a note and we will help you right away.', 'wp-ultimo'); ?></p>
					<p><a href="mailto:support@wpultimo.com" target="_blank"><?php _e('Send us a Message &rarr;', 'wp-ultimo'); ?></a></p>
        </div>
        
			</div>
    </div>

		<hr>

		<div class="changelog">
		  
      <h2><?php _e('Complete Changelog', 'wp-ultimo'); ?></h2>
      <p class="description"><?php _e('The complete history of WP Ultimo versions up to this date.', 'wp-ultimo'); ?></p>

      <div v-html="changelog" id="complete-changelog" class="complete-changelog"></div>
		
    </div>

		<hr>

		<div class="return-to-dashboard">
				<a href="<?php echo network_admin_url(); ?>"><?php _e('Go to Dashboard'); ?></a>
		</div>
	</div>

  <script>
		(function($) {
			$(function() {
				new Vue({
					el: "#complete-changelog",
					data: {
						changelog: 'Loading',
					},
					mounted: function() {
						var that = this;
						$.ajax(ajaxurl, {
							data: {
								action: 'wu_serve_changelogs',
								_wpnonce: <?php echo json_encode(wp_create_nonce('serve_changelogs')); ?>
							},
							success: function(data) {
								that.changelog = data;
							}
						})
					}
				})
			});
		})( jQuery );
	</script>
