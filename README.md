# Easy Digital Downloads - No Logins
Allow users to access their purchase info using only their purchase email

## How it Works

* When a user visits EDD's Purchase History page (e.g. /checkout/purchase-history/), they will see this screen:

<img src="http://i.imgur.com/pc4FFAp.png" width="371" height="108" />

* After entering an email, a verification link will be emailed.
* The verification link goes back to the Purchase History page, but with the user's purchase information visible.

## Technical Details

* This add-on creates a custom database table, named `eddnl_tokens`
* A fake user, `eddnl` is created with a strong password and **no permissions**. This fake user is needed to bypass many of the `is_user_logged_in` checks within EDD and its add-ons.
* The `edd_payment_user_id` filter is used to set the correct user when viewing payment information.

## Hooks

`eddnl_verify_throttle`

Time (seconds) between when token requests can be sent to a given email address (default: 300)

`eddnl_token_expiration`

Time (seconds) before an access token expires (default: 86400)
