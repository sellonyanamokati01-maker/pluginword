jQuery(function($){
  const $grid = $('#dl-grid');
  const $skel = $('#dl-skeleton');

  // If the page doesn't have showroom markup, do nothing.
  if (!$grid.length) return;

  let state = {
    make: '',
    search: '',
    order: '',
    page: 1,
    per: 12,
    max: parseInt($('#dl-max').text() || '1', 10)
  };

  function reveal(){
    const els = document.querySelectorAll('.dl-reveal');
    if (!('IntersectionObserver' in window)) {
      els.forEach(el => el.classList.add('is-in'));
      return;
    }
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(e=>{
        if(e.isIntersecting){ e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    els.forEach(el=>io.observe(el));
  }

  function setCounts(found){
    $('#dl-found').text(found);
    $('#dl-found-desktop').text(found);
  }

  function setPager(){
    $('#dl-page').text(state.page);
    $('#dl-max').text(state.max);
    $('#dl-prev').prop('disabled', state.page <= 1);
    $('#dl-next').prop('disabled', state.page >= state.max);
  }

  function loading(on){
    if(on){ $skel.show(); $grid.hide(); }
    else { $skel.hide(); $grid.show(); }
  }

  function loadAjax(){
    loading(true);

    $.post(dl_ajax.ajax_url, {
      action: 'dl_showroom',
      nonce: dl_ajax.nonce,
      make: state.make,
      search: state.search,
      order: state.order,
      page: state.page,
      per: state.per
    }).done(function(res){
      if(!res || !res.success) return;

      $grid.html(res.data.html);
      setCounts(res.data.found);

      state.max = res.data.max_pages || 1;
      setPager();

      reveal();
      window.scrollTo({top:0,behavior:'smooth'});
    }).always(function(){
      loading(false);
    });
  }

  // SSR cards reveal immediately
  reveal();
  setCounts(parseInt($('#dl-found').text() || '0', 10));
  setPager();

  // Desktop controls
  $('#dl-make').on('change', function(){ state.make=$(this).val(); state.page=1; loadAjax(); });
  $('#dl-sort').on('change', function(){ state.order=$(this).val(); state.page=1; loadAjax(); });

  $('#dl-searchBtn').on('click', function(){
    state.search = $('#dl-search').val().trim();
    state.page=1;
    loadAjax();
  });

  $('#dl-search').on('keydown', function(e){
    if(e.key==='Enter'){ e.preventDefault(); $('#dl-searchBtn').click(); }
  });

  $('#dl-clear').on('click', function(){
    state = {...state, make:'', search:'', order:'', page:1};
    $('#dl-make').val('');
    $('#dl-sort').val('');
    $('#dl-search').val('');
    loadAjax();
  });

  // Pager
  $('#dl-prev').on('click', function(){ if(state.page>1){ state.page--; loadAjax(); }});
  $('#dl-next').on('click', function(){ if(state.page<state.max){ state.page++; loadAjax(); }});

  // Drawer
  const $drawer = $('#dl-drawer');
  function openDrawer(){ $drawer.addClass('is-open').attr('aria-hidden','false'); }
  function closeDrawer(){ $drawer.removeClass('is-open').attr('aria-hidden','true'); }

  $('#dl-openFilters').on('click', openDrawer);
  $('#dl-closeFilters').on('click', closeDrawer);
  $drawer.on('click', function(e){ if(e.target === this) closeDrawer(); });

  $('#dl-apply').on('click', function(){
    state.make  = $('#dl-make-mobile').val();
    state.order = $('#dl-sort-mobile').val();
    state.page  = 1;

    $('#dl-make').val(state.make);
    $('#dl-sort').val(state.order);

    closeDrawer();
    loadAjax();
  });

  $('#dl-clear-mobile').on('click', function(){
    $('#dl-make-mobile').val('');
    $('#dl-sort-mobile').val('');
    state = {...state, make:'', order:'', page:1};
    $('#dl-make').val('');
    $('#dl-sort').val('');
    closeDrawer();
    loadAjax();
  });
});
