var voicewp_media_frame = [];
( function( $ ) {

  $( document ).on( 'click', '.voicewp-media-remove', function(e) {
    e.preventDefault();
    $(this).parents( '.voicewp-wrapper' ).find( '.voicewp-media-id' ).val( 0 );
    $(this).parents( '.voicewp-wrapper' ).find( '.media-wrapper' ).html( '' );
  });

  $( document ).on( 'click', '.media-wrapper a', function( event ){
    event.preventDefault();
    $(this).closest('.media-wrapper').siblings('.voicewp-media-button').click();
  } );

  $( document ).on( 'click', '.voicewp-media-button', function( event ) {
    var $el = $(this),
      library = {};
    event.preventDefault();

    // If the media frame already exists, reopen it.
    if ( voicewp_media_frame[ $el.attr('id') ] ) {
      voicewp_media_frame[ $el.attr('id') ].open();
      return;
    }

    // Create the media frame.
    voicewp_media_frame[ $el.attr('id') ] = wp.media({
      library: library,
    });

    // When an image is selected, run a callback.
    voicewp_media_frame[ $el.attr('id') ].on( 'select', function() {
      // Grab the selected attachment.
      var attachment = voicewp_media_frame[ $el.attr('id') ].state().get('selection').first().attributes;

      props = { size: $el.data('preview-size') || 'thumbnail' };
      props = wp.media.string.props( props, attachment );
      props.align = 'none';
      props.link = 'custom';
      props.linkUrl = '#';
      props.caption = '';
      $el.parent().find('.voicewp-media-id').val( attachment.id );

      if ( attachment.type == 'image' ) {
        props.url = props.src;
        var preview = 'Uploaded file:<br />';
        preview += wp.media.string.image( props );
      } else {
        var preview = 'Uploaded file:&nbsp;';
        preview += wp.media.string.link( props );
      }

      preview += '<br /><a class="voicewp-media-remove voicewp-delete" href="#">remove</a>';
      var $wrapper = $el.parent().find( '.media-wrapper' );
      $wrapper.html( preview );
    });

    // Select the attachment when the frame opens
    voicewp_media_frame[ $el.attr('id') ].on( 'open', function() {
      // Select the current attachment inside the frame
      var selection = voicewp_media_frame[ $el.attr('id') ].state().get('selection'),
        id = $el.parent().find('.voicewp-media-id').val(),
        attachment;

      // If there is a saved attachment, use it
      if ( '' !== id && -1 !== id && typeof wp.media.attachment !== "undefined" ) {
        attachment = wp.media.attachment( id );
        attachment.fetch();
      }

      selection.reset( attachment ? [ attachment ] : [] );
    } );

    voicewp_media_frame[ $el.attr('id') ].open();
  } );

} )( jQuery );
