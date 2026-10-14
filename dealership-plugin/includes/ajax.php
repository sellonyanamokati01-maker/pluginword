<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_dl_showroom','dl_ajax_showroom');
add_action('wp_ajax_nopriv_dl_showroom','dl_ajax_showroom');

function dl_ajax_showroom(){
  check_ajax_referer('dl_ajax_nonce','nonce');

  $make   = sanitize_text_field($_POST['make'] ?? '');
  $search = sanitize_text_field($_POST['search'] ?? '');
  $order  = sanitize_text_field($_POST['order'] ?? '');
  $page   = max(1,(int)($_POST['page'] ?? 1));
  $per    = max(6, min(24,(int)($_POST['per'] ?? 12)));

  $args = [
    'post_type'      => 'dl_car',
    'post_status'    => 'publish',
    'posts_per_page' => $per,
    'paged'          => $page,
  ];

  if($search !== '') $args['s'] = $search;

  if($make !== ''){
    $args['tax_query'] = [[
      'taxonomy'=>'dl_make',
      'field'=>'name',
      'terms'=>$make,
    ]];
  }

  if($order === 'ASC' || $order === 'DESC'){
    $args['orderby']  = 'meta_value_num';
    $args['meta_key'] = '_dl_price';
    $args['order']    = $order;
  } else {
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
  }

  $q = new WP_Query($args);

  $html = '';
  if($q->have_posts()){
    while($q->have_posts()){
      $q->the_post();
      $html .= dl_card_html(get_the_ID());
    }
    wp_reset_postdata();
  } else {
    $html = '<div class="dl-empty">No cars found.</div>';
  }

  wp_send_json_success([
    'html'      => $html,
    'found'     => (int)$q->found_posts,
    'page'      => $page,
    'max_pages' => (int)$q->max_num_pages,
  ]);
}
