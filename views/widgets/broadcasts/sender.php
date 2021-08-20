<form name="post" action="" method="post" id="wu-broadcast-form">

  <ul class="wu-message-type-tabs">
    <li v-bind:class="type == 'message' ? 'active' : ''">
      <a v-on:click="setType($event, 'message')" href=""><?php _e('Add a Message', 'wp-ultimo'); ?></a>
    </li>
    <li v-bind:class="type == 'email' ? 'active' : ''">
      <a v-on:click="setType($event, 'email')" href=""><?php _e('Send an Email', 'wp-ultimo'); ?></a>
    </li>
  </ul>

  <div class="row">

    <div class="input-text-wrap wu-col-sm-12">
      <label for="wu-broadcast-users"><?php _e('Target Users', 'wp-ultimo'); ?></label>
      <input type="text" name="target_users" id="wu-broadcast-users" class="regular-text" placeholder="<?php _e('Select the Target Users', 'wp-ultimo'); ?>">
    </div>

    <div class="input-text-wrap wu-col-sm-12">
      <label for="wu-broadcast-plans"><?php _e('Target Plans', 'wp-ultimo'); ?></label>

      <select id="wu-broadcast-plans" name="target_plans[]" multiple="multiple">
        <?php foreach(WU_Plans::get_plans() as $plan) :
        echo "<option value='$plan->id'>". $plan->title ." (". $plan->get_subscription_count() ." ". __('users', 'wp-ultimo') .")</option>"; 
        endforeach; ?>
      </select>
    </div>

  </div>

  <div class="row">

    <div class="input-text-wrap wu-col-sm-12" v-show="false">
      <label for="type"><?php _e('Message Type', 'wp-ultimo'); ?></label>
      <select id="type" name="type" class="regular-text" v-model="type">
          <option value=""><?php _e('Message Type', 'wp-ultimo'); ?></option>
          <option value="message"><?php _e('Message', 'wp-ultimo'); ?></option>
          <option value="email"><?php _e('Email', 'wp-ultimo'); ?></option>
      </select>
    </div>

    <div v-if="type == 'message'" class="input-text-wrap wu-col-sm-12">
      <label for="style"><?php _e('Message Style', 'wp-ultimo'); ?></label>
      <select id="style" name="style" class="regular-text" v-model="style">
          <option value=""><?php _e('Message Style', 'wp-ultimo'); ?></option>
          <option value="success"><?php _e('Success (green border)', 'wp-ultimo'); ?></option>
          <option value="warning"><?php _e('Warning (yellow border)', 'wp-ultimo'); ?></option>
          <option value="error"><?php _e('Error (red border)', 'wp-ultimo'); ?></option>
      </select>
    </div>

  </div>

  <div class="input-text-wrap" id="broadcast-subject-line">
    <label for="title"><?php _e('Message Subject', 'wp-ultimo'); ?></label>
    <input type="text" v-model="subject" name="post_title" id="title" class="regular-text" placeholder="<?php _e('Enter a Subject / Title (optional)', 'wp-ultimo'); ?>">
  </div>

  <div class="textarea-wrap" id="description-wrap">
    
    <!-- <label for="content"><?php _e('Message', 'wp-ultimo'); ?></label> -->
    
    <?php 
    
    wp_editor(__('Write a message...', 'wp-ultimo'), 'post_content', array(
      'wpautop'        => false,
      'media_buttons'  => false,
      'editor_height'  => 200,
      'teeny'          => true,
      'default_editor' => 'tinymce',
      'textarea_name'  => 'post_content',
      'tinymce'        => array("toolbar1" => 'bold,italic,strikethrough,link,unlink,undo,redo,pastetext'),
    )); 

    ?>

    <!-- <textarea class="regular-text" id="content" name="post_content" rows="3" placeholder="<?php _e('Your message. Simple HTML supported.', 'wp-ultimo'); ?>"></textarea> -->
  </div>

  <p class="wu-broadcast-submit-block" class="submit">
    
    <?php wp_nonce_field('wu_save_broadcast'); ?>
    
    <input type="hidden" name="action" value="wu_broadcast_message">

    <button v-if="type == 'email'" style="float: left;" class="button" id="preview-message" name="preview">
        <?php _e('Preview Email', 'wp-ultimo'); ?>
    </button>
    
    <button type="submit" class="button button-primary" id="save-message" name="submit">
        <?php _e('Send Message', 'wp-ultimo'); ?>
    
    </button>
    
    <br class="clear">
  </p>

</form>