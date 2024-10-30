<?php
add_filter('bulk_actions-edit-shop_order', function ($bulk_actions) {
  $bulk_actions['export_orders_oberon'] = __('Export orders to OBERON');
  return $bulk_actions;
});

add_filter('handle_bulk_actions-edit-shop_order', function ($redirect_to, $doaction, $post_ids) {
 
  if ($doaction !== 'export_orders_oberon') {
    return $redirect_to;
  }

  $xml = be_export_objednavok_oberon_xml($post_ids);

  header('Content-type: text/xml');
  header("Content-Disposition: attachment; filename=file.xml");
  echo $xml;
  exit();
}, 10, 3);

add_filter('pre_get_posts', function ($wp_query) {
  if (
    is_admin()
    && $wp_query->is_main_query()
    && isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) == 'shop_order'
    && isset($_GET['action']) && sanitize_text_field($_GET['action']) == 'export_orders_oberon'
  ) {
    $wp_query->set('posts_per_page', -1);
  }
  return $wp_query;
}, 10, 1);