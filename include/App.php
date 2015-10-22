<?php

require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 's5', 'API.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Saml', 'Request.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'GAppsUtils.php']));
include_once(__DIR__.'/google-api/src/Google/autoload.php');

class App
{
    public static $config;
    public static $s5;
    public static $gapps;

    public static function start()
    {
        self::$config = json_decode(file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'.local.json'));
        self::$config->gapps->saml_public = file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'.server.crt');
        self::$config->gapps->saml_private = file_get_contents(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'.server.pem');

        self::$s5 = new \s5\API(self::$config->s5->token, self::$config->s5->secret);


        $request = $_SERVER['REQUEST_URI'];
        if (strpos($request, '?') !== false) {
            $request = substr($request, 0, strpos($request, '?'));
        }

        if ($request === '/gapps') {
            self::$s5->RequireLogin();
            require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Controllers', 'gapps.php']));
        } else if ($request === '/incoming') {
            require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Controllers', 'incoming.php']));
        } else {
            header("HTTP/1.1 404 File Not Found");
            echo $request." not found";
        }
    }

    private static $_gapps;
    public static function gapps()
    {
        if (!isset(self::$_gapps)) {
            $client = new Google_Client();
            $client->setApplicationName('s5apps');
            $client->setScopes([\Google_Service_Directory::ADMIN_DIRECTORY_USER]);
            $client->setAuthConfigFile(dirname(__DIR__).'/.client_secret.json');
            $client->setAccessType('offline');

            $credentialsPath = dirname(__DIR__).'/.creds';
            if (file_exists($credentialsPath)) {
                $accessToken = file_get_contents($credentialsPath);
            } else {
                if (!$_GET['code']) {
                    echo $client->createAuthUrl();
                    exit;
                } else {
                    $accessToken = $client->authenticate($_GET['code']);
                    file_put_contents($credentialsPath, $accessToken);
                }
            }
            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                $client->refreshToken($client->getRefreshToken());
                file_put_contents($credentialsPath, $client->getAccessToken());
            }

            self::$_gapps = new Google_Service_Directory($client);
        }

        return self::$_gapps;
    }

    public static function UserAllowed($user, $groups) {
        $authorized = false;
        foreach ($groups as $allowed_group) {
            foreach ($user->groups as $group) {
                if ($group->id == $allowed_group) {
                    $authorized = true;
                }
            }
        }

        return $authorized;
    }
}