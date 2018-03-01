voicewp_add_another = function( $element ) {
  const repeaterGroup = $element.parent().parent();
  const cloneElement = repeaterGroup.find( '.voicewp-group-wrapper' ).last().clone();
  const items = repeaterGroup.find('.voicewp-group-wrapper');

  cloneElement.insertBefore( $element.parent() );

  // Clear data.
  const fields = cloneElement.find('.voicewp-item');
  fields.each( function() {
    jQuery( this ).val( '' );
  });

  voicewp_renumber( $element.closest( '.voicewpjs-repeating-group' ) );
}

voicewp_remove = function( $element ) {
  const removedEl = $element.parent().parent();
  const wrapper = removedEl.closest( '.voicewpjs-repeating-group' );
  removedEl.remove();
  voicewp_renumber( wrapper );
}

voicewp_renumber = function( $element ) {
  const repeaterName = $element.data( 'repeater-name' );
  const items = $element.find( '.voicewp-group-wrapper' );

  // Update name and clear data.
  items.each( function( index, value ) {
    const fields = jQuery( this ).find('.voicewp-item');

    fields.each( function() {
      let groupName =
        repeaterName.replace( 'voicewp-index', Math.max( 0, index ) )
        + '[' + jQuery( this ).data( 'base-name' ) + ']';

      jQuery( this ).attr( 'name', groupName );
      jQuery( this ).attr( 'id', groupName );
    });
  });
}

jQuery( document ).ready( function ( $ ) {
  // Handle adding another element.
  $( document ).on( 'click', '.voicewp-add-another', function( e ) {
    e.preventDefault();
    voicewp_add_another( $( this ) );
  } );

  // Handle remove events
  $( document ).on( 'click', '.voicewpjs-remove', function( e ) {
    e.preventDefault();
    voicewp_remove( $( this ) );
  } );
} );
