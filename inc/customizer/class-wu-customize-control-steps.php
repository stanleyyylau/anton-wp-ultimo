<?php
/**
 * Steps and Fields Customize Control
 *
 * Adds a new repeater controller to the customizer that handles WP Ultimo sign-up steps and fields
 *
 * Want to add custom steps to the Sign-up? Read this:
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Signup
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

if (class_exists('WP_Customize_Control')) {

  class Customize_Control_Steps_Field extends WP_Customize_Control {
    /**
     * The type of customize control being rendered.
     *
     * @access public
     * @var    string
     */
    public $type = 'steps_field';

    public $button_message = '';
    public $field_name = '';

    /**
     * Enqueue scripts/styles.
     *
     * @access public
     * @return void
     */
    public function enqueue() {

      $suffix = WP_Ultimo()->min;

      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-sortable');
      
      wp_enqueue_script('wu-customizer-steps-control', WP_Ultimo()->get_asset("wu-customizer-steps-control$suffix.js", 'js'), array('jquery', 'jquery-ui-sortable'), WP_Ultimo()->version, true);

    }

    /**
     * Add custom parameters to pass to the JS via JSON.
     *
     * @access public
     * @return void
     */
    public function to_json() {
      
      parent::to_json();

      $this->json['value']   = !is_array( $this->value() ) ? explode( ',', $this->value() ) : $this->value();
      $this->json['choices'] = $this->choices;
      $this->json['link']    = $this->get_link();
      $this->json['id']      = $this->id;

      $this->json['button_message'] = $this->button_message;
      $this->json['field_name'] = 'wu_sortable';

    }

  public function print_extra_template($id = 'wu-item-template') { ?>

      <?php $display = $id == 'wu-item-template' ? 'display: none;' : ''; ?>

      <li id="widget-{{step_id}}-list-item" class="customize-control customize-control-widget_form widget-rendered" style="<?php echo $display; ?>">
        <div id="widget-{{step_id}}" class="widget widget-wu-step">
            <div class="widget-top">
                <div class="widget-title-action">
                    <button type="button" class="widget-action hide-if-no-js" aria-expanded="false">
                <span class="screen-reader-text">{{step.name}}</span>
                <span class="toggle-indicator" aria-hidden="true"></span>
              </button>
                    <a class="widget-control-edit hide-if-js" href="/wp-admin/customize.php?editwidget=search-2&amp;key=-1">
                        <span class="edit">Edit</span>
                        <span class="add">Add</span>
                        <span class="screen-reader-text">{{step.name}}</span>
                    </a>
                </div>
                <div class="widget-reorder-nav"><span class="move-widget" tabindex="0">Search: Move to another area…</span><span class="move-widget-down" tabindex="0">Search: Move down</span><span class="move-widget-up" tabindex="0">Search: Move up</span></div>
                <div class="widget-title">
                    <h3>{{step.name}}<span class="in-widget-title"></span></h3>
                </div>
            </div>

            <div class="widget-inside" style="display: none;">
                <div class="form">

                    <div class="widget-content">

                        <p><label for="{{data.field_name}}-{{{ step_id }}}"><?php _e('Step ID', 'wp-ultimo'); ?>: 
                        <input <# if (step.core) { #>disabled="disabled"<# } #> class="widefat" id="{{data.field_name}}-{{step_id}}" name="{{data.field_name}}[{{step_id}}][id]" type="text" value="{{step_id}}" placeholder="{{step_id}}"></label></p>

                        <p><label for="{{data.field_name}}-{{{ step.description }}}"><?php _e('Name', 'wp-ultimo'); ?>: <input class="widefat" id="{{data.field_name}}-{{step_id}}" name="{{data.field_name}}[{{step_id}}][name]" type="text" value="{{step.name}}" placeholder="{{step.old_values.name}}"></label></p>

                    </div>

                    <!-- <div class="widget-content">
                        
                      <button class="button button-primary">Edit Fields</button>

                    </div> -->

                    <!-- .widget-content -->
                    <input type="hidden" name="{{data.field_name}}[{{step_id}}][order]" class="step-order" value="{{step.old_values.order}}">

                    <div class="widget-control-actions">
                        <!-- <div class="alignleft">
                              <button type="button" class="button-link button-link-delete widget-control-remove" title="Trash widget by moving it to the inactive widgets sidebar.">Remove</button> |
                              <button type="button" class="button-link widget-control-close">Close</button>
                          </div> -->
                        <div class="alignright">
                            <input type="submit" name="savewidget" id="widget-search-2-savewidget" class="button widget-control-save right" value="Close" title=""> <span class="spinner"></span>
                        </div>
                        <br class="clear">
                    </div>
                </div>
                <!-- .form -->
            </div>

            <div class="widget-description">
                A search form for your site.
            </div>
        </div>
      </li>

    <?php }

    /**
     * Underscore JS template to handle the control's output.
     *
     * @access public
     * @return void
     */
    public function content_template() { ?>

      <# if ( ! data.choices ) {
        return;
      } #>

      <# if ( data.label ) { #>
        <!-- <span class="customize-control-title">{{ data.label }}</span> -->
      <# } #>

      <# if ( data.description ) { #>
        <span class="description customize-control-description">{{{ data.description }}}</span>
      <# } #>

      <ul>
        <# _.each( data.choices, function( step, step_id ) { //console.log(step); #>
          
          <?php echo $this->print_extra_template('step-{{step_id}}-list-item'); ?>

        <# } ) #>
      </ul>

      <?php // echo $this->print_extra_template(); ?>
        
      <div class="customize-control-notifications-container" style="">
        <ul>
        
        </ul>
      </div>
        
      <button disabled="disabled" id="add-new-step" type="button" class="button add-new-widget wu-tooltip" title="<?php _e('This is not ready just yet! Stay tuned!', 'wp-ultimo'); ?>" aria-expanded="true" aria-controls="available-widgets">{{data.button_message}}</button>
    
      <!-- <button type="button" class="button-link reorder-toggle" aria-label="Reorder widgets" aria-describedby="reorder-widgets-desc-sidebars_widgets-sidebar-1">
      
        <span class="reorder">Reorder</span>
        <span class="reorder-done">Done</span>
      </button> -->
      
      </li>
          
    </ul>

    <?php }

  } // end class

} // end if;
