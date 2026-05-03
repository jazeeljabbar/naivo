<?php

namespace KaizenCoders\UpdateURLS;

/**
 * Class Promo
 *
 * Handle Promotional Campaign
 *
 * @since   1.8.0
 * @package KaizenCoders\URL_Shortify
 *
 */
class Promo {
	/**
	 * Initialize Promotions
	 *
	 * @since 1.8.0
	 */
	public function init() {
		add_action( 'admin_init', [ $this, 'dismiss_promotions' ] );
		add_action( 'admin_notices', [ $this, 'handle_promotions' ] );
	}

	/**
	 * Get Valid Promotions.
	 *
	 * @since 1.5.12.2
	 * @return string[]
	 *
	 */
	public function get_valid_promotions() {
		return [
            'price_increase_notification'
		];
	}

	/**
	 * Dismiss Promotions.
	 *
	 * @since 1.5.12.2
	 */
	public function dismiss_promotions() {
		if ( isset( $_GET['kc_uu_dismiss_admin_notice'] ) && $_GET['kc_uu_dismiss_admin_notice'] == '1' && isset( $_GET['option_name'] ) ) {
			$option_name = sanitize_text_field( $_GET['option_name'] );

			$valid_options = $this->get_valid_promotions();

			if ( in_array( $option_name, $valid_options ) ) {

				update_option( 'kc_uu_' . $option_name . '_dismissed', 'yes', false );

				if ( in_array( $option_name, $valid_options ) ) {
					$referer = wp_get_referer();
					wp_safe_redirect( $referer );
				}

				exit();
			}
		}
	}

	/**
	 * Handle promotions activity.
	 *
	 * @since 1.5.12.2
	 */
	public function handle_promotions() {
		$price_increase_notification = [
			'title'                         => "<b class='text-red-600 text-xl'>" . __( 'Important Announcement',
					'update-urls' ) . "</b>",
			'start_date'                    => '2024-08-20',
			'end_date'                      => '2024-09-03',
			'start_after_installation_days' => 0,
			'pricing_url'                   => 'https://kaizencoders.com/url-shortify/',
			'promotion'                     => 'price_increase_notification',
			'message'                       => __( '<p class="text-xl">Buy an annual/lifetime URL Shortify PRO plan at the old price until <b class="text-red-600 text-xl">September 2, 2024</b></p>',
				'update-urls' ),
			'coupon_message'                => sprintf( __( 'Starting September 2, our updated pricing will apply to all our subscription plans... <b><a href="%s" target="_blank">Learn More</a></b>',
				'update-urls' ),
				'https://docs.kaizencoders.com/announcements/important-buy-an-annual-lifetime-url-shortify-pro-plan-at-the-old-price-until-september-2-2024?utm_source=plugin&utm_medium=notification&utm_campaign=price_increase_august_2024' ),
			'show_upgrade'                  => true,
			'check_plan'                    => 'free',
		];


        // Promotion.
        if ( Helper::can_show_promotion( $price_increase_notification ) ) {
            $this->show_promotion( 'price_increase_notification', $price_increase_notification );
        }
	}

	/**
	 * Show Promotion.
	 *
	 * @since 1.5.12.2
	 *
	 * @param $data      array
	 * @param $promotion string
	 *
	 */
	public function show_promotion( $promotion, $data ) {

		$current_screen_id = Helper::get_current_screen_id();

		if ( in_array( $promotion, [
				'initial_upgrade',
				'regular_upgrade_banner',
			] ) && Helper::is_plugin_admin_screen( $current_screen_id ) ) {
			$action = Helper::get_data( $_GET, 'action' );
			if ( 'statistics' === $action ) {
				?>
                <div class="wrap">
					<?php Helper::get_upgrade_banner( null, null, $data ); ?>
                </div>
				<?php
			}
		} else {
			$query_strings = [
				'kc_uu_dismiss_admin_notice' => 1,
				'option_name'                => $promotion,
			];
			?>

            <div class="wrap">
				<?php Helper::get_upgrade_banner( $query_strings, true, $data ); ?>
            </div>
			<?php
		}
	}

	/**
	 * Is Promo displayed and dismissed by user?
	 *
	 * @since 1.5.12.2
	 *
	 * @param $promo
	 *
	 * @return bool
	 *
	 */
	public function is_promotion_dismissed( $promotion ) {
		if ( empty( $promotion ) ) {
			return false;
		}

		$promotion_dismissed_option = 'kc_us_' . trim( $promotion ) . '_dismissed';

		return 'yes' === get_option( $promotion_dismissed_option );
	}
}
