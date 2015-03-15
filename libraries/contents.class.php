<?php
/**
 * 采集类  根据正文URL 采集内容，输出
 * tangjian  20120511
 
 include 'content.class.php';
 $content = new content();
 echo $content->get_content('http://news.gxnews.com.cn/staticpages/20120516/newgx4fb2d85b-5258067.shtml');
 */

class Contents {
	
	private $collection = null;
	
	function __construct() {
       require('collection.class.php');
	   $this->collection = new collection();
    }

	
	// 获取内容 路由
	function get_content($content_url)
	{
		$content = '';
		
		if(strpos($content_url, 'people.') !== false) {
			$content = $this->get_content_people($content_url);
		} elseif(strpos($content_url, 'qq.com') !== false) {
			$content = $this->get_content_qq($content_url);
		} elseif(strpos($content_url, 'sina.') !== false) {
			$content = $this->get_content_sina($content_url);
		} elseif(strpos($content_url, 'gxnews.com.cn') !== false) {
			$content = $this->get_content_gxnews($content_url);
		} elseif(strpos($content_url, 'ngzb.com.cn') !== false) {
			$content = $this->get_content_ngzb($content_url);
		} elseif(strpos($content_url, 'lznews.') !== false) {
			$content = $this->get_content_lznews($content_url);
		} elseif(strpos($content_url, 'chinanews.com') !== false) {
			$content = $this->get_content_chinanews($content_url);
		} elseif(strpos($content_url, 'xinhuanet.com') !== false) {
			$content = $this->get_content_xinhuanet($content_url);
		} elseif(strpos($content_url, 'ifeng.com') !== false) {
			$content = $this->get_content_ifeng($content_url);
		}
		
		// 去html strip_tags
		$content = strip_tags($content);
		$content = str_replace(array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '"', '"', '—', '<', '>', '·', '…'), $content);
	
		return $content;
	}
	
	//  获取广西新闻的内容
	function get_content_gxnews($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<div id="content">[内容]<!--【文章内容末尾广告】-->',        // 内容截取
				'content_html_rule' => '<div id="player1"(.*)</idv>[|]<script(.*)</script>',   // 内容过滤
				'content_page_start' => '<span class="here">[1]</span>',  // 分页起始点
				'content_page_end' => '</table>',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',	
				);		
		$data = $this->collection->get_content($url, $config, $config['content_page']);
		return $data['content'];
	}
	
	
	//  获取南国早报的
	function get_content_ngzb($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<div class="mb10 contentImage" id="read_tpc" style="word-wrap:break-word;word-break:break-all;width:100%;overflow:hidden; font-size:16px;">[内容]<!--content_read-->',        // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '',  // 分页起始点
				'content_page_end' => '',                         // 分页结束点
				'content_page_rule' => '0',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',
				);
		$data = $this->collection->get_content($url, $config, $config['content_page']);
		//return $data['content'];		
		
		// 去乱码  <span style="display:none">。。。
		$content = $a = array();
		$string = '';
		$content = explode('<span style="display:none">', $data['content']);		
		foreach($content as $key=>$value) {
			$a = explode('</span>', $value);
			$string .= $a[1];
		}
		return $content[0] . $string;
		//return preg_replace('/<span style="display:none">(.*)<\/span>/i', '', $string['content']);
	}
	
	//  获取柳州新闻网的
	function get_content_lznews($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '</span></h1>[内容]<!--内容关联投票-->',        // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '<div id="pages" class="text-c">',  // 分页起始点
				'content_page_end' => '</div>',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',	
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	//  获取中国新闻网
	function get_content_chinanews($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<div class="left_zw">[内容]<!--正文start-->',        // 内容截取
				'content_html_rule' => '<table([^>]*)>(.*)</table>[|]<div([^>]*)>(.*)</div>[|]',   // 内容过滤
				'content_page_start' => '<div id="function_code_page">',  // 分页起始点
				'content_page_end' => '</div>',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',	
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	
	//  获取人民网新闻内容
	function get_content_people($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<div id="p_content">[内容]<div class="edit"',        // 内容截取
				'content_html_rule' => '<table([^>]*)>(.*)</table>[|]<div([^>]*)>(.*)</div>[|]',   // 内容过滤
				'content_page_start' => '<div id="function_code_page">',  // 分页起始点
				'content_page_end' => '</div>',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',	
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	
	//  获取腾讯网新闻内容
	function get_content_qq($url)
	{
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<div id="Cnt-Main-Article-QQ" bossZone="content">[内容]<div class="ft">',  // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '',  // 分页起始点
				'content_page_end' => '',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	
	//  获取新浪网新闻内容
	function get_content_sina($url)
	{
		// 网址有跳转的 
		if(strpos($url, 'url=') !== false) {
			$url = explode('url=',$url);
			$url = $url[1];
		}		
		
		$config = array(
				'sourcecharset' => 'GBK',                                 // 内容编码			
				'content_rule' => '<!-- 正文内容 begin -->[内容]<div class="show_author">',  // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '',  // 分页起始点
				'content_page_end' => '',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	
	//  获取新华网新闻内容
	function get_content_xinhuanet($url)
	{
		$config = array(
				'sourcecharset' => 'UTF-8',                                 // 内容编码			
				'content_rule' => '<div id="contentblock">[内容]</div>',  // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '',  // 分页起始点
				'content_page_end' => '',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}
	
	
	//  获取凤凰网新闻内容
	function get_content_ifeng($url)
	{
		$config = array(
				'sourcecharset' => 'UTF-8',                                 // 内容编码			
				'content_rule' => '<div id="artical_real">[内容]</div>',  // 内容截取
				'content_html_rule' => '',   // 内容过滤
				'content_page_start' => '',  // 分页起始点
				'content_page_end' => '',                         // 分页结束点
				'content_page_rule' => '1',                               // 分页模式 1全部列出模式 2上 下一页模式
				'content_page' => '0',                                    // 内容分页 0不分页  1按原文分页
				'content_nextpage' => '',
				);		
		 $data = $this->collection->get_content($url, $config, $config['content_page']);
		 return $data['content'];	
	}

}




