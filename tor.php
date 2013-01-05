#!/usr/bin/php
<?php
/**
* /home/user/.rtorrent.rc
* system.method.set_key = event.download.finished,move_comp,"execute=/home/user/tor.php,$d.get_base_path=,$d.get_name=,$d.get_custom2="
* event.download.paused - for testing
*
* sudo /etc/init.d/rtorrentInit.sh restart
* screen -r
*/


/**
 * Setings
 */
$login[]=array('url'=>'torrents.ru','login_url'=>'http://torrents.ru/forum/login.php','login_post'=>'login_username=--&login_password=--&autologin=3&login=%C2%F5%EE%E4');

$param['cat']='<td class="nav.*?</td>|<span class="nav">(?!<a href="posting).*?</span>';

$move[]=array('dir'=>'/home/user/Films'); // Default
$move[]=array('dir'=>'/home/user/Serials','param'=>'cat','val'=>'Документальные фильмы');


$badname="Torrents";


/**
 * Main
 */
main();


function main(){
	global $argc,$argv;

	nlog(print_r($argv,1));

	if($argc==1) die("Usage: $argv[0] [torrent_path] [torrent_name] [torrent_comment_url]\n\n");

	list(,$t_path,$t_name,$t_url) = $argv;

	$url = urldecode(str_replace('VRS24mrker', '', $t_url));

	nlog("$t_path:\t$url");

	$new_path = getNewPath($url,$t_path);

	// -s = символьные ссылки плюсы:можно ссылаться на каталог или другой раздел
	// exec("ln -s \"$t_path\" \"$new_path\"");

	// -n = не перезаписывать существующие файлы
	// -f = перезаписывать не спрашивая
	exec("mv -n \"$t_path\" \"$new_path\"");

	// nlog("$t_path\t->\t$new_path");

}
function getNewPath($url,$t_path){
	global $login,$param,$move,$badname;

	// Логинимся
	foreach ($login as $val) {
		if(substr_count($url, $val['url'])) {
			echo "Login in {$val['url']}\n";
			post($val['login_url'],$val['login_post']);
		}
	}

	// Загружаем страницу из комментария (*.torrents)
	$ret = post($url);

	// Обрабатываем Title
	$title = getTitle($ret);
	$ext=$moviename=$year=$quality='';
	if(!is_dir($t_path)) $ext = '.'.array_pop ( explode ( '.', $t_path ) );
	if(preg_match('~^[^\[\]\\|/]+~si', $title, $m)) $moviename = trim($m[0]);
	if(preg_match('~[12][90]\d{2}~si', $title, $m)) $year = $m[0];
	if(preg_match('~DVDScr|TV-?Rip|SAT-?Rip|HDTV-?Rip|BDRip|BDRemux|HDRip|DVD-?Rip|WEB-?DLRip|DVB|DVD5|DVD9|CAMRip|CAM|Telesync|TS|Telecine|TC|Super-?TS|VHS-?Rip|720p|1080p~s', $title, $m)) $quality = $m[0];

	$new_name = "$moviename $year $quality$ext";
	if(trim($new_name)==''){ nlog("ERROR: empty title. ($t_path)"); exit; }
	if(!empty($badname) and preg_match('~'.$badname.'~si', $new_name)){ nlog("ERROR: bad name! ($t_path)"); exit; }


	// Подбор каталога в который будем перемещать файл
	$_path = $move[0]['dir']; // Default
	foreach ($param as $key => $val) {
		$m = $param_data[$key]=m($val,$ret);
		// echo "\n$val\n$m\n";
	}
	foreach ($move as $val) {
		$dir = @$val['dir'];
		$reg = @$val['val'];
		$subject = @$param_data[@$val['param']];
		if(!empty($dir) and !empty($reg) and !empty($subject) and preg_match('#' . preg_quote($reg,'#') . '#si', $subject)) $_path=$dir;
	}
	$new_path = "$_path/$new_name";

	nlog("$t_path\t->\t$new_path");

	echo "$title \n";
	echo "$moviename $year $quality\n";
	return $new_path;
}


/**
 * Функции
 */
function m($reg, $text, $isall = FALSE, $n = FALSE, $n1 = FALSE) {
     /**
     * Obertka dlya preg_match i preg_match_all
     * @param regular expression string <p>
     * The input regx string.
     * </p>
     * @param text string<p>
     * If the optional delimiter is specified, it
     * </p>
     * @param Use match_all [1/2] optoinal<p>
     * 1 = Use match_all
     * 2 = m_all whis PREG_SET_ORDER
     * </p>
     * @param Return $m[N] optoinal<p>
     * </p>
     *
     * @param Return $m[][N] optoinal<p>
     * </p>
     *
     * @return string or array.
     */

     $reg=addcslashes($reg,'#');

     if (FALSE === $isall) {
     	if(!preg_match ( '#' . $reg . '#si', $text, $m )) return FALSE;
          if (! (FALSE === $n)) {
               return $m [$n];
          } else {
               return (isset ( $m [1] ) ? $m [1] : $m [0]);
          }
     } else {

          if(!preg_match_all ( '#' . $reg . '#si', $text, $m, (2 === $isall ? PREG_SET_ORDER : PREG_PATTERN_ORDER) )) return FALSE;
          if (! (FALSE === $n)) {
               return $m [$n];
          } elseif (! (FALSE === $n) and ! (FALSE === $n1)) {
               return $m [$n] [$n1];
          } else {
               return $m;
          }
     }

}



function getTitle($ret){
	if(preg_match('~<title[^>]*>(.*?)</title>~si', $ret, $m)){
		if('Torrents.ru'==$m[1] and preg_match('~<h1[^>]*>(.*?)</h1>~si', $ret, $m)) return strip_tags($m[1]);// костыль
		return $m[1];
	}
	return false;
}

function nlog($str){
	echo "$str\n";
	file_put_contents(__DIR__.'/torrent_move.log', @date('Y-m-d H:i:s')."\t$str\n",FILE_APPEND);
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
	$headers[]="Accept-Charset: utf-8";
	$headers[]="Expect:";
	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt ( $ch, CURLOPT_ENCODING, 'gzip,deflate');

	$result = curl_exec($ch);

	curl_close($ch);

	if(preg_match('/charset=([^;"\\s]+|"[^;"]+")/i', $result, $m)){
		$result = iconv($m[1], 'UTF-8', $result);
	}

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
