<div class="" v-if="type == 'message'" v-cloak>

  <p><?php _e('This is how your clients will view this on their dashboard', 'wp-ultimo'); ?></p>

  <div v-bind:class="'notice notice-' + style" v-html=""> 
    <p v-if="message">
      <strong v-if="subject"><% subject %> -</strong> <span v-html="message.replace(/(<p[^>]+?>|<p>|<\/p>)/img, '')"></span>
    </p>
    <p v-if="!message"><?php _e('Write a message...', 'wp-ultimo'); ?></p>
  </div>

</div>

<div v-if="type != 'message'">
  <p><?php _e('This preview only works for broadcasts of type <strong>message</strong>. Use the preview button to send a preview email.', 'wp-ultimo'); ?></p>
</div>