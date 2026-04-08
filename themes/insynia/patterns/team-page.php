<?php
/**
 * Title: Team Page
 * Slug: insynia/team-page
 * Categories: team, about
 * Viewport width: 1400
 * Description: Full-page team layout with a hero section and two rows of four team member cards, styled for the Insynia dark theme.
 * Inserter: true
 *
 * @package Insynia
 */
?>

<!-- === Hero === -->
<!-- wp:group {"align":"full","className":"insynia-team-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull insynia-team-hero" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"align":"center","className":"insynia-eyebrow"} -->
		<p class="has-text-align-center insynia-eyebrow"><?php echo esc_html_x( 'Our People', 'Team page eyebrow', 'insynia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"xx-large"} -->
		<h1 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php echo esc_html_x( 'Meet the Team', 'Team page heading', 'insynia' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center"><?php echo esc_html_x( "We're a diverse group of builders, designers, and thinkers passionate about making WordPress development smarter with AI.", 'Team page description', 'insynia' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- === Leadership === -->
<!-- wp:group {"align":"full","className":"insynia-section-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull insynia-section-dark" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"textAlign":"center","fontSize":"large"} -->
	<h2 class="wp-block-heading has-text-align-center has-large-font-size"><?php echo esc_html_x( 'Leadership', 'Team section heading', 'insynia' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
	<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=47" alt="<?php echo esc_attr_x( 'Alexandra Chen', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Alexandra Chen', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Co-Founder & CEO', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Visionary leader with 12 years in WordPress and AI product development.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=11" alt="<?php echo esc_attr_x( 'Marcus Rodriguez', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Marcus Rodriguez', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Co-Founder & CTO', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Full-stack architect obsessed with developer experience and scalable systems.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=48" alt="<?php echo esc_attr_x( 'Priya Sharma', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Priya Sharma', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Head of Design', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Crafts pixel-perfect interfaces that balance beauty and usability.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=13" alt="<?php echo esc_attr_x( "James O'Brien", 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( "James O'Brien", 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'VP of Engineering', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Leads engineering with a focus on reliability, performance, and team growth.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- === Engineering & Product === -->
<!-- wp:group {"align":"full","className":"insynia-section-dark","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull insynia-section-dark" style="padding-top:0;padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:separator {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"backgroundColor":"contrast-3","className":"is-style-wide"} -->
	<hr class="wp-block-separator has-text-color has-contrast-3-color has-alpha-channel-opacity has-contrast-3-background-color has-background is-style-wide" style="margin-bottom:var(--wp--preset--spacing--40)"/>
	<!-- /wp:separator -->

	<!-- wp:heading {"textAlign":"center","fontSize":"large"} -->
	<h2 class="wp-block-heading has-text-align-center has-large-font-size"><?php echo esc_html_x( 'Engineering & Product', 'Team section heading', 'insynia' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
	<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=53" alt="<?php echo esc_attr_x( 'Yuki Tanaka', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Yuki Tanaka', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'AI Research Lead', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Explores the boundaries of LLMs applied to content creation and code generation.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=44" alt="<?php echo esc_attr_x( 'Sophia Williams', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Sophia Williams', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Head of Product', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Shapes the product roadmap with deep empathy for developer workflows.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=16" alt="<?php echo esc_attr_x( 'Ethan Brooks', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Ethan Brooks', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Senior Engineer', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Builds robust APIs and block editor integrations that power the platform.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"insynia-team-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group insynia-team-card" style="padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"is-style-rounded"} -->
				<figure class="wp-block-image size-full is-style-rounded"><img src="https://i.pravatar.cc/300?img=49" alt="<?php echo esc_attr_x( 'Lena Fischer', 'Team member photo', 'insynia' ); ?>" style="aspect-ratio:1;object-fit:cover;width:96px;height:96px"/></figure>
				<!-- /wp:image -->
				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><strong><?php echo esc_html_x( 'Lena Fischer', 'Team member name', 'insynia' ); ?></strong></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-role"} -->
				<p class="has-text-align-center insynia-team-role"><?php echo esc_html_x( 'Customer Success Lead', 'Team member role', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","className":"insynia-team-bio"} -->
				<p class="has-text-align-center insynia-team-bio"><?php echo esc_html_x( 'Partners with customers to unlock the full potential of the platform.', 'Team member bio', 'insynia' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<!-- === Join Us CTA === -->
<!-- wp:group {"align":"full","className":"insynia-cta","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull insynia-cta" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","fontSize":"x-large"} -->
		<h2 class="wp-block-heading has-text-align-center has-x-large-font-size"><?php echo esc_html_x( "Want to join us?", 'CTA heading', 'insynia' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center"><?php echo esc_html_x( "We're always looking for passionate people. Check out our open roles.", 'CTA description', 'insynia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
		<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
		<div class="wp-blocks-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#cta"><?php echo esc_html_x( 'See open roles', 'CTA button label', 'insynia' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
