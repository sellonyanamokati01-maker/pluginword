<?php
defined('ABSPATH') || exit;

add_action('admin_init', function(){
  register_setting('dl_settings_group','dl_interest',['type'=>'number','default'=>5]);
  register_setting('dl_settings_group','dl_months',['type'=>'number','default'=>60]);

  register_setting('dl_settings_group','dl_color_text',['type'=>'string','default'=>'#0b0f14']);
  register_setting('dl_settings_group','dl_color_bg',['type'=>'string','default'=>'#ffffff']);
  register_setting('dl_settings_group','dl_color_border',['type'=>'string','default'=>'#e6e8ec']);
  register_setting('dl_settings_group','dl_color_btn',['type'=>'string','default'=>'#1f6feb']);
  register_setting('dl_settings_group','dl_color_muted',['type'=>'string','default'=>'#667085']);

  register_setting('dl_settings_group','dl_details_page_id',['type'=>'integer','default'=>0]);

  add_settings_section('dl_fin','Finance', function(){
    echo '<p>Used to estimate monthly installment.</p>';
  }, 'dl-settings');

  add_settings_field('dl_interest','Interest Rate (%)', function(){
    $v = get_option('dl_interest',5);
    echo '<input type="number" name="dl_interest" value="'.esc_attr($v).'" min="0" step="0.01">';
  }, 'dl-settings','dl_fin');

  add_settings_field('dl_months','Months', function(){
    $v = get_option('dl_months',60);
    echo '<input type="number" name="dl_months" value="'.esc_attr($v).'" min="1" step="1">';
  }, 'dl-settings','dl_fin');

  add_settings_section('dl_design','Design Colors', function(){
    echo '<p>Controls the whole UI via CSS variables.</p>';
  }, 'dl-settings');

  $colors = [
    'dl_color_text' => 'Text',
    'dl_color_bg' => 'Background',
    'dl_color_border' => 'Border',
    'dl_color_btn' => 'Button',
    'dl_color_muted' => 'Muted Text',
  ];

  foreach($colors as $opt => $label){
    add_settings_field($opt, $label, function() use($opt){
      $v = get_option($opt);
      echo '<input type="color" name="'.$opt.'" value="'.esc_attr($v).'">';
    }, 'dl-settings','dl_design');
  }

  add_settings_section('dl_pages','Pages', function(){
    echo '<p>Select the page that contains <code>[dl_single]</code>.</p>';
  }, 'dl-settings');

  add_settings_field('dl_details_page_id','Details Page', function(){
    $current = (int)get_option('dl_details_page_id',0);
    $pages = get_pages();
    echo '<select name="dl_details_page_id"><option value="0">— Select —</option>';
    foreach($pages as $p){
      echo '<option value="'.(int)$p->ID.'" '.selected($current,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
    }
    echo '</select>';
  }, 'dl-settings', 'dl_pages');
});

function dl_render_settings_page(){
  if(!current_user_can('manage_options')) return;
  echo '<div class="wrap"><h1>Dealership Settings</h1><form method="post" action="options.php">';
  settings_fields('dl_settings_group');
  do_settings_sections('dl-settings');
  submit_button();
  echo '</form></div>';
}

function dl_get_details_url(){
  $pid = (int)get_option('dl_details_page_id',0);
  return $pid ? get_permalink($pid) : home_url('/');
}
