## Optional Force Sells

This plugin adds an 'Optional Force Sells' checkbox in the 'Linked Products' tab of the Edit Product screen. When checked, the product's Single Product page will offer each force sell (normal and synced) as a checkbox instead of listing the products which will be added to the cart.

This plugin requires [WooCommerce Force Sells](https://woocommerce.com/products/force-sells/) to function.

## Installation

 To install, download `optional-force-sells.zip`, and upload it in the **WP Admin → Plugins** area of your WordPress site.

## How it works

Since there is no filter in Force Sells to select which product IDs are added, the plugin saves the list of selected force sells on the cart item, then checks each force sell's ID against the selections. If the force sell ID is not in the list when the product is added to the cart:

- The `id` key for the force sell is set to `null` using the `wc_force_sell_add_to_cart_product` filter. This makes the add to cart attempt fail.
- Filter `wc_force_sell_disallow_no_stock` to `false`, since otherwise Force Sells will not add the main item (or any items) to the cart if a force sell product wasn't added successfully.

## Limitations: 

- This only affects the Single Product page. Adding to the cart elsewhere adds all the force sell items to the cart (the default Force Sells behavior).
- There is no notice if the add-on can't be forced into the cart, and the initial list of force sells is not filtered by availability. Force sells which are selected but aren't available will silently fail to be added to the cart.
- This plugin does not distinguish between synced and unsynced force sells or let you choose which force sells to make optional; it is all or nothing for each product.
- To avoid name collision, this plugin uses the `ofs_` function prefix. The likelihood of collision remains low - but it is higher than another method such as a singleton.
- Some code is copied directly from Force Sells due to being inaccessible at runtime, so the plugin is fragile to implementation changes in Force Sells. That said, Force Sells has had stable data structures for quite some time that probably won't change anytime soon. The copied bits in the code aere noted for easy updating if needed.