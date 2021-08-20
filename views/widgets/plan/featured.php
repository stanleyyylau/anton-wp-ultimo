<p>
  <label for="top_deal">
    <input type="checkbox" <?php checked($plan->top_deal); ?> name="top_deal" id="top_deal">
    <?php _e('Featured Plan', 'wp-ultimo'); ?>
  </label>
</p>

<p class="description">
  <?php _e('Check this box to mark this plan as the "Featured Plan" on the plan pricing table.', 'wp-ultimo'); ?>
</p>