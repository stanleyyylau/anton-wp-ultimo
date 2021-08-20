<ul class="wu_status_list">
  <li class="users-today streched">
    <div>
      <strong><span class="amount"><?php echo $users['total_value']; ?></span>
      <span class="growth wu-tooltip growth-<?php echo $users['growth_sign']; ?>" title="<?php _e('Growth relative to past month', 'wp-ultimo'); ?>"><?php printf('%s %s', number_format($users['growth'], 2), '%'); ?></span>
      </strong> <?php _e('users', 'wp-ultimo'); ?> 
    </div>
  </li>
</ul>

<div id="wu-users-graph" class="wu-inside-graphs">

  <span class="wu_graph" data-color="blue" data-sparkline='<?php echo json_encode($users['pairs']); ?>' style="padding: 0px;">
  </span>
  
</div>