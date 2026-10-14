<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

delete_option('dl_interest');
delete_option('dl_months');
delete_option('dl_color_text');
delete_option('dl_color_bg');
delete_option('dl_color_border');
delete_option('dl_color_btn');
delete_option('dl_color_muted');
delete_option('dl_details_page_id');

$leads = get_posts(['post_type'=>'dl_lead','numberposts'=>-1,'post_status'=>'any']);
foreach($leads as $l){ wp_delete_post($l->ID,true); }
