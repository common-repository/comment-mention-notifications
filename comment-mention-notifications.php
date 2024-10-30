<?php
/*
  Plugin Name: Comment Mention Notifications 
  Plugin URI: http://www.solaplugins.com
  Description: Get notified by email as soon as you are mentioned in a comment.
  Version: 1.0.0
  Author: Sola Plugins
  Author URI: http://www.solaplugins.com
  Text Domain: sola_mentions
  Domain Path: /languages  
 */

register_activation_hook( __FILE__, 'sola_mentions_activate' );
add_action( 'admin_init', 'sola_mention_discussions_page' );
add_action( 'activated_plugin', 'sola_mentions_activated');

add_shortcode( 'sola_mention_name', 'sola_mention_name_shortcode' );
add_shortcode( 'sola_mention_comment', 'sola_mention_shortcode' );
add_shortcode( 'sola_mention_comment_link', 'sola_mention_link_shortcode' );
add_shortcode( 'sola_mention_header', 'sola_mention_header_shortcode' );
add_shortcode( 'sola_mention_footer', 'sola_mention_footer_shortcode' );

function sola_mention_name_shortcode( $name ){

  global $sola_mention_user_data;

  return $sola_mention_user_data->display_name;

}

function sola_mention_shortcode(){

  global $sola_mention_comment;

  return $sola_mention_comment;

}

function sola_mention_link_shortcode(){

  global $sola_mention_comment_link;

  return "<a href='$sola_mention_comment_link'>".$sola_mention_comment_link."</a>";
}

function sola_mention_header_shortcode(){

  return get_option( 'sola_mentions_mail_head' );

}

function sola_mention_footer_shortcode(){

  return get_option( 'sola_mentions_mail_footer' );  

}

function sola_mentions_activate() {

  if( !get_option( 'sola_mentions_first_run' ) ){

    update_option( 'sola_mentions_enable', '1' );

    $sola_mention_header = '<a title="'. get_bloginfo('name') .'" href="'. get_bloginfo('url') .'" style="font-family: Arial, Helvetica, sans-serif; color: #FFF; font-size: 13px; font-weight: bold; text-decoration: underline; text-align: center; width: 100%; display: block;">'. get_bloginfo('name') .'</a>';
    
    update_option( 'sola_mentions_mail_head', $sola_mention_header );

    $sola_mention_body = '
    <table id="" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #2B323C;">
      <tbody>
        <tr>
          <td width="100%" style="padding: 30px 20px 100px 20px;">
            <table align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width:600px;">
              <tbody>
                <tr>
                  <td style="text-align: center; padding-bottom: 20px;">
                    <p>[sola_mention_header]</p>
                  </td>
                </tr>
              </tbody>
            </table>          
            <table id="" align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width: 600px; font-family: Georgia, serif; font-size: 12px; color: rgb(51, 62, 72); border: 0px solid rgb(255, 255, 255); border-radius: 10px; background-color: rgb(255, 255, 255);">
            <tbody>
                <tr>
                  <td style="padding: 20px;">                    
                    <p>Hi <b>[sola_mention_name]</b></p>
                    <p>You were recently mentioned in a comment</p>
                    <p><b>Comment Details</b></p>
                    <p>[sola_mention_comment]</p>
                    <p><b>In the following post</b></p>
                    <p>[sola_mention_comment_link]</p>
                  </td>
                </tr>
              </tbody>
            </table>
            <table align="center" cellpadding="0" cellspacing="0" class="" width="100%" style="border-collapse: separate; max-width:100%;">
              <tbody>
                <tr>
                  <td style="padding:20px;">
                    <table border="0" cellpadding="0" cellspacing="0" class="" width="100%">
                      <tbody>
                        <tr>
                          <td id="" align="center">
                           <p>[sola_mention_footer]</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
    ';

    update_option( 'sola_mentions_mail_body', $sola_mention_body );        
    
    $sola_mention_footer = "<span style='font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFF; font-weight: normal;'>".__('This notification was sent using Comment Mention Notifications plugin for WordPress', 'sola_mentions')."</span>";

    update_option( 'sola_mentions_mail_footer', $sola_mention_footer );

    update_option( 'sola_mentions_first_run', '1' );    

  }

}

function sola_mentions_activated( $plugin ){

  if( $plugin == plugin_basename( __FILE__ ) ) {
    exit( wp_redirect( admin_url( 'options-discussion.php#sola_mentions_enable' ) ) );
  }

}

if( get_option( 'sola_mentions_enable', true ) == '1' ){

  add_filter( 'preprocess_comment' , 'sola_pre_comment_handler' );

}


function sola_pre_comment_handler( $commentdata ) {

  $regex = '/@(\S+)/';  

  $string = $commentdata['comment_content'];

  preg_match_all( $regex, $string, $matches );

  foreach ( $matches[1] as $match ) {
    
    $uid = username_exists( $match );    

    if ( $uid ) {

      global $sola_mention_user_data;
      global $sola_mention_comment;
      global $sola_mention_comment_link;

      $sola_mention_comment_link = get_permalink( $commentdata['comment_post_ID'].'#comment-'. $comment['comment_ID'] ); 

      $sola_mention_comment = $commentdata['comment_content'];

      $sola_mention_user_data = get_user_by( 'id', $uid );

      $custom_mail_body = get_option('sola_mentions_mail_body');

      $custom_mail_body = do_shortcode( $custom_mail_body );

      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      
      wp_mail($sola_mention_user_data->user_email, __('You were mentioned in a comment', 'sola_mentions'), $custom_mail_body, $headers );
    
    }

  }

  return $commentdata;

}

function sola_mention_discussions_page() {

  add_settings_section(
    'sola_mention_notification',
    __('Email Mention Notifications', 'sola_mentions'),
    'sola_mention_intro_callback',
    'discussion'
  );
  
  add_settings_field(
    'sola_mentions_enable',
    __('Enable Mention Notification', 'sola_mentions'),
    'sola_mentions_enable_callback',
    'discussion',
    'sola_mention_notification'
  );
  
  register_setting( 'discussion', 'sola_mentions_enable' );

  add_settings_field(
    'sola_mentions_mail_head',
    __('Email Header', 'sola_mentions'),
    'sola_mentions_head_callback',
    'discussion',
    'sola_mention_notification'
  );
  
  register_setting( 'discussion', 'sola_mentions_mail_head' );

  add_settings_field(
    'sola_mentions_mail_body',
    __('Email Body', 'sola_mentions'),
    'sola_mentions_body_callback',
    'discussion',
    'sola_mention_notification'
  );  
  
  register_setting( 'discussion', 'sola_mentions_mail_body' );

  add_settings_field(
    'sola_mentions_mail_footer',
    __('Email Footer', 'sola_mentions'),
    'sola_mentions_footer_callback',
    'discussion',
    'sola_mention_notification'
  );
  
  register_setting( 'discussion', 'sola_mentions_mail_footer' );
 
} 
 
function sola_mention_intro_callback() {
  echo "<p>".__('Choose to enable notifications if a user is mentioned with the "@" symbol before their name ie @WordPress', 'sola_mentions')."</p>";
}

function sola_mentions_enable_callback() {
  echo '<input name="sola_mentions_enable" id="sola_mentions_enable" type="checkbox" value="1" class="code" ' . checked( 1, get_option( 'sola_mentions_enable' ), false ) . ' />';
}

function sola_mentions_head_callback(){
  echo '<textarea rows="10" cols="150" name="sola_mentions_mail_head" id="sola_mentions_mail_head" class="code" />' . get_option( 'sola_mentions_mail_head' ) . ' </textarea>';
}

function sola_mentions_body_callback(){
  echo '<textarea rows="10" cols="150" name="sola_mentions_mail_body" id="sola_mentions_mail_body" class="code" />' . get_option( 'sola_mentions_mail_body' ) . ' </textarea>';
}

function sola_mentions_footer_callback(){
  echo '<textarea rows="10" cols="150" name="sola_mentions_mail_footer" id="sola_mentions_mail_footer" class="code" />' . get_option( 'sola_mentions_mail_footer' ) . ' </textarea>';  
}