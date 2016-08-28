Using the Poniverse authentication source with SimpleSAMLphp
===========================================================

For the main simplesamlpackage be sure run to get the libraries this
driver depends on.

```
composer require poniverse/api:dev-rewrite
```

Remember to configure `authsources.php`, with both OAuth Client ID and Secret.

```
    'poniverse' => array(
        'authponiverse:Poniverse', // Do not modify
        'key' => '', // Client ID
        'secret' => '', // Client Secret
    ),
```

To get an OAuth Client setup, contact Poniverse.

Set the callback URL to be:

 * `http://sp.example.org/simplesaml/module.php/authponiverse/linkback.php`

Replace `sp.example.org` with your hostname.

## Testing authentication

On the SimpleSAMLphp frontpage, go to the *Authentication* tab, and use the link:

  * *Test configured authentication sources*

Then choose the *poniverse* authentication source.

Expected behaviour would then be that you are sent to Poniverse and asked to login.
