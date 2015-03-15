<?php 
/**
======================================================================
 lastRSS 0.9.2
 
 Simple yet powerfull PHP class to parse RSS files.
 
 by Vojtech Semecky, webmaster@webdot.cz
 by Hongbin Hsu, hongbin.hsu@gmail.com
 
 Latest version, features, manual and examples:
 http://lastrss.webdot.cz/
 
 ----------------------------------------------------------------------
 LICENSE
 
 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ======================================================================
*/
 
/**
* lastRSS
* Simple yet powerfull PHP class to parse RSS files.
*/
class lastRSS {
    // -------------------------------------------------------------------
    // Public properties
    // -------------------------------------------------------------------
    var $default_cp = 'UTF-8';
    var $CDATA = 'nochange';
    var $cp = '';
    var $items_limit = 0;
    var $stripHTML = False;
    var $date_format = '';
 
    // -------------------------------------------------------------------
    // Private variables
    // -------------------------------------------------------------------
    var $channeltags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
    var $itemtags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source','content:encoded');
    var $imagetags = array('title', 'url', 'link', 'width', 'height');
    var $textinputtags = array('title', 'description', 'name', 'link');
 
    // -------------------------------------------------------------------
    // Parse RSS file and returns associative array.
    // -------------------------------------------------------------------
    function Get ($rss_url) {
        // If CACHE ENABLED
        if ($this->cache_dir != '') {
            $cache_file = $this->cache_dir . '/rsscache_' . md5($rss_url);
            $timedif = @(time() - filemtime($cache_file));
            if ($timedif < $this->cache_time) {
                // cached file is fresh enough, return cached array
                $result = unserialize(join('', file($cache_file)));
                // set 'cached' to 1 only if cached file is correct
                if ($result) $result['cached'] = 1;
            } else {
                // cached file is too old, create new
                $result = $this->Parse($rss_url);
                $serialized = serialize($result);
                if ($f = @fopen($cache_file, 'w')) {
                    fwrite ($f, $serialized, strlen($serialized));
                    fclose($f);
                }
                if ($result) $result['cached'] = 0;
            }
        }
        // If CACHE DISABLED >> load and parse the file directly
        else {
            $result = $this->Parse($rss_url);
            if ($result) $result['cached'] = 0;
        }
        
        // return result
        return $result;
    }
    
    // -------------------------------------------------------------------
    // Modification of preg_match(); return trimed field with index 1
    // from 'classic' preg_match() array output
    // -------------------------------------------------------------------
    function my_preg_match ($pattern, $subject) {
        // start regullar expression
        preg_match($pattern, $subject, $out);
 
        // if there is some result... process it and return it
        if(isset($out[1])) {
            // Process CDATA (if present)
            if ($this->CDATA == 'content') { // Get CDATA content (without CDATA tag)
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            } elseif ($this->CDATA == 'strip') { // Strip CDATA
                $out[1] = strtr($out[1], array('<![CDATA['=>'', ']]>'=>''));
            }
 
            // If code page is set convert character encoding to required
            if (!empty($this->cp) && $this->cp != $this->rsscp && !empty($this->rsscp)){
                //$out[1] = $this->MyConvertEncoding($this->rsscp, $this->cp, $out[1]);
                $out[1] = iconv($this->rsscp, $this->cp.'//TRANSLIT', $out[1]);
            }
                
            // Return result
            return trim($out[1]);
        } else {
        // if there is NO result, return empty string
            return '';
        }
    }
 
    // -------------------------------------------------------------------
    // Replace HTML entities &something; by real characters
    // -------------------------------------------------------------------
    function unhtmlentities ($string) {
        // Get HTML entities table
        $trans_tbl = get_html_translation_table (HTML_ENTITIES, ENT_QUOTES);
        // Flip keys<==>values
        $trans_tbl = array_flip ($trans_tbl);
        // Add support for &apos; entity (missing in HTML_ENTITIES)
        $trans_tbl += array('&apos;' => "'");
        // Replace entities by values
        return strtr ($string, $trans_tbl);
    }
 
    // -------------------------------------------------------------------
    // Parse() is private method used by Get() to load and parse RSS file.
    // Don't use Parse() in your scripts - use Get($rss_file) instead.
    // -------------------------------------------------------------------
	function Parse ($rss_url) {
	//Modify by hongbin hsu
        // Open and load RSS file 
        $ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $rss_url); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);    //10 time out
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5000); 
		$rss_content = curl_exec($ch); 
		curl_close($ch);
		
        if (!empty($rss_content)) {
            // Parse document encoding
            $result['encoding'] = $this->my_preg_match("'encoding=[\'\"](.*?)[\'\"]'si", $rss_content);
            // if document codepage is specified, use it
            if ($result['encoding'] != '')
                { $this->rsscp = $result['encoding']; } // This is used in my_preg_match()
            // otherwise use the default codepage
            else
                { $this->rsscp = $this->default_cp; } // This is used in my_preg_match()
 
            // Parse CHANNEL info
            preg_match("'<channel.*?>(.*?)</channel>'si", $rss_content, $out_channel);
            foreach($this->channeltags as $channeltag)
            {
                $temp = $this->my_preg_match("'<$channeltag.*?>(.*?)</$channeltag>'si", $out_channel[1]);
                if ($temp != '') $result[$channeltag] = $temp; // Set only if not empty
            }
            // If date_format is specified and lastBuildDate is valid
            if ($this->date_format != '' && ($timestamp = strtotime($result['lastBuildDate'])) !==-1) {
                        // convert lastBuildDate to specified date format
                        $result['lastBuildDate'] = date($this->date_format, $timestamp);
            }
 
            // Parse TEXTINPUT info
            preg_match("'<textinput(|[^>]*[^/])>(.*?)</textinput>'si", $rss_content, $out_textinfo);
                // This a little strange regexp means:
                // Look for tag <textinput> with or without any attributes, but skip truncated version <textinput /> (it's not beggining tag)
            if (isset($out_textinfo[2])) {
                foreach($this->textinputtags as $textinputtag) {
                    $temp = $this->my_preg_match("'<$textinputtag.*?>(.*?)</$textinputtag>'si", $out_textinfo[2]);
                    if ($temp != '') $result['textinput_'.$textinputtag] = $temp; // Set only if not empty
                }
            }
            // Parse IMAGE info
            preg_match("'<image.*?>(.*?)</image>'si", $rss_content, $out_imageinfo);
            if (isset($out_imageinfo[1])) {
                foreach($this->imagetags as $imagetag) {
                    $temp = $this->my_preg_match("'<$imagetag.*?>(.*?)</$imagetag>'si", $out_imageinfo[1]);
                    if ($temp != '') $result['image_'.$imagetag] = $temp; // Set only if not empty
                }
            }
            // Parse ITEMS
            preg_match_all("'<item(| .*?)>(.*?)</item>'si", $rss_content, $items);
            $rss_items = $items[2];
            $i = 0;
            $result['items'] = array(); // create array even if there are no items
            
            foreach($rss_items as $rss_item) {
                // If number of items is lower then limit: Parse one item
                if ($i < $this->items_limit || $this->items_limit == 0) {
                    foreach($this->itemtags as $itemtag) {
                        $temp = $this->my_preg_match("'<$itemtag.*?>(.*?)</$itemtag>'si", $rss_item);
                        if ($temp != '') $result['items'][$i][$itemtag] = $temp; // Set only if not empty
                    }
                    // Strip HTML tags and other bullshit from DESCRIPTION
                    if ($this->stripHTML && $result['items'][$i]['content:encoded'])
                        $result['items'][$i]['content:encoded'] = $this->unhtmlentities($this->strip_tags($result['items'][$i]['content:encoded']));
                    
	//Modify by hongbin hsu
                    // Strip HTML tags and other bullshit from DESCRIPTION
                    if (!empty($result['items'][$i]['content:encoded'])) {
                    	$result['items'][$i]['description'] = $result['items'][$i]['content:encoded'];
                    	unset($result['items'][$i]['content:encoded']);
                    }elseif ($this->stripHTML && $result['items'][$i]['description']){
                        $result['items'][$i]['description'] = $this->unhtmlentities($this->strip_tags($result['items'][$i]['description']));
                    }

                    //Modify by hongbin hsu
                    // Strip HTML tags and other bullshit from TITLE
                    if ($this->stripHTML && $result['items'][$i]['title'])
                        $result['items'][$i]['title'] = $this->unhtmlentities($this->strip_tags($result['items'][$i]['title']));
                    // If date_format is specified and pubDate is valid
                    if ($this->date_format != '' && ($timestamp = strtotime($result['items'][$i]['pubDate'])) !==-1) {
                        // convert pubDate to specified date format
                        $result['items'][$i]['pubDate'] = date($this->date_format, $timestamp);
                    }
                    $result['items'][$i] = array_filter($result['items'][$i]);
                    // Item counter
                    $i++;
                }
            }
 
            $result['items_count'] = $i;
            return $result;
        }
        else // Error in opening return False
        {
            return False;
        }
    }
    
	//Add by hongbin hsu
    public function strip_tags($str){
        $str = htmlspecialchars(urldecode($str), ENT_QUOTES);
	    $str = stripcslashes($str);
        $str = html_entity_decode($str, ENT_QUOTES);
		$str = htmlspecialchars_decode($str, ENT_QUOTES);
		return $str;
    }
}
 
// $rss=new lastRSS();             //实例化
// $rss->cache_dir = 'cache';      //设置缓存目录，要手动建立
// $rss->cache_time = 3600;        //设置缓存时间。默认为0，即随访问更新缓存；建议设置为3600，一个小时
// $rss->default_cp = 'UTF-8';     //设置RSS字符编码，默认为UTF-8
// $rss->cp = 'UTF-8';               //设置输出字符编码，默认为GBK
// $rss->items_limit = 10;         //设置输出数量，默认为10
// $rss->date_format = 'U';        //设置时间格式。默认为字符串；U为时间戳，可以用date设置格式
// $rss->stripHTML = true;         //设置过滤html脚本。默认为false，即不过滤
// $rss->CDATA = 'content';        //设置处理CDATA信息。默认为nochange。另有strip和content两个选项

// $url = 'http://www.people.com.cn/rss/politics.xml';
// $data = $rss->Get($url);        //处理RSS并获取内容
// print_r($data);
?>
