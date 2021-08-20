<ul class="wu_status_list">
  <li class="mrr streched">
    <div>
      <strong>
        <span class="amount"><?php echo wu_format_currency($mrr['total_value']); ?></span>
        <span class="growth wu-tooltip growth-<?php echo $mrr['growth_sign']; ?>" title="<?php _e('Growth relative to past month', 'wp-ultimo'); ?>"><?php printf('%s %s', number_format($mrr['growth'], 2), '%'); ?></span>
      </strong> <?php _e('monthly recurring revenue (MRR)', 'wp-ultimo'); ?> 
    </div>
  </li>
</ul>

<div id="wu-users-graph" class="wu-inside-graphs">

  <span class="wu_graph" data-color="green" data-sparkline='<?php echo json_encode($mrr['pairs']); ?>' style="padding: 0px;">
  </span>

</div>