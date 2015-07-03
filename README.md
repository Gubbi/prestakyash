# prestakyash
PrestaShop Integration Kit for the [Kyash Payment Gateway](http://www.kyash.com/).

## Installation
1. Login to your Admin Dashboard.
2. Go to `Modules` page. On the top right of window select option `Add a new module`
3. In the `ADD A NEW MODULE` section upload the [prestakyash.zip](https://github.com/Gubbi/prestakyash/releases/download/v1.1/Prestashop_Kyash_0.1.6.zip) file.
4. On this page you can find *Kyash* under `Payments and Gateway` list. Click on `Install` to proceed the installation.
5. Once finished, the *Kyash Settings* page appears. Here you need to fill your Kyash Account credentials, that is explained in the below section.

## Configuration
1. On your PrestaShop *Kyash Settings* page fill in the credentials (available in your Kyash Account Settings page).
2. There are two types of credentials you can enter: 
  - To test the system, use the *Developer* credentials.
  - To make the system live and accept your customer payments use the *Production* credentials.
3. Copy the *Callback URL* (e.g. `http://www.yoursite.com/?action=kyash-handler`) to your Kyash Account Settings and click `Set` to update the callback URL.

## Testing the Integration.
1. Place an order from your PrestaShop store.
2. Pick *Kyash - Pay at a nearby shop* as the payment option.
3. Note down the *KyashCode* generated for this order.
4. In a live system, the customer will take this KyashCode to a nearby shop and make the payment using cash.
5. But since we are testing, Login to your Kyash Account.
6. Enter the KyashCode in the search box.
7. You should see a `Mark as Paid` button there.
8. Clicking this should change the order status from *Processing in progress* to *Payment accepted* in your PrestaShop order details page.

## Troubleshooting
By default HTTPS scheme is used. If there are any SSL issue, alternatively you can use HTTP scheme as explained below.
1. Go to `modules`->`kyash`->`lib` from root folder.
2. Open `KyashPay.php` file.
3. Replace this line `private static $baseUri = 'https://api.kyash.in/v1';` with `private static $baseUri = 'http://api.kyash.in/v1';`.
4. Also replace `public $use_https = true;` as `public $use_https = false;`.

## Support
Contact developers@kyash.com for any issues you might be facing with this Kyash extension or call +91 8050114225.
