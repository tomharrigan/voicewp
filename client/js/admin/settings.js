voicewp_add_another = function( $element ) {
  const repeaterGroup = $element.parent().parent();
  const cloneElement = repeaterGroup.find( '.voicewp-group-wrapper' ).last().clone();
  const items = repeaterGroup.find('.voicewp-group-wrapper');

  cloneElement.insertBefore( $element.parent() );

  // Clear data.
  const fields = cloneElement.find('.voicewp-item');
  fields.each( function() {
    jQuery( this ).val( '' );
    jQuery( this ).siblings( '.media-wrapper' ).html( '' );
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

  // Initializes triggers to conditionally hide or show fields
  voicewp_init_display_if = function() {
    var val;
    var src = $( this ).data( 'display-src' );
    var values = voicewpGetCompareValues( this );
    var wrapper = $( this ).closest('.voicewp-wrapper');
    // Wrapper divs sometimes receive .voicewp-element, but don't use them as
    // triggers. Also don't use autocomplete inputs or a checkbox's hidden
    // sibling as triggers, because the value is in their sibling fields
    // (which this still matches).
    var trigger = wrapper.siblings( '.voicewp-' + src + '-wrapper' ).find( '.voicewp-element' ).not( 'div, .voicewp-autocomplete, .voicewp-checkbox-hidden' );

    // Sanity check before calling `val()` or `split()`.
    if ( 0 === trigger.length ) {
      return;
    }

    if ( trigger.is( ':checkbox' ) ) {
      if ( trigger.is( ':checked' ) ) {
        val = true;
      } else {
        val = false;
      }
    } else if ( trigger.is( ':radio' ) ) {
      if ( trigger.filter( ':checked' ).length ) {
        val = trigger.filter( ':checked' ).val();
      } else {
        // On load, there might not be any selected radio, in which case call the value blank.
        val = '';
      }
    } else {
      val = trigger.val().split( ',' );
    }
    trigger.addClass( 'display-trigger' );
    if ( ! voicewp_match_value( values, val ) ) {
      wrapper.hide();
    }
  };
  $( '.voicewp-display-if' ).each( voicewp_init_display_if );

  // Controls the trigger to show or hide fields
  voicewp_trigger_display_if = function() {
    var val;
    var $this = $( this );
    var name = $this.attr( 'name' );
    if ( $this.is( ':checkbox' ) ) {
      if ( $this.is( ':checked' ) ) {
        val = true;
      } else {
        val = false;
      }
    } else if ( $this.is( ':radio' ) ) {
      val = $this.filter( ':checked' ).val();
    } else {
      val = $this.val().split( ',' );
    }

    $( this ).closest( '.voicewp-wrapper' ).siblings().each( function() {
      var element = $( this ).find('.voicewp-element');

      if ( element.hasClass( 'voicewp-display-if' ) ) {
        if ( name && name.match( element.data( 'display-src' ) ) != null ) {
          if ( voicewp_match_value( voicewpGetCompareValues( element ), val ) ) {
            $( this ).show();
          } else {
            $( this ).hide();
          }
        }
      }
    } );
  };
  $( document ).on( 'change', '.display-trigger', voicewp_trigger_display_if );
} );

/**
 * Get data attribute display-value(s).
 *
 * Accounts for jQuery converting string to number automatically.
 *
 * @param HTMLDivElement el Wrapper with the data attribute.
 * @return string|number|array Single string or number, or array if data attr contains CSV.
 */
var voicewpGetCompareValues = function( el ) {
  var values = jQuery( el ).data( 'display-value' );
  try {
    values = values.split( ',' );
  } catch( e ) {
    // If jQuery already converted string to number.
    values = [ values ];
  }
  return values;
};

/**
 * Matches the display if value.
 *
 * @param  {array}  values       The list of values to match.
 * @param  {string} match_string The match string.
 * @return {bool}                True of false.
 */
var voicewp_match_value = function( values, match_string ) {
  for ( var index in values ) {
    if ( values[index] == match_string ) {
      return true;
    }
  }
  return false;
}
