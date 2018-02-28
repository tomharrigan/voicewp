voicewp_add_another = function( $element ) {
  const repeaterGroup = $element.parent().parent();
  const cloneElement = repeaterGroup.find( '.voicewp-wrapper' ).last().clone();

  cloneElement.insertBefore( $element.parent() );
}

voicewp_remove = function( $element ) {
  $wrapper = $element.parents( '.voicewp-wrapper' ).first();
  $element.parents( '.voicewp-item' ).first().remove();
  voicewp_renumber( $wrapper );
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

  // Special handling for Option pages.
  if ( 0 !== $( 'form[action="options.php"]' ).length ) {

    // Move table into repeating group.
    $( '.voicewpjs-options-repeating-group' ).each( function () {
      // $( this ).next( 'table.form-table' ).prependTo( $( this ).find( '.voicewp-wrapper' ) );
    } );
  }
} );
