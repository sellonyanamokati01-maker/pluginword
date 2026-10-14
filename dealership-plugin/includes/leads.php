<?php
defined('ABSPATH') || exit;

function dl_register_lead_cpt(){
  register_post_type('dl_lead', [
    'labels' => ['name'=>'Leads','singular_name'=>'Lead'],
    'public' => false,
    'show_ui' => true,
    'supports' => ['title'],
    'show_in_menu' => false,
  ]);
}
add_action('init','dl_register_lead_cpt');

function dl_save_lead($car_id,$name,$email,$phone){
  $lead_id = wp_insert_post([
    'post_type'=>'dl_lead',
    'post_status'=>'publish',
    'post_title'=>$name.' - '.get_the_title($car_id),
  ]);

  if(!is_wp_error($lead_id)){
    update_post_meta($lead_id,'dl_car_id',(int)$car_id);
    update_post_meta($lead_id,'dl_email',$email);
    update_post_meta($lead_id,'dl_phone',$phone);

    wp_mail(
      get_option('admin_email'),
      'New Car Inquiry: '.get_the_title($car_id),
      "Name: $name\nEmail: $email\nPhone: $phone\nCar ID: $car_id"
    );
  }
}

add_shortcode('dl_lead_form', function($atts){
  $atts = shortcode_atts(['car_id'=>0],$atts);
  $car_id = (int)$atts['car_id'];
  if(!$car_id) return '<div class="dl-empty">Missing car.</div>';

  $msg = '';
  if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['dl_lead_nonce']) && wp_verify_nonce($_POST['dl_lead_nonce'],'dl_lead')){
    $name  = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    if($name && $email && $phone){
      dl_save_lead($car_id,$name,$email,$phone);
      $msg = '<div class="dl-toast">Inquiry sent.</div>';
    } else {
      $msg = '<div class="dl-toast">Fill all fields.</div>';
    }
  }

  ob_start();
  echo $msg; ?>
  <form class="dl-form" method="post">
    <?php wp_nonce_field('dl_lead','dl_lead_nonce'); ?>
    <div class="dl-form__row">
      <input name="name" placeholder="Full name" required>
      <input name="phone" placeholder="Phone" required>
    </div>
    <input name="email" type="email" placeholder="Email" required>
    <button class="dl-btn dl-btn--solid dl-wide" type="submit">Send inquiry</button>
  </form>
  <?php
  return ob_get_clean();
});
