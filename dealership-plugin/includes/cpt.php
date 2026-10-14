<?php
defined('ABSPATH') || exit;

function dl_register_car_cpt(){
  register_post_type('dl_car', [
    'labels' => ['name'=>'Cars','singular_name'=>'Car'],
    'public' => true,
    'has_archive' => false,
    'show_in_rest' => true,
    'supports' => ['title','editor','thumbnail'],
    'show_ui' => true,
    'show_in_menu' => false,
  ]);

  register_taxonomy('dl_make', ['dl_car'], [
    'label'=>'Makes','public'=>true,'hierarchical'=>true,'show_in_rest'=>true
  ]);
  register_taxonomy('dl_model', ['dl_car'], [
    'label'=>'Models','public'=>true,'hierarchical'=>true,'show_in_rest'=>true
  ]);
  register_taxonomy('dl_condition', ['dl_car'], [
    'label'=>'Condition','public'=>true,'hierarchical'=>false,'show_in_rest'=>true
  ]);
  register_taxonomy('dl_body', ['dl_car'], [
    'label'=>'Body Type','public'=>true,'hierarchical'=>false,'show_in_rest'=>true
  ]);
}
add_action('init','dl_register_car_cpt');
