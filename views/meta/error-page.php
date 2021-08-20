<?php 
$defaults = array( 'response' => 500, 'back_link' => true );
$r = wp_parse_args($args, $defaults);

$have_gettext = function_exists('__');

if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
    if ( empty( $title ) ) {
        $error_data = $message->get_error_data();
        if ( is_array( $error_data ) && isset( $error_data['title'] ) )
            $title = $error_data['title'];
    }
    $errors = $message->get_error_messages();
    switch ( count( $errors ) ) {
    case 0 :
        $message = '';
        break;
    case 1 :
        $message = "<p>{$errors[0]}</p>";
        break;
    default :
        $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
        break;
    }
} elseif ( is_string( $message ) ) {
    $message = "<p>$message</p>";
}

if ( isset( $r['back_link'] ) && $r['back_link'] ) {
    $back_text = $have_gettext? __('&larr; Back', 'wp-ultimo') : '&larr; Back';
    // $message .= "\n<p class='submit'><a style='width: 100%; text-align: center;' class='button button-primary button-streched' href='javascript:history.back()'>$back_text</a></p>";
}

if ( ! did_action( 'admin_head' ) ) :
    if ( !headers_sent() ) {
        status_header( $r['response'] );
        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
    }

    if ( empty($title) )
        $title = $have_gettext ? __('WordPress &rsaquo; Error') : 'WordPress &rsaquo; Error';

    $text_direction = 'ltr';
    if ( isset($r['text_direction']) && 'rtl' == $r['text_direction'] )
        $text_direction = 'rtl';
    elseif ( function_exists( 'is_rtl' ) && is_rtl() )
        $text_direction = 'rtl';

wp_enqueue_style( 'themes' );
wp_enqueue_style( 'common' );
wp_enqueue_style( 'login' );
?><!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

  <head>

    <style type="text/css">

      body #login {
        width: 500px;
        text-align: left;
      } 

      body .login h1 a {
        background-size: 200px;
      }

      body #errorblock h1 {
        margin: .67em 0;
        text-align: left;
      }

      body #errorblock {
        padding-right: 24px;
        padding-left: 24px;
      }

      body div#errorblock {
        margin-top: 20px;
        margin-left: 0;
        padding: 26px 24px 12px;
        font-weight: 400;
        overflow: hidden;
        background: #fff;
        -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
        box-shadow: 0 1px 3px rgba(0,0,0,0.13);
      }
      
      body #errorblock p {
        padding-right: 0;
        padding-left: 0;
        margin-bottom: 16px;
      }

      body #errorblock form {
        box-shadow: none;
        margin: 0;
        padding: 0;
        clear: both;
      }

      body #errorblock p.submit, 
      body #nav p.submit, 
      body #backtoblog p.submit {
        box-sizing: content-box;
        /*margin: 0 -24px -46px -24px !important;*/
        clear: both;
        overflow: hidden;
      }

    </style>

    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>
      <?php echo $title; ?>
    </title>
    <?php wp_print_scripts('wp-ultimo'); ?>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('admin_print_scripts'); ?>
    <?php do_action('admin_head'); ?>
    <?php // Signup do action, like the default ?>
    <?php do_action('signup_header'); ?>
    <?php do_action('login_enqueue_scripts'); ?>
  </head>

  <body class="login wu-setup wp-core-ui">

  <?php endif; // ! did_action( 'admin_head' ) ?>

    <div id="login">
      <h1 id="wu-setup-logo">
        <a href="<?php echo get_site_url(1); ?>">
          &nbsp;
        </a>
      </h1>

      <div id="errorblock">
        
        <?php echo $message; ?>

      </div>

      <?php if (isset( $r['back_link'] ) && $r['back_link']) : ?>
      <p id="nav">
        <a href='javascript:history.back()'><?php echo $back_text; ?></a>
      </p>
      <?php endif; ?>

    </div>
    <div class="clear"></div>
  </body>

  <?php do_action('admin_print_footer_scripts'); ?>

</html>