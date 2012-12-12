#!/usr/bin/php
<?php
/**
* /home/user/.rtorrent.rc
* system.method.set_key = event.download.finished,auto_move,"execute=/home/user/rtorrent_auto_move/tor.php,$d.get_base_path=,$d.get_name=,$d.get_custom2="
* event.download.finished - for testing
*/

list(,$t_path,$t_name,$t_url) = $argv;

$url = urldecode(str_replace('VRS24mrker', '', $t_url));

// Загружаем страницу из комментария (*.torrents)
$ret = post($url);

$title = getTitle($ret);
if(is_file($t_path)) $ext = '.'.array_pop ( explode ( '.', $t_name ) );// else $ext = '/';
if(strpos($ret, '/')) $moviename = trim(current(explode('/',$title)));
if(preg_match('~[12][90]\d{2}~si', $title, $m)) $year = $m[0];
if(preg_match('~CAMRip|CAM|Telesync|TS|Telecine|TC|Super-?TS|VHS-?Rip|DVDScr|TV-?Rip|SAT-?Rip|HDTV-?Rip|BDRip|HDRip|DVD-?Rip|DVD5|DVD9~si', $title, $m)) $quality = $m[0];

$new_path = "/home/user/Films/$moviename $year $quality$ext";


// -s = символьные ссылки плюсы:можно ссылаться на каталог или другой раздел
// exec("ln -s \"$t_path\" \"$new_path\"");

// -n = не перезаписывать существующие файлы
// -f = перезаписывать не спрашивая
exec("mv -n \"$t_path\" \"$new_path\"");

nlog("$t_path\t->\t$new_path");


/**
 * Функции
 */

function getTitle($ret){
  if(preg_match('/charset=([^;"\\s]+|"[^;"]+")/i', $ret, $m)){
		$ret = iconv($m[1], 'UTF-8', $ret);
	}
	if(preg_match('~<title[^>]*>(.*?)</title>~si', $ret, $m)){
		return $m[1];
	}
	return false;
}

function nlog($str){
	file_put_contents('/home/user/logs.txt', date('Y-m-d H:i:s')."\t$str\n",FILE_APPEND);
}




function post($url,$postdata=0){
	$ch = curl_init($url);
	if (! empty ( $postdata )) {
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postdata );
	}
	curl_setopt($ch,CURLOPT_COOKIEJAR, "cookie.tmp");
	curl_setopt($ch,CURLOPT_COOKIEFILE, "cookie.tmp");

	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:2.0) Gecko/20100101 Firefox/4.0' );
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_REFERER, $url);
	$headers=array();
	$headers[]="Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
	$headers[]="Accept-Language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3";
	$headers[]="Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7";
	$headers[]="Expect:";
	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt ( $ch, CURLOPT_ENCODING, 'gzip,deflate');

	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

/**
 * Kinopoisk
 */
function getKinopoiskID($ret){
	if(preg_match('~kinopoisk\.ru/(?:rating|film)/(\d+)~si', $ret, $m)){
		return $m[1];
	}
	return false;
}

function getKinopoiskDesc($id){
	// post('http://www.kinopoisk.ru/level/30/','shop_user%5Blogin%5D=--LOGIN--&shop_user%5Bpass%5D=--PASS--&shop_user%5Bmem%5D=on&auth=%E2%EE%E9%F2%E8+%ED');

	$ret = i(post('http://www.kinopoisk.ru/film/'.$id,0));

	if(strpos($ret,'поступило необычно много запросов')) die("BAN!");

	file_put_contents('2.htm', $ret);

	$return['name']= m('itemprop="name">([^<]+)</h1>',$ret);
	$return['name_alter']= m('itemprop="alternativeHeadline">([^<]+)</span>',$ret);
	$return['text']= m('<div class="brand_words" itemprop="description">(.*?)</div>',$ret);
	$return['img']= "http://st.kinopoisk.ru/images/film/$id.jpg"; // /film_big/
	$return['rating']= m('<meta itemprop="ratingValue" content="([\d\.]+)" />',$ret);
	$return['country']= trim(strip_tags(m('страна</td>(.*?)</td>',$ret)));
	$return['year']= trim(strip_tags(m('год</td>(.*?)</td>',$ret)));
	$return['money']= str_replace('&nbsp;','',m('сборы в мире</td>.*?=\&nbsp;\$(.*?)</a>',$ret));
	if($return['money']==0) $return['money']= trim(str_replace('&nbsp;','',strip_tags(m('сборы в России</td>(.*?)</td>',$ret))),"\n\r $");

	return $return;
}



