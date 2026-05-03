<?php
/**
 * Coupon banner admin/public
 *
 * @link       
 * @since 1.3.5     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}
if( ! class_exists ( 'Wt_Smart_Coupon_Banner' ) ) {

    class Wt_Smart_Coupon_Banner {
        public $module_base='coupon_banner';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;
        public static $coupon_color_config=array();
        public static $banner_generated_count=0;
        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);

            add_filter('wt_sc_intl_default_val_needed_fields',array($this, 'default_val_needed_fields'), 10, 2);
        }

        /**
         * Get Instance
         * @since 1.3.5
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Banner();
            }
            return self::$instance;
        }

        /**
         * Banner display types
         * @since 1.3.5
         */
        public static function display_types()
        {
    
            return apply_filters('wt_smart_coupon_add_display_types',array(
                'banner'    => __('Banner','wt-smart-coupons-for-woocommerce-pro'),
                'widget'    => __('Widget','wt-smart-coupons-for-woocommerce-pro'),
                // 'popup'     => __('Popup','wt-smart-coupons-for-woocommerce-pro'),
            ));

        }

        /**
         * Display positions for banner type 
         * @since 1.3.5
         */
        public static function banner_display_positions()
        {

            return apply_filters('wt_smart_coupon_positions_for_banner',array(
                'top'    => __('Top','wt-smart-coupons-for-woocommerce-pro'),
                'bottom'    => __('Bottom','wt-smart-coupons-for-woocommerce-pro'),
                'custom'    => __('Custom','wt-smart-coupons-for-woocommerce-pro'),
            ));

        }

        /**
         * Display positions for widget type 
         * @since 1.3.5
         */
        public static function widget_display_positions()
        {

            return apply_filters('wt_smart_coupon_positions_for_widget',array(
                'top_left'          => __('Top Left','wt-smart-coupons-for-woocommerce-pro'),
                'top_right'         => __('Top Right','wt-smart-coupons-for-woocommerce-pro'),
                'bottom_left'       => __('Bottom Left','wt-smart-coupons-for-woocommerce-pro'),
                'bottom_right'      => __('Bottom Right','wt-smart-coupons-for-woocommerce-pro'),
                'custom'            => __('Custom','wt-smart-coupons-for-woocommerce-pro'),
            ));

        }


        public function default_val_needed_fields($default_val_needed_fields, $base_id)
        {
            if($base_id!=$this->module_id)
            {
                return $default_val_needed_fields;
            }

            return array(
                'inject_coupon'=>array(
                    'inject_coupon'=>0,
                    'inject_into_pages'=> array(),
                ),
                'display_banner'=>array(
                    'allow_dismissable'=>0,
                ),
                'banner_title'=>array(
                    'enable_banner_title'=>0,
                ),
                'banner_description'=>array(
                    'enable_banner_description'=>0,
                ),
                'coupon_timer'=>array(
                    'enable_coupon_timer'=>0,
                ),
                'coupon_section'=>array(
                    'enable_coupon_section'=>0,
                ),
            );
        }

        /**
         *  Default settings
         *  @since 1.3.5
        */
        public function default_settings($settings, $base_id)
        {
            if($base_id!=$this->module_id)
            {
                return $settings;
            }

            self::migrate_settings(); /* migrate old settings. If exists */

            return array(
                'display_banner'                => array(
                    'banner_type'               =>  'banner',
                    'height'                    =>  '',
                    'width'                     =>  '',
                    'bg_color'                  =>  '#3389ff',
                    'is_border'                 =>  false,
                    'border_color'              =>  '',
                    'banner_postion'            =>  'top',
                    'widget_postion'            =>  'bottom_left',
                    'allow_dismissable'         =>  true,
                    'dismissable_color'         => '#f3ecec61',
                    'action_on_click'           => 'apply_coupon',
                    'redirect_url'              => '',
                    'url_open_in_another_tab'   => false,
                ),
                'banner_title'                  =>  array(
                    'enable_banner_title'       => true,
                    'title'                     => __('FINAL HOURS!', 'wt-smart-coupons-for-woocommerce-pro'),
                    'font-size'                 => 20,
                    'font-color'                => '#f8f8f8',
                ),
                'banner_description'            => array(
                    'enable_banner_description' => true,
                    'title'                     => __('20% OFF', 'wt-smart-coupons-for-woocommerce-pro'),
                    'font-size'                 => 18,
                    'font-color'                => '#f8f8f8',
                ),
                'coupon_section'                => array(
                    'enable_coupon_section'     => true,
                    'font-size'                 => 15,
                    'font-color'                => '#ffffff',
                    'bg-color'                  => '',
                    'border-color'              => 'rgba(255, 255, 255, 0.37)',
                ),
                'coupon_timer'                  => array(
                    'enable_coupon_timer'       => true,
                    'font-size'                 => 13,
                    'font-color'                => '#ffffff',
                    'bg-color'                  => '',
                    'border-color'              => '#f3ecec61',
                    'action_on_expiry'          => 'hide_banner',
                    'expiry_text'               => '',
                ),
                'inject_coupon'                 => array(
                    'enable_inject_coupon'      => true,
                    'inject_coupon'             => 0,
                    'inject_into_pages'         => array(),
                ),
            );
        }

        /**
         *  Migrate old settings, If exists
         */
        protected static function migrate_settings()
        {
            $smart_coupon_option = get_option( 'wt_smart_coupon_options' );
            if(isset($smart_coupon_option['wt_coupon_banner_settings']) && !empty($smart_coupon_option['wt_coupon_banner_settings'])) /* old data exists */
            {
                $banner_settings=$smart_coupon_option['wt_coupon_banner_settings'];
                if(isset($banner_settings['inject_coupon']) && isset($banner_settings['inject_coupon']['inject_into_pages']))
                {
                    if(is_string($banner_settings['inject_coupon']['inject_into_pages']))
                    {
                        $banner_settings['inject_coupon']['inject_into_pages']=explode(",", $banner_settings['inject_coupon']['inject_into_pages']);
                    }
                }else{
                    $banner_settings['inject_coupon']['inject_into_pages']=array();
                }
                
                Wt_Smart_Coupon::update_settings($banner_settings, self::$module_id_static);

                //remove old option
                unset($smart_coupon_option['wt_coupon_banner_settings']);
                update_option('wt_smart_coupon_options', $smart_coupon_option);
            }
        }

        public static function get_current_banner_settings()
        {
            return Wt_Smart_Coupon::get_settings(self::$module_id_static);
        }

        public static function get_default_banner_settings()
        {
            return Wt_Smart_Coupon::default_settings(self::$module_id_static);
        }

        public static function prepare_banner($banner_data)
        {
            $coupon_id=(int) (isset($banner_data['coupon_id']) ? $banner_data['coupon_id'] : 0);
            if(!$coupon_id)
            {
                return false;
            }
            $coupon_data=array();
            $coupon_obj=new WC_Coupon($coupon_id);
            
            $coupon_data['coupon_expiry'] = Wt_Smart_Coupon_Public::get_coupon_expires($coupon_obj);
            $coupon_data['coupon_code']= $coupon_obj->get_code();
            $coupon_data['coupon_id']= $coupon_obj->get_id();

            return self::get_banner_html($banner_data, $coupon_data);
        }

        public static function timer_labels()
        {
            return  apply_filters( 'wt_smart_coupon_banner_timer_labels', array(
                'days' => __('Days','wt-smart-coupons-for-woocommerce-pro'),
                'hour' => __('Hours','wt-smart-coupons-for-woocommerce-pro'),
                'minutes' => __('Minutes','wt-smart-coupons-for-woocommerce-pro'),
                'seconds' => __('Seconds','wt-smart-coupons-for-woocommerce-pro'),
                'expired' => __('Expired !','wt-smart-coupons-for-woocommerce-pro'),
            ));
        }

        public static function enqueue_banner_style()
        {
            wp_enqueue_style('wt-sc-coupon-banner-css', plugin_dir_url(__FILE__) . 'assets/css/wt-sc-coupon-banner.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all');
        }

        public static function get_banner_html($banner_data, $coupon_data, $preview=false)
        {
            $coupon_expiry=$coupon_data['coupon_expiry'];
            $coupon_id=$coupon_data['coupon_id'];
            $coupon_code=$coupon_data['coupon_code'];

            $banner_settings = $banner_data['display_banner'];
            $timer_settings = $banner_data['coupon_timer'];
            $timer_labels=self::timer_labels();

            $style='';
            if(!$preview)
            {
                $style='position:fixed; ';
                if($banner_settings['banner_type']=='widget')
                {
                    if($banner_settings['widget_postion']!='custom')
                    {
                        switch($banner_settings['widget_postion']) {
                            case  'top_left' :
                                $style .= 'top:5px;left:5px;';
                                break;
                            case  'top_right' :
                                $style .= 'top:5px;right:5px;';
                                break;
                            case  'bottom_right' :
                                $style .= 'bottom:5px;right:5px;';
                                break;
                            case  'bottom_left' :
                                $style .= 'bottom:5px;left:5px;';
                                break;
                            default :
                                $style .= 'bottom:5px;left:5px;';
                                break;
                        }
                    }

                }else /* banner */
                {
                    if($banner_settings['banner_postion']!='custom')
                    {
                        switch($banner_settings['banner_postion']) {
                            case  'top' :
                                $style .= 'top:0px;left:0px;';
                                break;
                            case  'bottom' :
                                $style .= 'bottom:0px;left:0px;';
                                break;
                            default : 
                                $style .= 'top:0px;left:0px;';
                                break;
                        }
                    }
                }
            }

            if($banner_settings['banner_type']=='widget')
            {
                $height=(!empty($banner_settings['height']) ? $banner_settings['height'].'px' : 'auto');
                $width=(!empty($banner_settings['width']) ? $banner_settings['width'].'px' : 'auto');
                $style .= ' min-height:'.$height.'; width:'.$width.';';
            }

            if($banner_settings['bg_color']!="")
            {
                $style.=' background-color:'.$banner_settings['bg_color'].';';
            }

            if($banner_settings['border_color']!="")
            {
                $style .=' border:1px solid '.$banner_settings['border_color'].';';
            }

            $on_click_action_css_class=($banner_settings['action_on_click']=='apply_coupon' ? 'wt_apply_coupon_banner' :'');

            $attr='data-id="'.esc_attr($coupon_id).'" data-coupon="'.esc_attr($coupon_code).'"';

            if(!is_woocommerce() && !is_cart() && !is_checkout())
            {
                $cart_page = wc_get_cart_url();
                $attr.= ' data-redirect="'.esc_attr($cart_page.'/?wt_coupon='.$coupon_code).'"';
            }

            ob_start();           
            $enable_banner_click=false;
            $is_expired=false; /* always false on preview mode */

            if(!$preview) /* not admin preview */
            {
                self::$banner_generated_count++;
                if(!is_null($coupon_expiry)) /* coupon expiry enabled */
                {
                    if($coupon_expiry <= current_time('timestamp')) /* coupon expired */
                    {                       
                        if('display_text' === $timer_settings['action_on_expiry'])
                        {
                            $is_expired = true;
                        }else 
                        {
                            /* disable the banner */
                            return ob_get_clean();
                        }                      
                    }else
                    {
                        if($timer_settings['enable_coupon_timer'])
                        {
                            ?>
                            <script type="text/javascript">
                                jQuery(document).ready(function(){
                                    wt_banner_timer('<?php  echo esc_html(date('Y/m/d h:i:s A', $coupon_expiry)); ?>', jQuery('#wt_sc_coupon_banner_<?php echo esc_attr(self::$banner_generated_count); ?>'));
                                });
                            </script>
                            <?php
                        }
                    }
                }

                if('redirect_to_url' === $banner_settings['action_on_click'] && "" !== trim($banner_settings['redirect_url']))
                {
                    $link_target=($banner_settings['url_open_in_another_tab'] ? 'target="_blank"': '');
                    echo '<a href='.esc_attr($banner_settings['redirect_url']).' '.$link_target.'>';
                    $enable_banner_click=true;
                }
            }
            ?>
            <div class="wt_banner show_as_<?php echo sanitize_html_class($banner_settings['banner_type']); ?>  <?php echo sanitize_html_class($on_click_action_css_class); ?>"  style="<?php echo esc_attr($style); ?>"  <?php echo $attr; ?> id="wt_sc_coupon_banner_<?php echo esc_attr(self::$banner_generated_count); ?>">
                <div class="wt_banner_content" <?php echo ( $banner_settings['allow_dismissable'] ) ? ' style="padding-right:35px;"': ''; ?> >
                    <div class="coupon-items-container">
                        <div class="coupon-banner-items">                           
                            <?php 
                            /**
                             *  Banner title
                             */ 
                            $title_settings = $banner_data['banner_title'];
                            $title_text=trim($title_settings['title']);
                            $title_style='font-size:'.$title_settings['font-size'].'px; color:'.$title_settings['font-color'].';';
                            if(!$title_settings['enable_banner_title'])
                            {
                                $title_style.=' display:none;';
                            }
                            ?>
                            <div class="wt_banner_title" style="<?php echo esc_attr($title_style); ?>">
                                <?php esc_html_e($title_text, 'wt-smart-coupons-for-woocommerce-pro'); ?>
                            </div>
                            
                            <?php
                            /**
                             *  Banner description
                             */
                            $description_settings = $banner_data['banner_description'];
                            $description_text=trim($description_settings['title']);
                            $description_style='font-size:'.$description_settings['font-size'].'px; color:'.$description_settings['font-color'].';';
                            if(!$description_settings['enable_banner_description'])
                            {
                                $description_style.=' display:none;';
                            }
                            ?>
                            <div class="banner-description" style="<?php echo esc_attr($description_style); ?>">
                                <?php esc_html_e($description_text, 'wt-smart-coupons-for-woocommerce-pro'); ?>
                            </div>

                            
                            <?php
                            /**
                             *  Banner timer
                             */                                
                            $timer_style='font-size:'.$timer_settings['font-size'].'px; color:'.$timer_settings['font-color'].';';
                            if(!$timer_settings['enable_coupon_timer'])
                            {
                                $timer_style.=' display:none;';
                            }

                            $time_entry_style='';
                            if($timer_settings['bg-color'])
                            {
                                $time_entry_style.='background-color:'.$timer_settings['bg-color'].';';
                            }
                            if($timer_settings['border-color'])
                            {
                                $time_entry_style.=' border-color:'.$timer_settings['border-color'].';';
                            }
                            $time_entry_style=esc_attr($time_entry_style);
                            ?>
                            <div class="banner-coupon-timer" style="<?php echo esc_attr($timer_style); ?>">                               
                                <?php 
                                if($is_expired) /* always `false` on preview mode */
                                {
                                    echo (""!=$timer_settings['expiry_text'] ? $timer_settings['expiry_text'] : $timer_labels['expired']);
                                }else
                                {
                                ?>
                                    <div class="wt_timer timer-day">
                                        <div class="wt_time_entry">
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                        </div>
                                        <div class="wt_time_details">
                                            <small><?php echo esc_html($timer_labels['days']); ?></small>
                                        </div>
                                    </div>
                                    <div class="wt_timer timer-hours">
                                        <div class="wt_time_entry">
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                        </div>
                                        <div class="wt_time_details">
                                            <small><?php echo esc_html($timer_labels['hour']); ?></small>
                                        </div>
                                    </div>
                                    <div class="wt_timer timer-minutes">
                                        <div class="wt_time_entry">
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                        </div>
                                        <div class="wt_time_details">
                                            <small><?php echo esc_html($timer_labels['minutes']); ?></small>
                                        </div>
                                    </div>
                                    <div class="wt_timer timer-seconds">
                                        <div class="wt_time_entry">
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                            <span style="<?php echo $time_entry_style; ?>">0</span>
                                        </div>
                                        <div class="wt_time_details">
                                            <small><?php echo esc_html($timer_labels['seconds']); ?></small>
                                        </div>
                                    </div> 
                                <?php 
                                }
                                ?>                           
                            </div>


                            <?php
                            /**
                             *  Banner coupon code
                             */
                            $coupon_settings = $banner_data['coupon_section'];
                            $coupon_style='font-size:'.$coupon_settings['font-size'].'px; color:'.$coupon_settings['font-color'].';';
                            if(!$coupon_settings['enable_coupon_section'])
                            {
                                $coupon_style.=' display:none;';
                            }

                            if($coupon_settings['bg-color'])
                            {
                                $coupon_style.=' background-color:'.$coupon_settings['bg-color'].';';
                            }
                            if($coupon_settings['border-color'])
                            {
                                $coupon_style.=' border-color:'.$coupon_settings['border-color'].';';
                            }

                            ?>
                            <div class="banner-coupon-code"  style="<?php echo esc_attr($coupon_style); ?>">
                                <?php 
                                /** 
                                 * If coupon is New BOGO then display coupon name instead of coupon code, else display coupon code
                                 * @since 3.0.0
                                 */
                                echo esc_html( 'wbte_sc_bogo' === get_post_meta( $coupon_id, 'discount_type', true ) ? get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true ) : $coupon_code ); 
                                ?>
                            </div>

                        </div>
                    </div>
                    <?php
                    $dismissable_style="color:".$banner_settings['dismissable_color'].';';
                    if(!$banner_settings['allow_dismissable'] || "false"== $banner_settings['allow_dismissable'])
                    { 
                        $dismissable_style.=' display:none;';
                    }
                    ?>
                    <div class="wt_dismissable" style="<?php echo esc_attr($dismissable_style);?>"> X </div>
                </div>
            </div>
            <?php
            if($enable_banner_click)
            {
                echo '</a>';  
            }
            return ob_get_clean();
        }
    }
    Wt_Smart_Coupon_Banner::get_instance();
}