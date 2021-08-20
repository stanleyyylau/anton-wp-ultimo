<?php
$mrr = WU_Page_Statistics::get_stat('mrr', false); ?>
<ul class="wu_status_list">
  <li class="mrr streched">
    <a href="<?php echo network_admin_url('admin.php?page=wp-ultimo-stats'); ?>">
      
      <span class="wu_sparkline lines" data-mode="categories" data-color="#777" data-sparkline="<?php echo json_encode($mrr['pairs']); ?>" style="padding: 0px;">
      </span>
      
      <strong><span class="amount"><?php echo wu_format_currency($mrr['total_value']); ?></span></strong> <?php _e('monthly recurring revenue (MRR)', 'wp-ultimo'); ?>				
    </a>
  </li>
  
  <?php
  /**
   * Users registered today
   */
  $registers_today = WU_Util::registers_today();
  ?>
  <li class="users-today">
    <div>
    <strong><?php printf(__('%s users', 'wp-ultimo'), $registers_today); ?></strong> <?php _e('registered today', 'wp-ultimo'); ?></div>
  </li>
  
  <?php
  /**
   * Most Popular Plan
   */
  $most_popular_plan = WU_Plans::get_most_popular_plan();
  $url = $most_popular_plan 
    ? network_admin_url('admin.php?page=wu-edit-plan&plan_id=').$most_popular_plan->id
    : network_admin_url('admin.php?page=wp-ultimo-plans');
  ?>
  <li class="most-popular-plan">
    <a href="<?php echo $url ?>">
    <strong><?php echo $most_popular_plan ? $most_popular_plan->title : '--'; ?></strong> <?php _e('most popular plan', 'wp-ultimo'); ?></a>
  </li>
  
</ul>