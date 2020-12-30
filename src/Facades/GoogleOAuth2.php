<?php

namespace Lcmaquino\GoogleOAuth2\Facades;

use Lcmaquino\GoogleOAuth2\GoogleOAuthManager;

use Illuminate\Support\Facades\Facade;

class GoogleOAuth2 extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { 
        return 'goa2m';
    }
}