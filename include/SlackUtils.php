<?php


class SlackUtils
{
    public static function Invite($firstName, $lastName, $email) {
        self::post('users.admin.invite', [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'set_active' => 'true',
            't' => time()
        ]);
    }

    public static function get($endpoint, $data = []) {
        return self::query('GET', $endpoint, $data);
    }

    public static function post($endpoint, $data = []) {
        return self::query('POST', $endpoint, $data);
    }

    public static function query($method, $endpoint, $data = []) {
        $subdomain = App::$config->slack->subdomain;
        $url = 'https://'.$subdomain.'.slack.com/api/'.$endpoint;

        $data['token'] = App::$config->slack->token;
        
        $query = http_build_query($data);
        $opts = ['http' =>['method'  => strtoupper($method)]];
        if (strtoupper($method) === 'GET') {
            $url .= '?'.$query;
        } else {
            $opts['http']['header'] = 'Content-type: application/x-www-form-urlencoded';
            $opts['http']['content'] = $query;
        }

        $context  = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            $error = error_get_last();
            throw new \Exception($error['message']);
        }

        return $result;
    }
}
