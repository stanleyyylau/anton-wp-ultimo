<?php

/**
 * Load menus
 */
$menu_groups = $module->get_link_list(); 

?>
<div id="bd-search-and-go" style="display: none;">
  
  <select id="bd-search-and-go-select" data-placeholder="<?php _e('Type and press Enter', 'wp-ultimo'); ?>">
    
    <?php if (count($menu_groups)) : ?>
      <option></option>
    <?php else: ?>
      <option></option>
      <optgroup label="<?php _e('Error', 'wp-ultimo'); ?>">
        <option value="<?php echo network_admin_url('?wu-rebuild-jumper=1'); ?>"><?php _e('Click to rebuild menu list', 'wp-ultimo'); ?></option>
      </optgroup>
    <?php endif; ?>
    
    <?php foreach($menu_groups as $optgroup => $menus) : ?>

      <optgroup label="<?php echo $optgroup; ?>">
        
        <?php foreach($menus as $url => $menu) : ?>
          <option value="<?php echo $url ?>"><?php echo $menu; ?></option>
        <?php endforeach; ?>
        
      </optgroup>

    <?php endforeach; ?>
    
  </select>

  <div class="bd-search-and-go-loading">
    <?php _e('Redirecting you to the target page...', 'wp-ultimo'); ?>
  </div>
  
</div>