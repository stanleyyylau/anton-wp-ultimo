<div id="wpultimo-header">
  <div class="row">
    
    <div class="wu-col-sm-6 text-left">
      <strong><?php _e('WP Ultimo', 'wp-ultimo'); ?></strong> 
      <small><?php printf(__('Version %s', 'wp-ultimo'), WP_Ultimo()->version); ?></small>
    </div>
    
    <div class="wu-col-sm-6 text-right">

      <small style="margin-right: 20px" class="wu-ticker-container">
        <strong><?php _e('Server Time:', 'wp-ultimo'); ?></strong> 
        <span id="wu-ticker"><?php echo date('Y-m-d H:i:s', current_time( 'timestamp' )); ?></span>
      </small>

      <span class="dashicons-before dashicons-wpultimo"></span>

    </div>
    
  </div>
</div>