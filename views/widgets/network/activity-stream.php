<div v-if='loading' class='wu-feed-loading'>
  <span class="dashicons-before dashicons-wpultimo wu-spin" style="display: inline-block"></span>
</div>

<div v-if='!data.length && !loading' class='wu-feed-loading' v-cloak><?php _e("No more items to display", 'wp-ultimo'); ?></div>

<ul v-cloak v-if='!loading' class='wu-feed-block'>
    
  <li class="wu-feed-container" v-for="item in data" v-bind:class="item.type">

    <a v-bind:href="'<?php echo network_admin_url('admin.php?page=wu-edit-subscription&user_id='); ?>' + item.user.ID" target="_blank" title="Teste" class="wu-feed-logo wu-tooltip" v-html="item.user.avatar"></a>
    <div class="wu-feed-type" v-html="item.type_icon"></div>

    <div class="wu-feed-company-name" v-html="item.description"></div>
    
    <!-- <div class='wu-feed-content rssSummary'>{{item.description}}</div> -->

    <ul class="wu-feed-footer">
      <li><strong>{{item.type_label}}</strong></li>
      <!-- <li v-if="item.reference_id">{{item.reference_id}}</li> -->
      <li v-if="item.amount && item.amount != '--'"><?php _e('Amount', 'wp-ultimo'); ?>:  <span v-html="item.amount"></span></li>
      <li>{{item.from_now}}</li>
      <li class="wu-feed-see-more">
        <a v-bind:href="'<?php echo network_admin_url('admin.php?page=wu-edit-subscription&user_id='); ?>' + item.user.ID" target="_blank">{{item.user.display_name}}</a>
      </li>
    </ul>

  </li>

</ul>

<ul v-if='!loading' class='wu-feed-pagination' v-cloak>
  <li v-on:click="refresh"><a href="#"><?php _e('Refresh', 'wp-ultimo'); ?></a></li>
  <li v-if="page > 1" v-on:click="navigatePrev"><a href="#">&larr; <?php _e('Previous Page', 'wp-ultimo'); ?></a></li>
  <li><a v-if="data.length && !loading" href="#" v-on:click="navigateNext"><?php _e('Next Page', 'wp-ultimo'); ?> &rarr;</a></li>
</ul>