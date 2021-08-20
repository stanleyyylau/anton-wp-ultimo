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

    jQuery('#' + $target + '-preview').attr('src', attachment.url).show();
    jQuery('#' + $target).attr('value', attachment.id);
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
  var $default = jQuery(this).data('default');
  jQuery('#' + $target + '-preview').attr('src', '#').hide();
  jQuery('#' + $target).attr('value', '');
});

jQuery('.wu-field-button-upload-set-default').on( 'click', function(e) {
  e.preventDefault();
  var $target = jQuery(this).data('target');
  var $value = jQuery(this).data('value');
  jQuery('#' + $target + '-preview').attr('src', $value).show();
  jQuery('#' + $target).attr('value', $value);
});