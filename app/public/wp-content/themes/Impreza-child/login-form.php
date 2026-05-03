<?php
/*
If you would like to edit this file, copy it to your current theme's directory and edit it there.
Theme My Login will always look in your theme's directory first, before using this default template.
*/
?>
<div class="tml tml-login" id="theme-my-login<?php $template->the_instance(); ?>">
	<?php $template->the_action_template_message( 'login' ); ?>
	<?php $template->the_errors(); ?>
	<form name="loginform" id="loginform<?php $template->the_instance(); ?>" action="<?php $template->the_action_url( 'login', 'login_post' ); ?>" method="post">
		<p class="tml-user-login-wrap">
			
			<input type="text" name="log" id="user_login<?php $template->the_instance(); ?>" placeholder="Email" class="input" value="<?php $template->the_posted_value( 'log' ); ?>" size="20" />
		</p>

		<p class="tml-user-pass-wrap">
			<input type="password" name="pwd" id="user_pass<?php $template->the_instance(); ?>" placeholder="Password" class="input" value="" size="20" autocomplete="off" />
		</p>


		<p class="tml-forgot-password">
				<a href="<?php echo get_home_url(); ?>/lostpassword/">Forgot Password? </a>
            </p>
		<?php do_action( 'login_form' ); ?>

		<div class="tml-rememberme-submit-wrap">
			<!-- <p class="tml-rememberme-wrap">
				<input name="rememberme" type="checkbox" id="rememberme<?php //$template->the_instance(); ?>" value="forever" />
				<label for="rememberme<?php ///$template->the_instance(); ?>"><?php //esc_attr_e( 'Remember Me', 'theme-my-login' ); ?></label>
			</p> -->

		

			<p class="tml-submit-wrap">
				<input type="submit" name="wp-submit" id="wp-submit<?php $template->the_instance(); ?>" value="<?php esc_attr_e( 'LogIn', 'theme-my-login' ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php $template->the_redirect_url( 'login' ); ?>" />
				<input type="hidden" name="instance" value="<?php $template->the_instance(); ?>" />
				<input type="hidden" name="action" value="login" />
			</p>
		</div>
	</form>
	<p class="tml-register-link">
	Don’t have account <a href="<?php echo get_home_url(); ?>/register/">Create One?</a></p>
	<?php //$template->the_action_links( array( 'login' => false ) ); ?>
</div>
