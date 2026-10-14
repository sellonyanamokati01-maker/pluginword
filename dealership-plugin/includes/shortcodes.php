<?php
defined('ABSPATH') || exit;

function dl_monthly($price){
  $price = (float)$price;
  if($price<=0) return 0;
  $interest = (float)get_option('dl_interest',5);
  $months = (int)get_option('dl_months',60);
  if($months<=0) $months=60;
  $total = $price + ($price*($interest/100));
  return round($total/$months);
}

function dl_card_html($id){
  $price   = (float)get_post_meta($id,'_dl_price',true);
  $monthly = dl_monthly($price);

  $make  = wp_get_post_terms($id,'dl_make',['fields'=>'names']);
  $model = wp_get_post_terms($id,'dl_model',['fields'=>'names']);
  $cond  = wp_get_post_terms($id,'dl_condition',['fields'=>'names']);

  $details = add_query_arg(['car_id'=>$id], dl_get_details_url());

  ob_start(); ?>
  <a class="dl-card dl-reveal" href="<?php echo esc_url($details); ?>">
    <div class="dl-card__media">
      <?php if(has_post_thumbnail($id)) echo get_the_post_thumbnail($id,'medium'); else echo '<div class="dl-media-fallback"></div>'; ?>
      <span class="dl-priceChip">R<?php echo number_format($price,0); ?></span>
      <?php if(!empty($cond[0])): ?><span class="dl-badge"><?php echo esc_html($cond[0]); ?></span><?php endif; ?>
    </div>
    <div class="dl-card__body">
      <div class="dl-card__title"><?php echo esc_html(get_the_title($id)); ?></div>
      <div class="dl-card__meta"><?php echo esc_html(($make[0]??'')); ?><span class="dl-dot">•</span><?php echo esc_html(($model[0]??'')); ?></div>
      <div class="dl-card__priceRow">
        <div class="dl-monthly">≈ R<?php echo number_format($monthly,0); ?>/mo</div>
        <span class="dl-btn dl-btn--ghost">View</span>
      </div>
    </div>
  </a>
  <?php return ob_get_clean();
}

add_shortcode('dl_showroom', function(){
  $per = 12;

  $q = new WP_Query([
    'post_type'=>'dl_car',
    'post_status'=>'publish',
    'posts_per_page'=>$per,
    'paged'=>1,
    'orderby'=>'date',
    'order'=>'DESC',
  ]);

  $makes = get_terms(['taxonomy'=>'dl_make','hide_empty'=>false]);

  ob_start(); ?>
  <section class="dl-shell">
    <header class="dl-hero">
      <div>
        <h1 class="dl-h1">Buy a car</h1>
        <p class="dl-sub">Search, filter and compare vehicles.</p>
      </div>
      <div class="dl-search">
        <input id="dl-search" placeholder="Search make, model, keyword..." />
        <button id="dl-searchBtn" type="button" class="dl-iconBtn" aria-label="Search">⌕</button>
      </div>
    </header>

    <div class="dl-mobileBar">
      <button type="button" id="dl-openFilters" class="dl-btn dl-btn--solid">Filters</button>
      <div class="dl-count"><span id="dl-found"><?php echo (int)$q->found_posts; ?></span> vehicles</div>
    </div>

    <div class="dl-bar">
      <div class="dl-bar__left">
        <select id="dl-make" class="dl-select">
          <option value="">All Makes</option>
          <?php foreach($makes as $m): ?><option value="<?php echo esc_attr($m->name); ?>"><?php echo esc_html($m->name); ?></option><?php endforeach; ?>
        </select>
        <select id="dl-sort" class="dl-select">
          <option value="">Sort</option>
          <option value="ASC">Price: Low → High</option>
          <option value="DESC">Price: High → Low</option>
        </select>
        <button type="button" id="dl-clear" class="dl-btn dl-btn--ghost">Clear</button>
      </div>
      <div class="dl-bar__right">
        <div class="dl-count"><span id="dl-found-desktop"><?php echo (int)$q->found_posts; ?></span> vehicles</div>
      </div>
    </div>

    <div id="dl-drawer" class="dl-drawer" aria-hidden="true">
      <div class="dl-drawer__sheet">
        <div class="dl-drawer__head">
          <div class="dl-drawer__title">Filters</div>
          <button type="button" id="dl-closeFilters" class="dl-iconBtn" aria-label="Close">✕</button>
        </div>
        <div class="dl-drawer__body">
          <label class="dl-label">Make</label>
          <select id="dl-make-mobile" class="dl-select">
            <option value="">All Makes</option>
            <?php foreach($makes as $m): ?><option value="<?php echo esc_attr($m->name); ?>"><?php echo esc_html($m->name); ?></option><?php endforeach; ?>
          </select>
          <label class="dl-label">Sort</label>
          <select id="dl-sort-mobile" class="dl-select">
            <option value="">Sort</option>
            <option value="ASC">Price: Low → High</option>
            <option value="DESC">Price: High → Low</option>
          </select>
          <button type="button" id="dl-apply" class="dl-btn dl-btn--solid dl-wide">Apply</button>
          <button type="button" id="dl-clear-mobile" class="dl-btn dl-btn--ghost dl-wide">Clear</button>
        </div>
      </div>
    </div>

    <div id="dl-skeleton" class="dl-skeleton" style="display:none;">
      <?php for($i=0;$i<8;$i++): ?><div class="dl-skelCard"></div><?php endfor; ?>
    </div>

    <div id="dl-grid" class="dl-grid">
      <?php
      if($q->have_posts()){
        while($q->have_posts()){ $q->the_post(); echo dl_card_html(get_the_ID()); }
        wp_reset_postdata();
      } else {
        echo '<div class="dl-empty">No cars found. Add cars in Dealership → Add New Car.</div>';
      }
      ?>
    </div>

    <div class="dl-pager">
      <button id="dl-prev" type="button" class="dl-pageBtn" disabled>Prev</button>
      <div class="dl-pageInfo">Page <span id="dl-page">1</span> / <span id="dl-max"><?php echo max(1,(int)$q->max_num_pages); ?></span></div>
      <button id="dl-next" type="button" class="dl-pageBtn" <?php echo ((int)$q->max_num_pages<=1)?'disabled':''; ?>>Next</button>
    </div>
  </section>
  <?php return ob_get_clean();
});

add_shortcode('dl_single', function(){
  $id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
  if(!$id) return '<div class="dl-empty">No car selected.</div>';

  $price   = (float)get_post_meta($id,'_dl_price',true);
  $monthly = dl_monthly($price);

  $gallery_ids = get_post_meta($id,'_dl_gallery',true);
  $gallery = array_filter(array_map('intval', explode(',', (string)$gallery_ids)));

  $images = [];
  if(has_post_thumbnail($id)){
    $src = wp_get_attachment_image_url(get_post_thumbnail_id($id),'large');
    if($src) $images[] = $src;
  }
  foreach($gallery as $gid){
    $src = wp_get_attachment_image_url($gid,'large');
    if($src) $images[] = $src;
  }
  if(empty($images)) $images[] = '';

  ob_start(); ?>
  <section class="dl-shell">
    <div class="dl-detail">
      <div class="dl-detail__left">
        <div class="dl-gallery">
          <div class="dl-gallery__main"><img id="dl-mainImg" src="<?php echo esc_url($images[0]); ?>" alt=""></div>
          <div class="dl-gallery__thumbs">
            <?php foreach($images as $i => $src): if(!$src) continue; ?>
              <button class="dl-thumb <?php echo $i===0?'is-active':''; ?>" type="button" data-src="<?php echo esc_url($src); ?>">
                <img src="<?php echo esc_url($src); ?>" alt="">
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="dl-acc">
          <button class="dl-acc__head" type="button">Description <span>+</span></button>
          <div class="dl-acc__body"><?php echo wp_kses_post(apply_filters('the_content', get_post_field('post_content',$id))); ?></div>
        </div>
      </div>

      <aside class="dl-detail__right">
        <div class="dl-sticky">
          <h2 class="dl-h2"><?php echo esc_html(get_the_title($id)); ?></h2>
          <div class="dl-priceBox">
            <div class="dl-priceBig">R<?php echo number_format($price,0); ?></div>
            <div class="dl-monthly">≈ R<?php echo number_format($monthly,0); ?>/month</div>
          </div>
          <div class="dl-panel">
            <h3 class="dl-h3">Send inquiry</h3>
            <?php echo do_shortcode('[dl_lead_form car_id="'.$id.'"]'); ?>
          </div>
        </div>
      </aside>
    </div>
  </section>
  <?php return ob_get_clean();
});

add_shortcode('dl_featured', function($atts){
  $atts = shortcode_atts(['posts'=>8],$atts);
  $q = new WP_Query([
    'post_type'=>'dl_car',
    'post_status'=>'publish',
    'posts_per_page'=>(int)$atts['posts'],
    'meta_query'=>[['key'=>'_dl_featured','value'=>'yes']],
  ]);

  ob_start();
  echo '<div class="dl-grid">';
  if($q->have_posts()){
    while($q->have_posts()){ $q->the_post(); echo dl_card_html(get_the_ID()); }
    wp_reset_postdata();
  } else {
    echo '<div class="dl-empty">No featured cars yet.</div>';
  }
  echo '</div>';
  return ob_get_clean();
});
