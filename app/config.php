<?php
add_filter('beeo_order_item_TransportTrackingNumber', function ($number, $order) {
    return $order->get_order_number();
}, 10, 2);

add_filter('beeo_order_gateways_fees_value', function ($number, $order) {
    $items = $order->get_items();
    $itemsprice = 0;
    if ($items) {
        foreach ($items as $item) {
            $itemsprice += $item->get_total();
        }
    }
    $number = $order->get_total() - $order->get_total_tax() - $order->get_shipping_total() - $itemsprice;
    return $number;
}, 10, 2);
