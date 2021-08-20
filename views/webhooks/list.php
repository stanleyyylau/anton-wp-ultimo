<div id="wp-ultimo-wrap" class="wrap">
  
  <h1>
    <?php _e('Webhooks', 'wp-ultimo'); ?>
    <a href="#" class="page-title-action" v-on:click="addNew"><?php _e('Add new Webhook', 'wp-ultimo'); ?></a>
    <a href="<?php echo network_admin_url('admin.php?page=wp-ultimo&wu-tab=tools'); ?>" class="page-title-action"><?php _e('Webhook Settings', 'wp-ultimo'); ?></a>
  </h1>

  <p><?php _e('Webhooks are a easy way to connect your WP Ultimo network to the outside world. Every major event inside WP Ultimo (new subscriptions, signups, cancelations, upgrades, etc) fire webhook calls that can communicate that information to third-party applications like a CRM or services like Zapier, for example.', 'wp-ultimo'); ?></p>

  <ul v-cloak class="subsubsub" id="wu-webhooks-filter" style="">
    <li v-for="(filter_name, filter_slug) in integrations">
      <a href="#" v-on:click="filterWebhooks(filter_slug, $event)" v-bind:class="filter == filter_slug ? 'current' : ''">{{filter_name}}</a>
    </li>
  </ul>

  <div id="poststuff">
      <div id="post-body" class="">
          <div id="post-body-content">
              
                <table v-bind:style="!is_enabled ? 'opacity: 0.8' : ''" class="wp-list-table widefat fixed striped webhooks">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1"><?php _e('Select All', 'wp-ultimo'); ?></label>
                                <input id="cb-select-all-1-2" type="checkbox" v-model="selectedAll" @click="selectAll">
                            </td>
                            <th scope="col" id="name" class="manage-column column-title column-primary"><?php _e('Name', 'wp-ultimo'); ?></th>
                            <th scope="col" id="url" class="manage-column column-url"><?php _e('URL', 'wp-ultimo'); ?></th>
                            <th scope="col" id="event" class="manage-column column-event"><?php _e('Event', 'wp-ultimo'); ?></th>
                            <th scope="col" id="integration" class="manage-column column-integration"><?php _e('Created by', 'wp-ultimo'); ?></th>
                            <th scope="col" id="sent_events" class="manage-column column-sent_events"><?php _e('Sent Events', 'wp-ultimo'); ?></th>
                            <th scope="col" id="active" class="manage-column column-sent_events"><?php _e('Active', 'wp-ultimo'); ?></th>
                        </tr>
                    </thead>

                    <tbody id="the-list" data-wp-lists="list:webhook" class="">

                        <tr v-if="loading">
                          <td colspan="7">
                            <?php _e('Loading...', 'wp-ultimo'); ?>
                          </td>
                        </tr>

                        <tr v-cloak v-if="!loading && webhooks.length == 0">
                          <td colspan="7">
                            <?php _e('No Webhooks created so far', 'wp-ultimo'); ?>
                          </td>
                        </tr>
                        
                        <tr v-cloak class="wu-plan-sortable ui-sortable-handle" v-bind:class="checkEdit(webhook.id) ? 'hidden' : ''" v-for="webhook in webhooks" v-if="!loading">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="bulk-delete[]" v-bind:value="webhook.id" v-model="selected" number>
                            </th>
                            <td class="title column-name has-row-actions column-primary" data-colname="Name">
                                <strong>{{webhook.name}}</strong> <code v-if="!webhook.active"><?php _e('Inactive', 'wp-ultimo'); ?></code> <code v-if="checkSending(webhook.id)"><?php _e('Sending...', 'wp-ultimo'); ?></code>
                                 
                                <div class="row-actions">
                                  <span class="edit"><a href="#" @click="edit($event, webhook)"><?php _e('Edit', 'wp-ultimo'); ?></a> | </span>
                                  <span class="edit" v-if="webhook.id"><a class="thickbox" title="<?php _e('Event Logs', 'wp-ultimo'); ?>" :href="'<?php echo admin_url('admin-ajax.php?action=wu_serve_logs'); ?>&id=' + webhook.id + '&TB_iframe=true&width=600&height=550'"><?php _e('See Events', 'wp-ultimo'); ?></a> | </span>
                                  <span class="edit"><a href="#" @click="sendTestEvent($event, webhook)"><?php _e('Send Test Event', 'wp-ultimo'); ?></a> | </span>
                                  <span class="delete"><a href="#" @click="remove($event, webhook)"><?php _e('Delete Webhook', 'wp-ultimo'); ?></a></span>
                                </div>
                            </td>
                            <td class="url column-url">
                              <span>{{webhook.url}}</span>
                            </td>
                            <td class="event column-event">
                              <code>{{webhook.event}}</code>
                            </td>
                            <td class="integration column-integration">
                              <code>{{webhook.integration}}</code>
                            </td>
                            <td class="sent_events column-sent_events">{{webhook.sent_events_count}}</td>
                            <td class="active column-active">{{ webhook.active ? "<?php _e('Active', 'wp-ultimo'); ?>" : "<?php _e('Inactive', 'wp-ultimo'); ?>" }}</td>
                        </tr>

                        <tr id="placeholder" class="hidden"></tr>
                        <tr v-cloak id="inline-edit" v-show="editing" v-if="!loading" class="inline-edit-row inline-edit-row-post quick-edit-row quick-edit-row-post inline-edit-post inline-editor" style="">
                          <td colspan="7" class="colspanchange">

                              <fieldset class="inline-edit-col-left">
                                  <legend class="inline-edit-legend"><?php _e('Edit Webhook', 'wp-ultimo'); ?></legend>
                                  <div class="inline-edit-col">

                                      <label>
                                          <span class="title"><?php _e('Name', 'wp-ultimo'); ?></span>
                                          <span class="input-text-wrap"><input type="text" name="post_title" class="ptitle" v-model="activeWebhook.name"></span>
                                      </label>

                                      <label>
                                          <span class="title"><?php _e('URL', 'wp-ultimo'); ?></span>
                                          <span class="input-text-wrap"><input type="text" name="post_name" v-model="activeWebhook.url"></span>
                                      </label>

                                      <fieldset class="inline-edit-date">
                                          <legend><span class="title"><?php _e('Event', 'wp-ultimo'); ?></span></legend>
                                          <div class="timestamp-wrap">
                                              <label><span class="screen-reader-text"><?php _e('Event', 'wp-ultimo'); ?></span>
                                                  <select name="event" v-model="activeWebhook.event">
                                                      <option value=""><?php _e('Select Event', 'wp-ultimo'); ?></option>
                                                      <?php foreach(WU_Webhooks()->get_events() as $event) : ?>
                                                        <option value="<?php echo $event['type']; ?>"><?php echo $event['name']; ?></option>
                                                      <?php endforeach; ?>
                                                  </select>
                                              </label>
                                          </div> 
                                      </fieldset>

                                      <br class="clear">

                                      <div class="inline-edit-group wp-clearfix">
                                          <label class="alignleft">
                                              <span class="title"><?php _e('Active', 'wp-ultimo'); ?></span>
                                              <input type="checkbox" name="active" value="private" v-model="activeWebhook.active" v-bind:true-value="1"
  v-bind:false-value="0">
                                              <!-- <span class="checkbox-title">Active</span> -->
                                          </label>
                                      </div>

                                  </div>
                              </fieldset>

                              <fieldset class="inline-edit-col-center inline-edit-categories">
                                <div class="inline-edit-col"></div>
                              </fieldset>

                              <div class="submit inline-edit-save">
                                  <button type="button" class="button cancel alignleft" @click="stopEdit"><?php _e('Close', 'wp-ultimo'); ?></button>
                                  <button type="button" class="button button-primary save alignright" @click="update($event, activeWebhook)"><?php _e('Save', 'wp-ultimo'); ?></button>
                                  <span class="spinner"></span>
                                  <br class="clear">
                                  <div class="notice notice-error notice-alt inline hidden">
                                      <p class="error"></p>
                                  </div>
                              </div>
                          </td>
                      </tr>

                    </tbody>

                    <tfoot>
                        <!-- <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-2"><?php _e('Select All', 'wp-ultimo'); ?></label>
                                <input id="cb-select-all-2" type="checkbox" v-model="selected">
                            </td>
                            <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'wp-ultimo'); ?></th>
                            <th scope="col" class="manage-column column-url"><?php _e('URL', 'wp-ultimo'); ?></th>
                            <th scope="col" class="manage-column column-event"><?php _e('Event', 'wp-ultimo'); ?></th>
                            <th scope="col" class="manage-column column-sent_events"><?php _e('Sent Events', 'wp-ultimo'); ?></th>
                            <th scope="col" class="manage-column column-active"><?php _e('Active?', 'wp-ultimo'); ?></th>
                        </tr> -->

                        <tr>
                            <td colspan="7" class="manage-column">
                                <button @click="addNew" class="button button-primary pull-right"><?php _e('Add new Webhook', 'wp-ultimo'); ?></button>
                                <button @click="removeMany" v-bind:disabled="selected.length == 0" class="button"><?php _e('Delete Selected', 'wp-ultimo'); ?></button>
                            </td>
                        </tr>
                    </tfoot>


                </table>
                
                <div id="nonce-field">
                  <?php wp_nonce_field('wu-updating-webhooks'); ?>
                </div>

          </div>
      </div>
      <br class="clear">
  </div>

  <div class="row">

    <div class="wu-col-md-12">
      <h2><?php _e('Available Events', 'wp-ultimo'); ?></h2>
    </div>

    <?php foreach (WU_Webhooks()->get_events() as $event) : ?>

      <div class="wu-col-lg-3 wu-col-md-4 wu-col-sm-4 wu-col-xs-6" style="margin-bottom: 30px;">
        <strong><?php echo $event['name']; ?></strong> <code><?php echo $event['type']; ?></code>
        <br><p class="description" style="margin-top: 8px;"><?php echo $event['description']; ?></p>

        <a style="text-decoration: none;" title="<?php printf(__('Event Payload: %s', 'wp-ultimo'), $event['name']); ?>" class="thickbox" href="#TB_inline?width=600&height=400&inlineId=payload-<?php echo $event['type']; ?>"><?php _e('See Payload &rarr;', 'wp-ultimo'); ?></a>

        <div id="payload-<?php echo $event['type']; ?>" class="hidden">
          <div>
            <p class="description" style="margin-top: 10px;"><?php _e('All the fields below will be sent as the payload to the webhook URL', 'wp-ultimo'); ?></p>
            <pre><strong><?php _e('Payload:', 'wp-ultimo'); ?></strong><br><?php 
              $payload = array_keys($event['data']); 
              sort($payload); 
              echo implode('<br>', $payload); ?></pre>
          </div>
        </div>
      </div>

    <?php endforeach; ?>

  </div>

</div>

<div id="preview-id" style="display:none;">
  <div v-html="previewLog"></div>
</div>