<?php
namespace Mostafiz\Gplagiarism;

use Mostafiz\Icurl\Icurl;

class Gplagiarism{
    
    private function excerpt_paragraph($html, $max_char = 100, $trail='...' )
    {
        // temp var to capture the p tag(s)
        $matches= array();
        if ( preg_match( '/<p>[^>]+<\/p>/', $html, $matches) )
        {
            // found <p></p>
            $p = strip_tags($matches[0]);
        } else {
            $p = strip_tags($html);
        }
        //shorten without cutting words
        $p = $this->short_str($p, $max_char );
    
        // remove trailing comma, full stop, colon, semicolon, 'a', 'A', space
        $p = rtrim($p, ',.;: aA' );
    
        // return nothing if just spaces or too short
        if (ctype_space($p) || $p=='' || strlen($p)<10) { return ''; }
    
        return '<p>'.$p.$trail.'</p>';
    }
    
    public function short_str( $str, $len, $cut = false )
    {
        if ( strlen( $str ) <= $len ) { return $str; }
        $string = ( $cut ? substr( $str, 0, $len ) : substr( $str, 0, strrpos( substr( $str, 0, $len ), ' ' ) ) );
        return $string;
    }
    
    
    public function getResult($text, $proxies = array(), $auth = array())
    {
        if(!count($proxies))
        {
           die("You have to pass an array of valid proxies like [ '120.10.1.123:12345', '000.12.20.122:80' ] in the 2nd paramete of getResult function of Gplgiarism instace.");
        }

        if(!count($auth))
        {
            die("You have to pass an array of valid credentials for your private proxies like [ 'username', 'password' ] in the 3rd paramete of getResult function of Gplgiarism instace.");
        }

        $url = "http://google.com/search?q=".urlencode($text);
        $curl = new Icurl();

        $html =  $curl->url($url)
        ->proxy($proxies)
        ->auth($auth[0], $auth[1])
        ->get();

        $dom = str_get_html($html);

        $results = $dom->find("div#main > div > div.ZINbbc");
        
        if(count($results) < 1)
        {
        
            for ($x = 0; $x <= 9; $x++) 
            {
                $curl = new Icurl();
                $html =  $curl->url($url)
                            ->proxy($proxies)
                            ->auth('ihabradwan','abuumar')
                            ->get();
        
                $dom = str_get_html($html);
                $results = $dom->find("div#main > div > div.ZINbbc");
                if(count($results) > 0) {
                    break;
                }
            }
        }
        
        
        $links = [];
        $titles = [];
        
        $plagcheck = $text;
        $plagcheck =   str_replace(" ", "", $plagcheck);
        $plagcheck = strtolower($plagcheck);
        $plagcheck = trim(preg_replace('/\s+/', ' ', $plagcheck));
        $plagcheck =   str_replace(" ", "", $plagcheck);
        
        $arr = str_split($plagcheck, 100);
        $arritem = count($arr);
        
             
        foreach($results as $item)
        {
            foreach($item->find('a > div.vvjwJb') as $key => $txt)
            {
                $titles[] = $txt->plaintext;
            }
            
            foreach($item->find('div.kCrYT') as $result)
            {
                $contentLink = $result->find('a');
        
                foreach($contentLink as $contentUrl)
                {
                    $url = str_replace('/url?q=', '', $contentUrl->href);
                    $url = explode("&", $url);
                    $links[] = $url[0];
                }
            //$url[] = $result->plaintext;
            }
        }
        
        $links = array_slice($links, 0, 10);
        
        $titles = array_slice($titles, 0, 10);
        
        // echo "<pre>";
        // print_r($titles);
        // die;
        
        $unq = 0;
        
        $data = [];

        foreach($links as $key => $link)
        {
            $page = $curl->url($link)->get();
    
    
            $exc = $this->excerpt_paragraph($page, $max_char = 150, $trail='' );
    
    
            $page = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $page);
            $page = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $page);
        
            $page =  strip_tags($page);
            $page =   str_replace(" ", "", $page);
            $page = strtolower($page);
            $page = trim(preg_replace('/\s+/', ' ', $page));
            $page =   str_replace(" ", "", $page);
        
            $plagpercent = 0;
        
            for ($x = 0; $x <= $arritem-1; $x++) 
            {
                $rsltplag =  strpos($page, $arr[$x]);
        
                if($rsltplag > 0)
                {
                    $plagpercent++;
                }
        
            }
        
            if($plagpercent > 0)
            {
        
                $data[] = [
                    'title' => $titles[$key],
                    'link' => $link,
                    'text' =>  strip_tags($exc),
                    'plagiarism' => round(($plagpercent/$arritem)*100, 0),
                ];
        
                $unq++;
            }
        
        }
        
        
        
        if($unq == 0)
        {
            $data = [];
        }
        
        
        return json_decode( json_encode($data) );
    }
}

