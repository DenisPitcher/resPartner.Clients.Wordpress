<?php

if ( file_exists( dirname( __FILE__ ) . '/httpful.phar' ) ) {
	require_once dirname( __FILE__ ) . '/httpful.phar';
}

function loadSecurityToken() {
        try {
            $accessToken = get_transient('resQwestAccessToken');
            if ($accessToken !== false)
            {
                error_log('loading cached token');
                return $accessToken;
            }

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
                   
                error_log('setting transient resQwestAccessToken for ' . 1 * DAY_IN_SECONDS);
                set_transient('resQwestAccessToken', $accessToken, 1 * DAY_IN_SECONDS);

                return $accessToken;
            }
            else {
                return false;
            }
        }
        catch(Exception $e)
        {
            // ignore exceptions as it crashed the page load if there is a connectivity issue
            // clear any transient data to force a retry
            delete_transient('resQwestAccessToken');
            error_log('resQwestAccessToken loading exception: ' . $e);
        }
}
?>