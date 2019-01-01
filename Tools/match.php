<?php
function decodesrttime($s){
	sscanf($s, "%d:%d:%d,%d", $v1, $v2, $v3, $v4);
	return $v1 * 3600000 + $v2 * 60000 + $v3 * 1000 + $v4;
}
function decodeasstime($s){
	sscanf($s, "%d:%d:%d.%d", $v1, $v2, $v3, $v4);
	return $v1 * 3600000 + $v2 * 60000 + $v3 * 1000 + $v4 * 10;
}
function encodeasstime($t){
	return sprintf("%01d:%02d:%02d.%02d",intval($t / 3600 / 1000), (intval($t / 60000) % 60), (intval($t / 1000) % 60), ($t % 1000) / 10);
}
function parse_srt($data){
	preg_match_all('/[0-9]+\r?\n(?<start>[0-9:,]+) --> (?<end>[0-9:,]+)\r?\n(?<text>.*?)\r?\n\r?\n/sim', $data, $res, PREG_SET_ORDER);
	$f=false;
	foreach($res as $key=>&$line){
		$line['text'] = preg_replace('/\s+/', ' ' , strip_tags(str_replace("&nbsp;", "  ", str_replace("<br />", "\\N", nl2br($line['text'])))));
		$strip=str_replace('\\N', '', preg_replace('/\s/','',$line['text']));
		//ie($line['text']);
		$line['start'] = decodesrttime($line['start']);
		$line['end'] = decodesrttime($line['end']);
		$line['length'] = $line['end'] - $line['start'];
		if(false!==strpos($strip,"勇者指令ダグオン용자지령다그온")) unset($res[$key]);
		else if(false!==strpos($strip,"용자지령다그온제")) unset($res[$key]);
		else if(false!==strpos($strip,"재바리")) unset($res[$key]);
		else{
			if(false!==strpos($strip,'다그온클럽~☆'))$f=true;
			if(false!==strpos($strip,'용자지령다그온엔딩테마'))$f=true;
			if($f)unset($res[$key]);
			if(false!==strpos($strip,'용자지령다그온차회예고')) $f=false;
		}
	}
	return array_values($res);
}
function ie($k){ echo iconv("utf-8", "euc-kr", $k); }
function fmt($k){
	$k=preg_replace('/^\s*(.*?)\s*$/', '$1', $k);
	return $k;
}
function parse_ass($data){
	preg_match_all('/^(?<linetype>Comment|Dialogue):\s*(?<layer>[^,]*?)\s*,\s*(?<start>[^,]*?)\s*,\s*(?<end>[^,]*?)\s*,\s*(?<style>[^,]*?)\s*,\s*(?<name>[^,]*?)\s*,\s*(?<marginl>[^,]*?)\s*,\s*(?<marginr>[^,]*?)\s*,\s*(?<marginv>[^,]*?)\s*,\s*(?<effect>[^,]*?)\s*,\s*(?<text>.*?)\s*$/uim', $data, $res, PREG_SET_ORDER);
	foreach($res as &$line){
		$line['start'] = decodeasstime($line['start']);
		$line['end'] = decodeasstime($line['end']);
		$line['length'] = $line['end'] - $line['start'];
	}
	return $res;
}
function toass($line){
	$line['end'] = encodeasstime($line['end']);
	$line['start'] = encodeasstime($line['start']);
	return "{$line['linetype']}: {$line['layer']},{$line['start']},{$line['end']},{$line['style']},{$line['name']},{$line['marginl']},{$line['marginr']},{$line['marginv']},{$line['effect']},{$line['text']}";
}
function closest($data, $time, $sfrom=0, $key = "start"){
	$a=$m= $sfrom; $b = count($data);
	while($a + 1 < $b){
		$m = ($a + $b) >> 1;
		if($data[$m][$key] == $time)
			return $m;
		else if($data[$m][$key] > $time)
			$b = $m;
		else
			$a = $m;
	}
	$a = max($sfrom, max($m-1, 0));
	for($i = $a; $i <= min($m + 1, count($data) - 1); $i++)
		if(abs($data[$i][$key] - $time) < abs($data[$a][$key] - $time))
			$a = $i;
	return $a;
}
function moveass($data, $ptr, $delta){
	for($i = 0; $ptr >= 0 && $ptr < count($data) && $i < $delta; $i++)
		do{
			$ptr += $delta>0?1:-1;
		}while($ptr >= 0 && $ptr < count($data) && (false === strpos("Default|CardEpisodeTitle|CardEpisodeSubtitle",$data[$ptr]['style']) || $data[$ptr]['linetype'] != "Dialogue"));
	return $ptr;
}
$ei=substr("00".$argv[1], -2, 2);
$f1 = "s/Daguon.TV.1996.DVDRip-Hi.x264.AC3.EP$ei.smi.srt";
$f2 = "Dagwon.$ei.ass";
if(!file_exists($f2) || !file_exists($f1)) die("not exist");
$f1 = parse_srt(file_get_contents($f1));
$f2 = parse_ass(strstr($template=file_get_contents($f2), "Brave Command Dagwon"));
$template = substr($template, 0, strpos($template, "\nComment: "));
$p1 = 0;
$p2 = moveass($f2, 0, 1);
$_ = 0;
$lastc=array("text"=>"");
$dl = 0;
$f2_orig = $f2;
foreach($f2 as &$v) $v['text'] = ''; unset($v);
$did = array();
while($p1 < count($f1) && $p2 < count($f2)){
	$c = &$f2[$p2];
	$next_p2 = moveass($f2, $p2, 1);
	$lastl = $c['start'] - $f1[$p1]['start'];
	$ptarget=closest($f1,$c['start'], $p1);
	$ptarget_end = closest($f1, $c['end'], $p1, 'end');
	while($p1 < count($f1) && $p1<$ptarget && $p1 < $ptarget_end){
		$lastc['text'] .= " " .  fmt($f1[$p1]['text']);
		$did[$p1] = 1;
		$lastl = $c['start'] - $f1[++$p1]['start'];
	}
	if(!isset($did[$p1]))
		$c['text'] .= " " . fmt($f1[$p1]['text']); $p1++;
	unset($lastc);
	$lastc = &$f2[$p2];
	for($i = $p1; $i < count($f1); $i++){
		$f1[$i]['start'] += $lastl;
		$f1[$i]['end'] += $lastl;
	}
	$p2 = $next_p2;
}
$res=$template."\r\nComment: 0,0:00:00.00,0:00:00.00,SETTINGS,,0,0,0,,lang:en,ko;complete:true;song:song.*\r\n";
foreach($f2 as $k=>&$v){
	$v['text']=str_replace("- ", "-", preg_replace('/\s+/', ' ', $v['text']));
	if($v['text']!='' && $v['text'] != $f2_orig[$k]['text']){
		$f2_orig[$k]['name'] = 'en'; $res .= toass($f2_orig[$k]) . "\n";
		$v['name'] = "ko";
		$v['text'] = trim($v['text']);
		$v['text'] = preg_replace("/!+/", "!", $v['text']);
		$v['text'] = str_replace("!?", "?!", $v['text']);
		if(substr($f2_orig[$k]['text'],0,1) == '{'){
			$v['text'] = substr($f2_orig[$k]['text'], 0, strpos($f2_orig[$k]['text'], '}')+1) . $v['text'];
		}
		$v['text'] = preg_replace('%^(.*?)\s*/\s*(.*?)$%sim', '-$1\\\\N-$2', $v['text']);
		$res .= toass($v) . "\n";
	}else{
		$res .= toass($f2_orig[$k]) . "\n";
	}
}
//die();
//file_put_contents("Dagwon.$ei.t.ass", $res);die();
if(!file_exists("s/Dagwon.$ei.ass") && rename("Dagwon.$ei.ass", "s/Dagwon.$ei.ass"))
	file_put_contents("Dagwon.$ei.ass", $res);