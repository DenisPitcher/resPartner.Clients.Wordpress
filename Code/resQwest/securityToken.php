<?php

if ( file_exists( dirname( __FILE__ ) . '/httpful.phar' ) ) {
	require_once dirname( __FILE__ ) . '/httpful.phar';
}

function resQwest_loadSecurityToken() {
        error_log('loading security token...');
        try {
            $accessToken = get_transient('resQwestAccessToken');
            if ($accessToken !== false)
            {
                error_log('loading cached token');
                return $accessToken;
            }

            $uri = "https://security.resqwest.com/accounts/login";
            $apiId = resQwest_get_option('resQwest_apiId');
            $apiKey = resQwest_get_option('resQwest_apiKey');
    
            if (empty($apiId))
            {
                error_log('no api id');
                return;
            }
            
            // Suppress deprecation warnings from Httpful library (PHP 8.2+ compatibility)
            $old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
            $response = \Httpful\Request::post($uri)
                ->sendsJson()
                ->body("{ username: '" . $apiId . "', password: '" . $apiKey . "'}")
                ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
                ->send();
            error_reporting($old_error_reporting);
    
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