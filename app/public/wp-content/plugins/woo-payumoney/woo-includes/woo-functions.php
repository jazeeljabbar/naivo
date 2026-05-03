<?php

     /**
      * Functions used by plugins
      */
     if (!class_exists('WCPM_Dependencies'))
	 require_once 'class-wcpm-dependencies.php';

     /**
      * WC Detection
      */
     if (!function_exists('is_woocommerce_active')) {

	 function is_woocommerce_active() {
	     return WCPM_Dependencies::woocommerce_active_check();
	 }

     }

