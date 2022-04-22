<?php

class AUTHORIZATION
{
    public static function validateTimestamp($token)
    {
        $CI =& get_instance();
       //~ echo"<pre>";print_r($CI);echo"</pre>";
        $token = self::validateToken($token);
        //~ echo"<pre>";print_r($CI);echo"</pre>";
        //~ echo $CI->config->item('token_timeout');
        if ($token != false && (now() - $token->timestamp < ($CI->config->item('token_timeout') * 60))) {
            return $token;
        }
        else{
			return [];
		}
    }

    public static function validateToken($token)
    {
        $CI =& get_instance();
        //~ echo"<pre>";print_r($CI);echo"</pre>";
        try {
			return JWT::decode($token, $CI->config->item('jwt_key'));
			} catch (\Exception $e) {
			return false;
		}
        
    }

    public static function generateToken($data)
    {
        $CI =& get_instance();
        return JWT::encode($data, $CI->config->item('jwt_key'));
    }

}
