<?php
//add_action('cmb2_init', 'initResQwestRoutes');

if ( file_exists( dirname( __FILE__ ) . '/httpful.phar' ) ) {
	require_once dirname( __FILE__ ) . '/httpful.phar';
}


function initResQwestRoutes() {

    $clearCache = resQwest_get_option('resQwest_clearCache');
    if ($clearCache === 'on')
    {
        error_log('clearing resQwest route cache');
        resQwest_update_option('resQwest_clearCache', '');
        delete_transient('resQwestRoutes');
    }
    
    $resQwestRoutes = get_transient('resQwestRoutes');
    if ( $resQwestRoutes === false)
    {
        error_log('loading resQwest routes');
        return loadResQwestRoutes();
    }
    else    
    {
        error_log('loading cached resQwest routes');
        return $resQwestRoutes;
    }
}

function loadResQwestRoutes() {
    // And you're ready to go!
    try {
        $uri = "https://api.resqwest.com/security/accounts/login";
        $apiId = resQwest_get_option('resQwest_apiId');
        $apiKey = resQwest_get_option('resQwest_apiKey');

        if (empty($apiId))
        {
            error_log('no api id');
            return;
        }
        
        $response = \Httpful\Request::post($uri)
            ->sendsJson()
            ->body("{ username: '" . $apiId . "', password: '" . $apiKey . "'}")
            ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
            ->send();

        $accessDetails = json_decode($response);
        
        if (isset($accessDetails->access_token))
        {
            $accessToken = $accessDetails->access_token;
        
            $body = "{ \$type: 'PortalWebRouteConfigRequest'}";
            $configUri = "https://api.resqwest.com/api/admin/portals/config";
            $configResponse = \Httpful\Request::post($configUri)
                ->sendsJson()
                ->body($body)
                ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
                ->addHeader('Authorization', 'Bearer ' . $accessToken)
                ->send();
            
            error_log($configResponse);
            $routeConfig = json_decode($configResponse);
            
            if (isset($routeConfig->routes))
            {
                $routes = $routeConfig->routes;
                $resQwestRoutes = array(); 
                foreach($routes as $key => $value)
                {
                    $resQwestRoutes[$value->webRoute] = $value->internalRoute;
                }
                $days = resQwest_get_option('resQwest_virtualPageDays');
               
                error_log('setting transient for ' . (int)$days * DAY_IN_SECONDS);
                set_transient('resQwestRoutes', $resQwestRoutes, (int)$days * DAY_IN_SECONDS);
                return $resQwestRoutes;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
    catch(Exception $e)
    {
        // ignore exceptions as it crashed the page load if there is a connectivity issue
        // clear any transient data to force a retry
        delete_transient('resQwestRoutes');
        error_log('resQwestRoutes loading exception: ' . $e);
    }

}
?>