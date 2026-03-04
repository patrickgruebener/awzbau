<?php
/**
 * Plugin Name: AWZ Admin Menu
 * Description: Reorders and simplifies the WP admin menu for AWZ staff (Wina, Johanna).
 *              Users in $awz_full_access see the default full menu.
 */

// Logins that keep the full default menu (developers/admins)
$awz_full_access = ['admin'];

// Logins that get priority reordering but all items remain visible
$awz_reorder_only = ['w.benner@awz-bau.de'];

/**
 * Returns true if the current user should see the full default menu.
 */
function awz_has_full_menu() {
    global $awz_full_access;
    $user = wp_get_current_user();
    return in_array($user->user_login, $awz_full_access, true);
}

/**
 * Returns true if the current user gets reordering only (no items hidden).
 */
function awz_reorder_only() {
    global $awz_reorder_only;
    $user = wp_get_current_user();
    return in_array($user->user_login, $awz_reorder_only, true);
}

// Add Orders as standalone top-level menu item.
// WooCommerce registers shop_order with show_in_menu = 'woocommerce' (Admin capability),
// so it appears only as a WC submenu. We add it back explicitly for AWZ staff.
add_action('admin_menu', function () {
    if (awz_has_full_menu()) {
        return;
    }
    add_menu_page(
        __('Orders', 'woocommerce'),
        __('Orders', 'woocommerce'),
        'edit_shop_orders',
        'edit.php?post_type=shop_order',
        '',
        'dashicons-cart'
    );
}, 5);

// Enable custom menu ordering (only for simplified-menu users)
add_filter('custom_menu_order', function () {
    return ! awz_has_full_menu();
});

// Define priority order — items not listed keep their position after these
add_filter('menu_order', function ($menu_order) {
    if (awz_has_full_menu()) {
        return $menu_order;
    }

    $priority = [
        'upload.php',
        'edit.php',
        'edit.php?post_type=page',
        'stec',
        'edit.php?post_type=product',
        'edit.php?post_type=shop_order',
        'layerslider',
        'metaslider',
        'themes.php',
        'tools.php',
    ];

    $new_order = [];
    foreach ($priority as $item) {
        if (in_array($item, $menu_order, true)) {
            $new_order[] = $item;
        }
    }
    foreach ($menu_order as $item) {
        if (!in_array($item, $priority, true)) {
            $new_order[] = $item;
        }
    }
    return $new_order;
});

// Hide menu items not needed by AWZ staff (skipped for reorder-only users like Wina)
add_action('admin_menu', function () {
    if (awz_has_full_menu() || awz_reorder_only()) {
        return;
    }

    $remove = [
        // WordPress core
        'edit-comments.php',
        'plugins.php',
        'options-general.php',
        'users.php',
        // WooCommerce — main menu + extras (Products remain as top-level, Orders added manually above)
        'woocommerce',
        'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
        'wc-admin&path=/analytics/overview',
        'woocommerce-marketing',
        // Mailjet
        'mailjet_settings_page',
        'mailjet_form_7',
        // Technical plugins (slug = full admin.php?page=... URL as registered by the plugin)
        'tablepress',
        'wpcf7',
        'wpcode',
        'admin.php?page=siteorigin-installer',
        'wpseo_dashboard',
        'sp-dsgvo',
        'loco',
    ];

    foreach ($remove as $slug) {
        remove_menu_page($slug);
    }
}, 999);
