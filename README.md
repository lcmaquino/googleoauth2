## Introduction

GoogleOAuth2 is a Laravel package for Google OAuth 2.0 authentication.
It was created based on [Laravel Socialite](https://github.com/laravel/socialite) 
but with focus only on Google.

GoogleOAuth2 implements the main process for OAuth 2:
- authentication — get the user coming from Google authentication;
- get user information — get the user data from their Google account;
- refresh token — refresh the user's access token;
- revoke token — revoke the user's access token.

For more information about Google OAuth 2.0, please see https://developers.google.com/identity/protocols/oauth2/web-server

## Installation

It can be installed as usual for laravel packages:
```
$ cd /path/to/your/laravel/root
$ composer require lcmaquino/googleoauth2
$ php artisan vendor:publish --provider="Lcmaquino\GoogleOAuth2\GoogleOAuth2Provider"
```

Laravel should automatically include `Lcmaquino\GoogleOAuth2\GoogleOAuth2Provider`
as a service provider and include `GoogleAuth` as an alias for `Lcmaquino\GoogleOAuth2\Facades\GoogleOAuth2::class`.

It can be done manually editing `config/app.php` to look like:
```
    'providers' => [

        //More Service Providers...

        /*
         * Package Service Providers...
         */
        Lcmaquino\GoogleOAuth2\GoogleOAuth2Provider::class,
    ],

    'aliases' => [

        //More aliases...

        'GoogleAuth' => Lcmaquino\GoogleOAuth2\Facades\GoogleOAuth2::class,
    ],
```

## Configuration

Before using GoogleOAuth2, you need to set up an [OAuth 2.0 client ID](https://support.google.com/cloud/answer/6158849?hl=en). It will provide a *client id*, a *client secret*, and a *redirect uri* for your application. These parameters should be placed in your `.env` Laravel configuration file.
```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

It will be loaded by your application when reading the file `config/googleoauth2.php`:
```
<?php

return [
    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', ''),
];
```

## Routing

Create two routes in `routes/web.php`:
```
Route::get('login/google', 'Auth\LoginController@redirectToProvider');
Route::get('login/google/callback', 'Auth\LoginController@handleProviderCallback');
```

Create a `LoginController.php` to controll these routes:
```
$ php artisan make:controller Auth/LoginController
```

Open `app/Http/Controllers/Auth/LoginController.php` and edit like this:

```
<?php

namespace App\Http\Controllers\Auth;

use Lcmaquino\GoogleOAuth2\GoogleOAuth2Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        $ga = new GoogleOAuth2Manager(config('googleoauth2'), $request);

        return $ga->redirect();
    }

    public function handleProviderCallback(Request $request)
    {
        $ga = new GoogleOAuth2Manager(config('googleoauth2'), $request);
        
        $user = $ga->user();

        if(empty($user)) {
            //$user is not logged in.

            //Do something.
        }else{
            //$user is logged in.

            //Do something.
        }
    }
}
```

When you hit the route `login/google` it will redirect your request to 
Google authentication page. Google authentication will ask user 
for permission and then hit your callback route `login/google/callback`.

If the user has allowed your application to login with their Google account, 
then $user looks like:
```
Lcmaquino\GoogleOAuth2\GoogleUser {
    #sub: "1234"
    #name: null
    #email: "usermail@gmail.com"
    #emailVerified: true
    #picture: "https://something/with/code"
    #rawAttributes: array:4 [
        "sub" => "1234"
        "picture" => "https://something/with/code"
        "email" => "usermail@gmail.com"
        "email_verified" => true
    ]
    #token: "abcd1234"
    #refreshToken: null
    #expiresIn: 3599
}
```

See **Access Scopes** and **Retrieving User Details** for more details.

GoogleOAuth2 comes with a GoogleAuth facade. So you could edit `app/Http/Controllers/Auth/LoginController.php` like this:
```
<?php

namespace App\Http\Controllers\Auth;

use GoogleAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        return GoogleAuth::redirect();
    }

    public function handleProviderCallback(Request $request)
    {
        $user = GoogleAuth::user();

        if(empty($user)) {
            //$user is not logged in.

            //Do something.
        }else{
            //$user is logged in.

            //Do something.
        }
    }
}
```

## Optional Parameters

Google OAuth 2.0 support some optional parameters in the redirect request. To include any optional parameters in the request, call the `with` method with an associative array:
```
$params = [
    'approval_prompt' => 'force',
];

return GoogleAuth::with($params)->redirect();
```

## Access Scopes

The scopes are used by Google to limit your application access to the user account data.
Use the `scopes` method to set your scopes. The defaults are `openid` and `email`.
```
$scopes = [
    'openid',
    'email',
    'profile',
];

return GoogleAuth::scopes($scopes)->redirect();
```

## Stateless Authentication

The `stateless` method disable session state verification.
```
$user = GoogleAuth::stateless()->user();
```

## Retrieving User Details

Once you have an authenticated `$user`, you can get more details about the user:
```
$user = GoogleAuth::user();

$user->getSub(); //the unique Google identifier for the user.
$user->getName();
$user->getEmail();
$user->emailVerified();
$user->getPicture();
$user->getToken();
$user->getRefreshToken(); //not always provided
$user->getExpiresIn();
```

### Retrieving User Details From A Token

You can retrieve user details from a valid access `$token` using the 
`getUserFromToken` method:
```
$user = GoogleAuth::getUserFromToken($token);
```

## Refreshing token

The access token expires periodically. So you need to get a new one.
You can get this using the `refreshUserToken` method:
```
$new_token = GoogleAuth::refreshUserToken($refresh_token);
```

You should pay attetion to keep the user `$refresh_token` on your application.
If you lose it, then you can't get a new access token. In that case, the user 
has to log in again when the current access token expires.

You will notice that refresh token is not always provided on Google authentication.
You can force Google to do so using the `with` method (see **Optional Parameters**):
```
$params = [
    'approval_prompt' => 'force',
    'access_type' => 'offline',
];

return GoogleAuth::with($params)->redirect();
```

## Revoking token

If you need to invalidate the access token and the refresh token, you can revoke
them using the `revokeToken` method:
```
if (GoogleAuth::revokeToken($token)) {
    //token was revoked
}else{
    //token was not revoked
}
```

**Tips**
- You can use a valid access token or refresh token as `$token`.
- Remeber to revoke the token when the user decides to sign out/remove their data from your application.
- Keep in mind that the user always can revoke their token on https://myaccount.google.com/permissions.

## License

GoogleOAuth2 is open-sourced software licensed under the [GPL v3.0 or later](https://github.com/lcmaquino/googleoauth2/blob/main/LICENSE).