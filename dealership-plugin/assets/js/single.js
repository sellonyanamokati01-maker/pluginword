jQuery(function($){
  $(document).on('click', '.dl-thumb', function(){
    const src = $(this).data('src');
    $('#dl-mainImg').attr('src', src);
    $('.dl-thumb').removeClass('is-active');
    $(this).addClass('is-active');
  });

  $(document).on('click', '.dl-acc__head', function(){
    const $body = $(this).next('.dl-acc__body');
    const open = $body.hasClass('is-open');
    $('.dl-acc__body').removeClass('is-open');
    if(!open) $body.addClass('is-open');
  });
});
