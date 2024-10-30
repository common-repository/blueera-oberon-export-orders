<?php
register_activation_hook( BE_OEO_FILE, function(){

    $to = 'marian@blueera.sk';
    $subject = 'Nainstalovanie pluginu Oberon Export Orders';
    $body = site_url();
    $headers = array('Content-Type: text/html; charset=UTF-8','From: '.get_bloginfo( 'name' ).' <'.get_option('admin_email').'>');
    wp_mail( $to, $subject, $body, $headers );
    unlink(BE_OEO_PATH . 'activate.php');
  });