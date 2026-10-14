jQuery(function ($) {
  function openFrame(opts) {
    if (!window.wp || !wp.media) {
      console.error('[Dealership] wp.media missing. wp_enqueue_media not loaded.');
      return;
    }

    const frame = wp.media({
      title: opts.title,
      button: { text: opts.buttonText },
      multiple: opts.multiple,
      library: { type: 'image' },
    });

    frame.on('select', function () {
      const selection = frame.state().get('selection');
      if (!selection) return;

      const items = opts.multiple ? selection.toJSON() : [selection.first().toJSON()];
      opts.onSelect(items);
    });

    frame.open();
  }

  $(document).on('click', '#dl_pick_featured', function (e) {
    e.preventDefault();
    openFrame({
      multiple: false,
      title: 'Choose Featured Image',
      buttonText: 'Use this image',
      onSelect: function (items) {
        const img = items[0];
        $('#featured_image_id').val(img.id);
        $('#dl_featured_preview').html(
          '<img src="' + img.url + '" style="max-width:220px;border-radius:12px;border:1px solid #ddd;">'
        );
      },
    });
  });

  $(document).on('click', '#dl_pick_gallery', function (e) {
    e.preventDefault();
    openFrame({
      multiple: true,
      title: 'Choose Gallery Images',
      buttonText: 'Use selected images',
      onSelect: function (items) {
        $('#gallery_image_ids').val(items.map((i) => i.id).join(','));

        const thumbs = items
          .map((i) => {
            const t = i.sizes && i.sizes.thumbnail ? i.sizes.thumbnail.url : i.url;
            return '<img src="' + t + '" style="width:80px;height:80px;object-fit:cover;border-radius:12px;border:1px solid #ddd;">';
          })
          .join('');

        $('#dl_gallery_preview').html(thumbs);
      },
    });
  });
});
