<?php
function decodeasstime($s){
	sscanf($s, "%d:%d:%d.%d", $v1, $v2, $v3, $v4);
	return $v1 * 3600000 + $v2 * 60000 + $v3 * 1000 + $v4 * 10;
}
function encodeasstime($t){
	return sprintf("%01d:%02d:%02d.%02d",intval($t / 3600 / 1000), (intval($t / 60000) % 60), (intval($t / 1000) % 60), ($t % 1000) / 10);
}
function ie($k){ echo iconv("utf-8", "euc-kr", $k); }
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
$songs = array();
foreach(explode("\n", file_get_contents("songs.txt")) as $l){
	if($l == '') continue;
	list($style, $text, $change) = explode(":", $l);
	$songs[$style][$text] = $change;
}
// die(print_r($songs));

$kara_ed = parse_ass('Comment: 0,0:22:21.48,0:22:28.23,KaraokeED,,0,0,0,karaoke,{\k30}{\k24}ta{\k20}i{\k26}yo{\k46}u{\k0} {\k24}ga{\k0} {\k48}fu{\k23}i{\k0} {\k73}ni{\k0} {\k26}o{\k24}re{\k0} {\k42}wo{\k0} {\k46}i{\k23}za{\k54}na{\k116}u{\k30}
Comment: 0,0:22:28.56,0:22:35.77,KaraokeED,,0,0,0,karaoke,{\k30}{\k45}a{\k30}o{\k89}ku{\k0} {\k28}su{\k23}ki{\k27}to{\k25}o{\k67}ru{\k0} {\k26}u{\k22}mi{\k0} {\k25}ni{\k0} {\k45}a{\k24}i{\k0} {\k48}ni{\k0} {\k20}ki{\k117}ta{\k30}
Comment: 0,0:22:36.39,0:22:40.09,KaraokeED,,0,0,0,karaoke,{\k30}{\k17}yo{\k26}se{\k29}te{\k0} {\k22}wa{\k0} {\k27}ka{\k14}e{\k26}su{\k0} {\k29}sa{\k25}za{\k26}na{\k69}mi{\k30}
Comment: 0,0:22:40.18,0:22:44.10,KaraokeED,,0,0,0,karaoke,{\k30}{\k26}to{\k27}ma{\k20}ru{\k0} {\k24}ko{\k21}to{\k0} {\k26}na{\k21}i{\k0} {\k29}ME{\k21}LO{\k138}DY{\k30}
Comment: 0,0:22:44.10,0:22:51.47,KaraokeED,,0,0,0,karaoke,{\k30}{\k38}so{\k15}no{\k0} {\k28}su{\k45}ga{\k26}ta{\k0} {\k46}to{\k28}wa{\k0} {\k92}ni{\k0} {\k27}tsu{\k45}na{\k44}gu{\k0} {\k30}yo{\k40}u{\k0} {\k173}ni{\k30}
Comment: 0,0:22:51.76,0:22:58.68,KaraokeED,,0,0,0,karaoke,{\k30}{\k20}o{\k27}i{\k25}ka{\k45}ke{\k26}te{\k0} {\k49}yu{\k29}ku{\k0} {\k88}yo{\k0} {\k24}yu{\k44}me{\k0} {\k49}no{\k0} {\k52}yu{\k26}ku{\k134}e{\k30}
Comment: 0,0:22:58.68,0:23:02.82,KaraokeED,,0,0,0,karaoke,{\k30}{\k23}to{\k68}o{\k97}ku{\k0} {\k23}ki{\k25}ra{\k22}me{\k24}i{\k72}ta{\k30}
Comment: 0,0:23:02.22,0:23:06.39,KaraokeED,,0,0,0,karaoke,{\k30}{\k26}ka{\k20}ze{\k0} {\k28}no{\k0} {\k17}na{\k25}ka{\k0} {\k25}no{\k0} {\k24}P{\k24}RI{\k25}S{\k143}M{\k30}');

for($i=17; $i<=48; $i++){
	$ei=substr("00".$i, -2, 2);
	$f=parse_ass($o=file_get_contents("Dagwon.$ei.ass"));
	$res='';
	unset($eyecatch_endtime, $ed_start);
	foreach($f as $l){
		if($l['style'] == "SongED" && !isset($ed_start))
			$ed_start = $l['start'];
		if($l['linetype'] == 'Dialogue' && substr(strtolower($l['style']),0,4)=="song" && $l['name']==''){
			$l['name'] = 'en'; $res.=toass($l)."\n";
			$l['name'] = 'ko'; $l['text'] = $songs[$l['style']][$l['text']]; $res.=toass($l)."\r\n";
		}else{
			if($l['style'] == 'Eyecache'){
				if(!isset($eyecatch_endtime)){
					$eyecatch_endtime = $l['end'];
					$t2 = $eyecatch_endtime;
					$t1 = $t2 - 3920;
					$t3 = $t2 + ($i < 22 ? 2000 : 2800);
					$t4 = $t2 + 6000;
					$t1 = encodeasstime($t1);
					$t2 = encodeasstime($t2);
					$t3 = encodeasstime($t3);
					$t4 = encodeasstime($t4);
					$res .= "Dialogue: 0,$t1,$t2,CardSeriesTitleUp,en,0,0,0,,{\\fscx80\\fscy80\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c000000\\1a00)\\pos(462,268)}Yuusha Shirei
Dialogue: 0,$t1,$t2,CardSeriesTitleDown,en,0,0,0,,{\\fscx80\\fscy104\\frx-30\\pos(462,389)\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c3790D3\\1a00)}Dagwon
Dialogue: 0,$t1,$t2,CardSeriesTitleUp,ko,0,0,0,,{\\fscx80\\fscy80\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c000000\\1a00)\\pos(462,268)}용자지령
Dialogue: 0,$t1,$t2,CardSeriesTitleDown,ko,0,0,0,,{\\fscx80\\fscy104\\frx-30\\pos(462,389)\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c3790D3\\1a00)}다그온
Dialogue: 0,$t2,$t4,Eyecache,en,0,0,0,,Original Korean Subtitles by Jaebari (재바리)\\NTranslated to English by Subdivers\\NChecked by Great Exkaiser103
Dialogue: 0,$t2,$t4,Eyecache,ko,0,0,0,,자막 원본(번역): 재바리\\N수정: Subdivers
Dialogue: 0,$t3,$t4,CardSeriesTitleUp,en,0,0,0,,{\\fscx70\\fscy70\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c000000\\1a00)\\pos(485,315)}Yuusha Shirei
Dialogue: 0,$t3,$t4,CardSeriesTitleDown,en,0,0,0,,{\\fscx70\\fscy91\\frx-30\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c3790D3\\1a00)\\pos(485,425)}Dagwon
Dialogue: 0,$t3,$t4,CardSeriesTitleUp,ko,0,0,0,,{\\fscx70\\fscy70\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c000000\\1a00)\\pos(485,315)}용자지령
Dialogue: 0,$t3,$t4,CardSeriesTitleDown,ko,0,0,0,,{\\fscx70\\fscy91\\frx-30\\3aFF\\1ac0\\3cFFFFFF\\blur2\\t(0,230,\\3a00)\\t(230,700,\\blur0\\3c3790D3\\1a00)\\pos(485,425)}다그온\n";
				}
				/*
				if($l['text'] == '{\\an3\\fs32}Yuusha Shirei Dagwon'){
					$l['marginr'] = 65;
					$l['marginv'] = 40;
					$l['name'] = 'en'; $res.=toass($l)."\n";
					$l['marginr'] = 110;
					$l['name'] = 'ko'; $l['text'] = '{\\an3\\fs32}용자지령 다그온'; $res.=toass($l)."\r\n";
				}else{
					$l['name'] = 'en'; $res.=toass($l)."\n";
					$l['name'] = 'ko'; $l['text'] = '자막 원본(번역): 재바리\\N수정: Subdivers'; $res.=toass($l)."\r\n";
				}
				//*/
			}else{
				$res.=toass($l)."\n";
			}
		}
	}
	$ked = "";
	$edl_base = $kara_ed[0]['start'];
	foreach($kara_ed as $edl){
		$edl['start'] -= $edl_base - $ed_start;
		$edl['end'] -= $edl_base - $ed_start;
		$ked .= toass($edl) . "\n";
	}
	$res=str_replace("NNNNNN", $ei, file_get_contents("template.txt")) . $ked . $res;
	$res = preg_replace('/Dialogue: 0,([0-9:.]+),([0-9:.]+),CardSeriesTitleUp,,(.*[\n\r]+Dialogue: 0,)[0-9:.]+,[0-9:.]+,CardSeriesTitleDown,,(.*[\n\r]+?Dialogue: 0,)(?=[0-9:.]+,[0-9:.]+,Default).*$/im', 
		'Dialogue: 0,$1,$2,CardSeriesTitleUp,en,0,0,0,,{\fad(1460,500)}Yuusha Shirei
Dialogue: 0,$1,$2,CardSeriesTitleDown,en,0,0,0,,{\fad(1460,500)\frx-40}Dagwon
Dialogue: 0,$1,$2,Default,en,0,0,0,,{\fad(1460,500)\fs32\an9\pos(353,342)\shad0\bord2}Brave Command Dagwon
Dialogue: 0,$1,$2,CardSeriesTitleUp,ko,0,0,0,,{\fad(1460,500)}용자지령
Dialogue: 0,$1,$2,CardSeriesTitleDown,ko,0,0,0,,{\fad(1460,500)\frx-40}다그온', $res);
	$res = preg_replace('/Dialogue: 0,([0-9:.]+),([0-9:.]+),CardSeriesTitleUp,,(.*[\n\r]+Dialogue: 0,)[0-9:.]+,[0-9:.]+,CardSeriesTitleDown,,(.*[\n\r]+?Dialogue: 0,)(?=[0-9:.]+,[0-9:.]+,CardNextEpisode).*$/im', 
		'Dialogue: 0,$1,$2,CardSeriesTitleUp,en,0,0,0,,Yuusha Shirei
Dialogue: 0,$1,$2,CardSeriesTitleDown,en,0,0,0,,{\frx-30}Dagwon
Dialogue: 0,$1,$2,CardNextEpisode,en,0,0,0,,Preview for Next Episode
Dialogue: 0,$1,$2,CardSeriesTitleUp,ko,0,0,0,,용자지령
Dialogue: 0,$1,$2,CardSeriesTitleDown,ko,0,0,0,,{\frx-30}다그온
Dialogue: 0,$1,$2,CardNextEpisode,ko,0,0,0,,차회예고', $res);
	$res = str_replace("<br />", "\n", nl2br($res));
	if($res == $o) echo "S";
	else echo strlen($res) . "/". strlen($o) . "\n";
	if(!file_exists("s/Dagwon.$ei.ass") && rename("Dagwon.$ei.ass", "s/Dagwon.$ei.ass"))
		file_put_contents("Dagwon.$ei.ass", $res);
	// file_put_contents("Dagwon.$ei.new.ass", $res);
}

die();
print_r($songs);
foreach($songs as $style => &$d){
	$d = array_keys($d);
	foreach($d as $l)
		echo "$style:$l:\r\n";
	unset($d);
}