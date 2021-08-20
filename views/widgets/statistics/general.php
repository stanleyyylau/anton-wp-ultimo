<ul class="wu_status_list">

  <li class="total-revenue streched">
    <div>
    <strong><?php echo wu_format_currency($total_revenue); ?></strong> <?php _e('total revenue so far', 'wp-ultimo'); ?></div>
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