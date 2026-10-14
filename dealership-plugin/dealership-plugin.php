<?php
/*
Plugin Name: Dealership Plugin (Full Stable)
Description: Dealership admin + Media + showroom + single + leads + settings.
Version: 2.0.0
Author: MOKATI SELLONYANA
*/

defined('ABSPATH') || exit;

define('DL_PATH', plugin_dir_path(__FILE__));
define('DL_URL', plugin_dir_url(__FILE__));

function dl_require($rel){
  $file = DL_PATH . ltrim($rel,'/');
  if (file_exists($file)) require_once $file;
}

dl_require('includes/cpt.php');
dl_require('includes/settings.php');
dl_require('includes/leads.php');
dl_require('includes/shortcodes.php');
dl_require('includes/ajax.php');
dl_require('includes/admin-menu.php');

register_activation_hook(__FILE__, function(){
  if (function_exists('dl_register_car_cpt')) dl_register_car_cpt();
  if (function_exists('dl_register_lead_cpt')) dl_register_lead_cpt();
  flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function(){
  flush_rewrite_rules();
});

/** Frontend assets + theme variables */
add_action('wp_enqueue_scripts', function(){
  wp_enqueue_style('dl-style', DL_URL.'assets/css/style.css', [], '2.0.0');

  wp_enqueue_script('dl-single', DL_URL.'assets/js/single.js', ['jquery'], '2.0.0', true);

  // Always enqueue showroom JS (safe + avoids shortcode detection edge cases)
  wp_enqueue_script('dl-showroom', DL_URL.'assets/js/showroom.js', ['jquery'], '2.0.0', true);

  wp_localize_script('dl-showroom', 'dl_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('dl_ajax_nonce'),
  ]);

  // Settings -> CSS vars
  $text   = get_option('dl_color_text',   '#0b0f14');
  $bg     = get_option('dl_color_bg',     '#ffffff');
  $border = get_option('dl_color_border', '#e6e8ec');
  $btn    = get_option('dl_color_btn',    '#1f6feb');
  $muted  = get_option('dl_color_muted',  '#667085');

  wp_add_inline_style('dl-style', ":root{
    --dl-text:$text;
    --dl-bg:$bg;
    --dl-border:$border;
    --dl-btn:$btn;
    --dl-muted:$muted;
  }");
});

/** Admin: WP Media only on Add Car page */
add_action('admin_enqueue_scripts', function(){
  $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
  if ($page !== 'dl-add-car') return;

  wp_enqueue_media();
  wp_enqueue_script('jquery');

  wp_enqueue_script(
    'dl-admin-media',
    DL_URL.'assets/js/admin-media.js',
    ['jquery'],
    '2.0.0',
    true
  );
});
