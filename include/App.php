<?php

require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 's5', 'API.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Saml', 'Request.php']));
require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'GAppsUtils.php']));

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
            set_include_path(dirname(__FILE__).PATH_SEPARATOR.get_include_path());
            require_once(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Zend', 'Loader.php']));
            \Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
            \Zend_Loader::loadClass('Zend_Gdata_Gapps');

            $loginToken = null;
            $loginCaptcha = null;
            $client = \Zend_Gdata_ClientLogin::getHttpClient(self::$config->gapps->username,
                self::$config->gapps->password,
                \Zend_Gdata_Gapps::AUTH_SERVICE_NAME,
                null,
                '',
                $loginToken,
                $loginCaptcha,
                \Zend_Gdata_ClientLogin::CLIENTLOGIN_URI, 'HOSTED');
            self::$_gapps = new \Zend_Gdata_Gapps($client, self::$config->gapps->domain);
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