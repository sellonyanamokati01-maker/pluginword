<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
  add_menu_page(
    'Dealership',
    'Dealership',
    'manage_options',
    'dl-dashboard',
    'dl_admin_all_cars',
    'dashicons-car',
    60
  );

  add_submenu_page('dl-dashboard','All Cars','All Cars','manage_options','dl-dashboard','dl_admin_all_cars');

  // slug MUST be dl-add-car (matches admin enqueue)
  add_submenu_page('dl-dashboard','Add New Car','Add New Car','manage_options','dl-add-car','dl_admin_add_car');

  add_submenu_page('dl-dashboard','Leads','Leads','manage_options','edit.php?post_type=dl_lead');

  add_submenu_page('dl-dashboard','Makes','Makes','manage_options','edit-tags.php?taxonomy=dl_make&post_type=dl_car');
  add_submenu_page('dl-dashboard','Models','Models','manage_options','edit-tags.php?taxonomy=dl_model&post_type=dl_car');
  add_submenu_page('dl-dashboard','Condition','Condition','manage_options','edit-tags.php?taxonomy=dl_condition&post_type=dl_car');
  add_submenu_page('dl-dashboard','Body Type','Body Type','manage_options','edit-tags.php?taxonomy=dl_body&post_type=dl_car');

  add_submenu_page('dl-dashboard','Settings','Settings','manage_options','dl-settings','dl_render_settings_page');
});

function dl_admin_all_cars(){
  if(!current_user_can('manage_options')) return;

  $q = new WP_Query([
    'post_type'=>'dl_car',
    'post_status'=>'publish',
    'posts_per_page'=>50,
    'orderby'=>'date',
    'order'=>'DESC'
  ]);

  echo '<div class="wrap"><h1>All Cars</h1>';
  echo '<p><a class="button button-primary" href="'.admin_url('admin.php?page=dl-add-car').'">Add New Car</a></p>';

  echo '<table class="widefat striped"><thead><tr><th>Car</th><th>Price</th><th>Featured</th><th>Actions</th></tr></thead><tbody>';

  if($q->have_posts()){
    while($q->have_posts()){
      $q->the_post();
      $id = get_the_ID();
      $price = (float)get_post_meta($id,'_dl_price',true);
      $featured = get_post_meta($id,'_dl_featured',true)==='yes'?'Yes':'No';

      echo '<tr>';
      echo '<td><strong>'.esc_html(get_the_title()).'</strong></td>';
      echo '<td>R'.number_format($price,0).'</td>';
      echo '<td>'.esc_html($featured).'</td>';
      echo '<td><a class="button" href="'.get_edit_post_link($id).'">Edit</a> ';
      echo '<a class="button button-link-delete" href="'.get_delete_post_link($id,'',true).'">Trash</a></td>';
      echo '</tr>';
    }
    wp_reset_postdata();
  } else {
    echo '<tr><td colspan="4">No cars yet.</td></tr>';
  }

  echo '</tbody></table></div>';
}

function dl_admin_add_car(){
  if(!current_user_can('manage_options')) return;

  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['dl_add_car_nonce']) && wp_verify_nonce($_POST['dl_add_car_nonce'],'dl_add_car')){
    $title = sanitize_text_field($_POST['title'] ?? '');
    $desc  = wp_kses_post($_POST['desc'] ?? '');
    $price = sanitize_text_field($_POST['price'] ?? '');

    $post_id = wp_insert_post([
      'post_type' => 'dl_car',
      'post_status' => 'publish',
      'post_title' => $title,
      'post_content' => $desc,
    ]);

    if($post_id && !is_wp_error($post_id)){
      update_post_meta($post_id,'_dl_price',$price);
      update_post_meta($post_id,'_dl_featured', (isset($_POST['featured']) && $_POST['featured']==='yes') ? 'yes' : 'no');

      if(!empty($_POST['make_term'])) wp_set_post_terms($post_id, [(int)$_POST['make_term']], 'dl_make');
      if(!empty($_POST['model_term'])) wp_set_post_terms($post_id, [(int)$_POST['model_term']], 'dl_model');
      if(!empty($_POST['condition_term'])) wp_set_post_terms($post_id, [(int)$_POST['condition_term']], 'dl_condition');
      if(!empty($_POST['body_term'])) wp_set_post_terms($post_id, [(int)$_POST['body_term']], 'dl_body');

      $featured_id = (int)($_POST['featured_image_id'] ?? 0);
      if($featured_id) set_post_thumbnail($post_id, $featured_id);

      $gallery_ids = sanitize_text_field($_POST['gallery_image_ids'] ?? '');
      update_post_meta($post_id,'_dl_gallery',$gallery_ids);

      echo '<div class="notice notice-success"><p>Car added.</p></div>';
    } else {
      echo '<div class="notice notice-error"><p>Failed to add car.</p></div>';
    }
  }

  $make_terms  = get_terms(['taxonomy'=>'dl_make','hide_empty'=>false]);
  $model_terms = get_terms(['taxonomy'=>'dl_model','hide_empty'=>false]);
  $cond_terms  = get_terms(['taxonomy'=>'dl_condition','hide_empty'=>false]);
  $body_terms  = get_terms(['taxonomy'=>'dl_body','hide_empty'=>false]);

  echo '<div class="wrap"><h1>Add New Car</h1><form method="post">';
  wp_nonce_field('dl_add_car','dl_add_car_nonce');

  echo '<table class="form-table"><tbody>';
  echo '<tr><th>Title</th><td><input name="title" class="regular-text" required></td></tr>';
  echo '<tr><th>Description</th><td><textarea name="desc" class="large-text" rows="6"></textarea></td></tr>';
  echo '<tr><th>Price (R)</th><td><input name="price" type="number" class="regular-text" required></td></tr>';

  echo '<tr><th>Make</th><td><select name="make_term"><option value="">Select</option>';
  foreach($make_terms as $t) echo '<option value="'.(int)$t->term_id.'">'.esc_html($t->name).'</option>';
  echo '</select></td></tr>';

  echo '<tr><th>Model</th><td><select name="model_term"><option value="">Select</option>';
  foreach($model_terms as $t) echo '<option value="'.(int)$t->term_id.'">'.esc_html($t->name).'</option>';
  echo '</select></td></tr>';

  echo '<tr><th>Condition</th><td><select name="condition_term"><option value="">Select</option>';
  foreach($cond_terms as $t) echo '<option value="'.(int)$t->term_id.'">'.esc_html($t->name).'</option>';
  echo '</select></td></tr>';

  echo '<tr><th>Body Type</th><td><select name="body_term"><option value="">Select</option>';
  foreach($body_terms as $t) echo '<option value="'.(int)$t->term_id.'">'.esc_html($t->name).'</option>';
  echo '</select></td></tr>';

  echo '<tr><th>Featured</th><td><label><input type="checkbox" name="featured" value="yes"> Yes</label></td></tr>';

  echo '<tr><th>Featured Image</th><td>
    <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
    <button type="button" class="button" id="dl_pick_featured">Choose Featured Image</button>
    <div id="dl_featured_preview" style="margin-top:10px;"></div>
  </td></tr>';

  echo '<tr><th>Gallery Images</th><td>
    <input type="hidden" id="gallery_image_ids" name="gallery_image_ids" value="">
    <button type="button" class="button" id="dl_pick_gallery">Choose Gallery Images</button>
    <div id="dl_gallery_preview" style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;"></div>
  </td></tr>';

  echo '</tbody></table>';
  echo '<p><button class="button button-primary" type="submit">Save Car</button></p>';
  echo '</form></div>';
}
