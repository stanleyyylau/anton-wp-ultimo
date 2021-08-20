/* global inlineEditL10n, ajaxurl */
/**
 * This file is used on the term overview page to power quick-editing terms.
 */

window.wp = window.wp || {};

/**
 * Consists of functions relevant to the inline taxonomy editor.
 *
 * @namespace wuExtentionEditor
 *
 * @property {string} type The type of inline edit we are currently on.
 * @property {string} what The type property with a hash prefixed and a dash
 *                         suffixed.
 */
var wuExtentionEditor;

( function( $, wp ) {

wuExtentionEditor = {

  /**
   * @summary Initializes the inline taxonomy editor.
   *
   * Adds event handlers to be able to quick edit.
   *
   * @since 2.7.0
   *
   * @this wuExtentionEditor
   * @memberof wuExtentionEditor
   * @returns {void}
   */
  init : function() {
    var t = this, row = $('#inline-edit');

    t.type = 'plugins';
    t.what = '#'+t.type+'-';

    $('#the-list').on('click', 'a.editinline', function(){
      wuExtentionEditor.edit(this);
      return false;
    });

    /*
     * @summary Cancels inline editing when pressing escape inside the inline editor.
     *
     * @param {Object} e The keyup event that has been triggered.
     */
    row.keyup( function( e ) {
      // 27 = [escape]
      if ( e.which === 27 ) {
        return wuExtentionEditor.revert();
      }
    });

    /**
     * @summary Cancels inline editing when clicking the cancel button.
     */
    $( '.cancel', row ).click( function() {
      return wuExtentionEditor.revert();
    });

    /**
     * @summary Saves the inline edits when clicking the save button.
     */
    $( '.save', row ).click( function() {
      return wuExtentionEditor.save(this);
    });

    /**
     * @summary Saves the inline edits when pressing enter inside the inline editor.
     */
    $( 'input, select', row ).keydown( function( e ) {
      // 13 = [enter]
      if ( e.which === 13 ) {
        return wuExtentionEditor.save( this );
      }
    });

    /**
     * @summary Saves the inline edits on submitting the inline edit form.
     */
    $( '#posts-filter input[type="submit"]' ).mousedown( function() {
      t.revert();
    });

  },

  /**
   * Shows the quick editor
   *
   * @since 2.7.0
   *
   * @this wuExtentionEditor
   * @memberof wuExtentionEditor
   *
   * @param {string|HTMLElement} id The ID of the term we want to quick edit or an
   *                                element within the table row or the
   * table row itself.
   * @returns {boolean} Always returns false.
   */
  edit : function(id) {

    var editRow, rowData, val,
      t = this;
    t.revert();

    // Makes sure we can pass an HTMLElement as the ID.
    if ( typeof(id) === 'object' ) {
      id = t.getId(id);
    }

    t.file = $('tr[data-slug="'+ id +'"]').data('plugin');

    editRow = $('#inline-edit').clone(true);
    rowData = $('#inline_'+id);
    
    $( 'td', editRow ).attr( 'colspan', $( 'th:visible, td:visible', '.wp-list-table.widefat:first thead' ).length );

    $('tr[data-slug="'+ id +'"]').hide().after(editRow).after('<tr class="hidden"></tr>');

    var fields = ['blogname', 'blogdescription', 'wu_categories', 'template_img-preview', 'template_img'];

    $.each(fields, function(index, field_name) {

      value = $('[name="field_' + field_name + '"]', $('tr[data-slug="'+ id +'"]')).val();

      // console.log(value);

      if (field_name === 'template_img-preview') {
        $('.template_img-preview').attr('src', value);
        return true;
      }

      var $target_field = $('[name="' + field_name + '"]', editRow);

      if ($target_field.attr('type') === 'checkbox') {
        if (value) {
          $target_field.click();
        }
      } else {
        $target_field.val(value);
      }

    });

    $(editRow).attr('id', id).addClass('inline-editor').show();
    $('.ptitle', editRow).eq(0).focus();

    return false;
    
  },

  /**
   * @summary Saves the quick edit data.
   *
   * Saves the quick edit data to the server and replaces the table row with the
   * HTML retrieved from the server.
   *
   * @since 2.7.0
   *
   * @this wuExtentionEditor
   * @memberof wuExtentionEditor
   *
   * @param {string|HTMLElement} id The ID of the term we want to quick edit or an
   *                                element within the table row or the
   * table row itself.
   * @returns {boolean} Always returns false.
   */
  save : function(id) {

    var params, fields, tax = $('input[name="taxonomy"]').val() || '';

    // Makes sure we can pass an HTMLElement as the ID.
    if( typeof(id) === 'object' ) {
      id = this.getId(id);
    }

    // console.log(id);

    $( 'table.widefat .spinner' ).addClass( 'is-active' );

    params = {
      action: 'wu_save_site_template',
      // extension_type: this.type,
      extension_id: id,
    };

    fields = $('#'+id).find(':input').serialize();
    params = fields + '&' + $.param(params);

    // Do the ajax request to save the data to the server.
    $.post( ajaxurl, params,
      /**
       * @summary Handles the response from the server.
       *
       * Handles the response from the server, replaces the table row with the response
       * from the server.
       *
       * @param {string} r The string with which to replace the table row.
       */
      function(r) {
        var row, new_id, option_value,
          $errorSpan = $( '#' + id + ' .inline-edit-save .error' );

        $( 'table.widefat .spinner' ).removeClass( 'is-active' );

        if (r) {

          if ( -1 !== r.indexOf( '<tr' ) ) {

            $('tr[data-slug="'+ id +'"]').siblings('tr.hidden').addBack().remove();
            new_id = $(r).attr('data-slug');

            $('#'+id).before(r).remove();

            if ( new_id ) {
              option_value = new_id.replace( wuExtentionEditor.type + '-', '' );
              row = $('tr[data-slug="'+ new_id +'"]');
            } else {
              option_value = id;
              row = $( wuExtentionEditor.what + id );
            }

            // Update the value in the Parent dropdown.
            $( '#parent' ).find( 'option[value=' + option_value + ']' ).text( row.find( '.row-title' ).text() );

            row.hide().fadeIn( 400, function() {
              // Move focus back to the Quick Edit link.
              row.find( '.editinline' ).focus();
              // wp.a11y.speak( inlineEditL10n.saved );
            });

          } else {

            $errorSpan.html( r ).show();
            /*
             * Some error strings may contain HTML entities (e.g. `&#8220`), let's use
             * the HTML element's text.  
             */
            // wp.a11y.speak( $errorSpan.text() );

          }
        } else {
          $errorSpan.html( inlineEditL10n.error ).show();
          // wp.a11y.speak( inlineEditL10n.error );
        }
      }
    );

    // Prevent submitting the form when pressing Enter on a focused field.
    return false;
  },

  /**
   * Closes the quick edit form.
   *
   * @since 2.7.0  
   *
   * @this wuExtentionEditor
   * @memberof wuExtentionEditor
   * @returns {void}
   */
  revert : function() {

    var id = $('table.widefat tr.inline-editor').attr('id');

    if ( id ) {
      $( 'table.widefat .spinner' ).removeClass( 'is-active' );
      $('#'+id).siblings('tr.hidden').addBack().remove();

      // Show the taxonomy row and move focus back to the Quick Edit link.
      $('tr[data-slug="'+ id +'"]').show().find( '.editinline' ).focus();
    }
  },

  /**
   * Retrieves the ID of the term of the element inside the table row.
   *
   * @since 2.7.0 
   *
   * @memberof wuExtentionEditor
   *
   * @param {HTMLElement} o An element within the table row or the table row itself.
   * @returns {string} The ID of the term based on the element.
   */
  getId : function(o) {

    var parent = $(o).parents('tr');

    var id = parent.find('[name="allblogs[]"]').attr('id');

    // CHeck in case of button
    id = id ? id : parent.attr('id');

    id = id ? id : 'blog_1';

    if (id.indexOf('wu_') < 0) {

      id = 'wu_' + id;

    }

    if (!parent.is('.inline-edit-row')) {
      parent.attr('data-slug', id);
    }

    return id;

  }

};

$(document).ready(function(){wuExtentionEditor.init();});

})( jQuery, window.wp );

/**
   * Callback function for the 'click' event of the 'Set Footer Image'
   * anchor in its meta box.
   *
   * Displays the media uploader for selecting an image.
   *
   * @since 0.1.0
   */
function renderMediaUploader($target) {
  'use strict';

  var file_frame, attachment;

  if ( undefined !== file_frame ) {
    file_frame.open();
    return;
  }

  file_frame = wp.media.frames.file_frame = wp.media({
    title: 'Upload Image',//jQuery( this ).data( 'uploader_title' ),
    button: {
      text: 'Select Image' //jQuery( this ).data( 'uploader_button_text' )
    },
    multiple: false  // Set to true to allow multiple files to be selected
  });

  // When an image is selected, run a callback.
  file_frame.on( 'select', function() {
    // We set multiple to false so only get one image from the uploader
    attachment = file_frame.state().get('selection').first().toJSON();

    jQuery('.' + $target + '-preview').attr('src', attachment.url);
    jQuery('.' + $target).attr('value', attachment.id);
    // console.log(jQuery('#' + $target));
    // Do something with attachment.id and/or attachment.url here
  });
  
  // Now display the actual file_frame
  file_frame.open();

}

jQuery('.wu-field-button-upload').on( 'click', function(e) {
  e.preventDefault();
  var $trigger = jQuery(this).data('target');
  renderMediaUploader($trigger);
});

jQuery('.wu-field-button-upload-remove').on( 'click', function(e) {
  e.preventDefault();
  var $target = jQuery(this).data('target');
  jQuery('.' + $target + '-preview').removeAttr('src');
  jQuery('.' + $target).attr('value', '');
});