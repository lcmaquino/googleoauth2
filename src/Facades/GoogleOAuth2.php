<?php

namespace Lcmaquino\GoogleOAuth2\Facades;

use Lcmaquino\GoogleOAuth2\GoogleOAuth2Manager;

use Illuminate\Support\Facades\Facade;

class GoogleOAuth2 extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { 
        return GoogleOAuth2Manager::class;
    }
}
