<?php 
namespace Mostafiz\Icurl;

class Icurl{
    private $ch;

    function __construct($url = null)
    {
        $this->ch = curl_init();

        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
    }

    function url($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        return $this;
    }

    function proxy($proxies = array())
    {
        $proxy = $proxies[ array_rand($proxies)]; 

        preg_match('/^(\d[\d.]+):(\d+)\b/', $proxy, $matches);

        if(count($matches))
        {
            $proxy = explode(':', $proxy);
            $ip = $proxy[0];
            $port = $proxy[1];
            curl_setopt($this->ch, CURLOPT_PROXY, $ip); 
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $port);
            curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL , 1);
        } 
        else 
        {
            die("Please give a array of valid proxies like [ '120.10.1.123:12345', '000.12.20.122:80' ]");
        }

        return $this;
    }

    function auth($username, $password)
    {
        curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, "$username:$password");
        return $this;
    }

    function get()
    {
        return curl_exec($this->ch);
    }

    function __destruct()
    {
        curl_close($this->ch); 
    }

}