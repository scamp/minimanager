<?php
/*
 * Project Name: MiniManager for Mangos Server
 * Date: 17.10.2006 inital version (0.0.1a)
 * Author: Q.SA
 * Copyright: Q.SA
 * Email: *****
 * License: GNU General Public License v2(GPL)
 */

require_once("header.php");
valid_login(0);
require_once("scripts/id_tab.php");
require_once("scripts/get_lib.php");
require_once("scripts/bbcode_lib.php");

$sql = new SQL;
$sql->connect($mangos_db[$realm_id]['addr'], $mangos_db[$realm_id]['user'], $mangos_db[$realm_id]['pass'], $mangos_db[$realm_id]['name']);

$output .= "<div class=\"top\">";

if (test_port($server[$realm_id]['addr'],$server[$realm_id]['game_port'])) 
{
    $query = $sql->query("SELECT `starttime` FROM `uptime` ORDER BY `starttime` DESC LIMIT 1");
    $getuptime = mysql_fetch_row($query);
    $uptimetime = time() - $getuptime[0];
    $uptime = array
        (
            'd' => $uptimetime / 86400 % 7,
            'h' => $uptimetime / 3600 % 24,
            'm' => $uptimetime / 60 % 60,
            's' => $uptimetime / 1 % 60,
         );

    if ($uptime['d'] < 2) $sd = ""; else $sd = "s"; 
    if ($uptime['h'] < 2) $sh = ""; else $sh = "s"; 
    if ($uptime['m'] < 2) $sm = ""; else $sm = "s"; 
    if ($uptime['s'] < 2) $ss = ""; else $ss = "s"; 

    if ($uptime['d'] > 0) 
        $output .= "<h1><font color=\"#55aa55\">{$lang_index['realm']} ".get_realm_name($realm_id)." {$lang_index['online']} for " . $uptime['d'] . " day$sd " .  $uptime['h'] . " hour$sh " .  $uptime['m'] . " minute$sm and  " .  $uptime['s'] . " second$ss.</font></h1>";
    else 
    {
        if ($uptime['h'] > 0) 
            $output .= "<h1><font color=\"#55aa55\">{$lang_index['realm']} ".get_realm_name($realm_id)." {$lang_index['online']} for " .  $uptime['h'] . " hour$sh " .  $uptime['m'] . " minute$sm and  " .  $uptime['s'] . " second$ss.</font></h1>";
        else 
            $output .= "<h1><font color=\"#55aa55\">{$lang_index['realm']} ".get_realm_name($realm_id)." {$lang_index['online']} for " .  $uptime['m'] . " minute$sm and  " .  $uptime['s'] . " second$ss.</font></h1>";
    }

    $online = true;
} 
else 
{
    $output .= "<h1><font class=\"error\">{$lang_index['realm']} ".get_realm_name($realm_id)." {$lang_index['offline_or_let_high']}</font></h1>";
	$online = false;
}

//  This retrieves the actual database version from the database itself,  instead of hardcoding it into a string
$sql->connect($mangos_db[$realm_id]['addr'], $mangos_db[$realm_id]['user'], $mangos_db[$realm_id]['pass'], $mangos_db[$realm_id]['name']);
$query_db_version = $sql->query("SELECT version FROM db_version");
$db_rev = $sql->result($query_db_version, 0);
$output .= "Mangos: {$server[$realm_id]['rev']} Using DB: $db_rev</div>";
$sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
$sql->db($characters_db[$realm_id]['name']);

//MOTD part
$start = (isset($_GET['start'])) ? $sql->quote_smart($_GET['start']) : 0;

$query_1 = $sql->query("SELECT count(*) FROM bugreport");
$all_record = $sql->result($query_1, 0);

if ($user_lvl > 0) $output .= "<script type=\"text/javascript\">
	answerbox.btn_ok='{$lang_global['yes_low']}';
	answerbox.btn_cancel='{$lang_global['no']}';
	var del_motd = 'motd.php?action=delete_motd&amp;id=';
 </script>";
$output .= "<center>
			<table class=\"lined\">
			<tr>
			<th align=\"right\">";
if ($user_lvl) $output .= "<a href=\"motd.php?action=add_motd\">{$lang_index['add_motd']}</a>";
$output .= "</tr>";

if($all_record)
{
	$result = $sql->query("SELECT id, type, content FROM bugreport ORDER BY id DESC LIMIT $start, 3");
	while($post = $sql->fetch_row($result)){
		$output .= "<tr><td align=\"left\" class=\"large\"><blockquote>".bbcode2html($post[2])."</blockquote></td></tr>
					<tr><td align=\"right\">$post[1] ";
		if ($user_lvl > 0) $output .= "<img src=\"img/cross.png\" width=\"12\" height=\"12\" onclick=\"answerBox('{$lang_global['delete']}: <font color=white>{$post[0]}</font><br />{$lang_global['are_you_sure']}', del_motd + $post[0]);\" style=\"cursor:pointer;\" />
			<a href=\"motd.php?action=edit_motd&amp;id=$post[0]\"><img src=\"img/edit.png\" width=\"14\" height=\"14\" /></a>";
		$output .= "</th></tr>
					<tr><td class=\"hidden\"></td></tr>";
	}
   $output .= "<tr><td align=\"right\" class=\"hidden\">".generate_pagination("index.php?", $all_record, 3, $start)."</td></tr>";
 }
$output .= "</table><br />";


//print online chars
if ($online)
{
	$order_by = (isset($_GET['order_by'])) ? $sql->quote_smart($_GET['order_by']) : "name";

	$dir = (isset($_GET['dir'])) ? $sql->quote_smart($_GET['dir']) : 1;
	$order_dir = ($dir) ? "ASC" : "DESC";
	$dir = ($dir) ? 0 : 1;

	$result = $sql->query("SELECT count(*) FROM `characters` WHERE `online`= 1");
	$total_online = $sql->result($result, 0);
    
    $order_side = "";
	if( !$user_lvl && !$server[$realm_id]['both_factions'])
    {
		$result = $sql->query("SELECT race FROM `characters` WHERE account = '$user_id' AND totaltime = (SELECT MAX(totaltime) FROM `characters` WHERE account = '$user_id') LIMIT 1");
		if ($sql->num_rows($result))
        {
			$order_side = (in_array($sql->result($result, 0, 'race'),array(2,5,6,8,10))) ?
			" AND race IN (2,5,6,8,10) " : " AND race IN (1,3,4,7,11) ";
		} 
	} 

	require_once("scripts/defines.php");
    
	$result = $sql->query("SELECT guid,name,race,class,zone,map,
            CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_POINTS+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,
            CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
            account,
            CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_GUILD_ID+1)."), ' ', -1) AS UNSIGNED) as GNAME,
            mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(36+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender
            FROM `characters` WHERE `online`= 1 $order_side ORDER BY $order_by $order_dir");

	$output .= "
	<font class=\"bold\">{$lang_index['tot_users_online']} : $total_online</font><br /><br />
	<table class=\"lined\">
	<tr>
	<th width=\"20%\"><a href=\"index.php?order_by=name&amp;dir=$dir\"".($order_by=='name' ? " class=\"$order_dir\"" : "").">{$lang_index['name']}</a></th>
	<th width=\"5%\"><a href=\"index.php?order_by=race&amp;dir=$dir\"".($order_by=='race' ? " class=\"$order_dir\"" : "").">{$lang_index['race']}</a></th>
	<th width=\"5%\"><a href=\"index.php?order_by=class&amp;dir=$dir\"".($order_by=='class' ? " class=\"$order_dir\"" : "").">{$lang_index['class']}</a></th>
	<th width=\"5%\"><a href=\"index.php?order_by=level&amp;dir=$dir\"".($order_by=='level' ? " class=\"$order_dir\"" : "").">{$lang_index['level']}</a></th>
      <th width=\"5%\"><a href=\"index.php?order_by=highest_rank&amp;dir=$dir\"".($order_by=='highest_rank' ? " class=\"$order_dir\"" : "").">{$lang_index['rank']}</a></th>
	<th width=\"15%\"><a href=\"index.php?order_by=GNAME&amp;dir=$dir\"".($order_by=='GNAME' ? " class=\"$order_dir\"" : "").">Guild</a></th>
	<th width=\"20%\"><a href=\"index.php?order_by=map&amp;dir=$dir\"".($order_by=='map' ? " class=\"$order_dir\"" : "").">{$lang_index['map']}</a></th>
	<th width=\"25%\"><a href=\"index.php?order_by=zone&amp;dir=$dir\"".($order_by=='zone' ? " class=\"$order_dir\"" : "").">{$lang_index['zone']}</th>
	</tr>";

	require_once("scripts/id_tab.php");

	while ($char = $sql->fetch_row($result))
    {
	    $accid = $char[8];

        $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
		$gmlvl = $sql->query("SELECT `gmlevel` FROM `account` WHERE `id`=$accid");
		$gml = $sql->fetch_row($gmlvl);
		$gm = $gml[0];

        $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
		$guild_name = $sql->fetch_row($sql->query("SELECT `name` FROM `guild` WHERE `guildid`={$char[9]}"));


    $level = $char[7];

    if($level < 10)
      $lev = '<font color="#FFFFFF">'.$level.'</font>';
    else if($level < 20)
      $lev = '<font color="#858585">'.$level.'</font>';
    else if($level < 30)
      $lev = '<font color="#339900">'.$level.'</font>';
    else if($level < 40)
      $lev = '<font color="#3300CC">'.$level.'</font>';
    else if($level < 50)
      $lev = '<font color="#C552FF">'.$level.'</font>';
    else if($level < 60)
      $lev = '<font color="#FF8000">'.$level.'</font>';
    else if($level < 70)
      $lev = '<font color="#FFF280">'.$level.'</font>';  
    else if($level < 80)
      $lev = '<font color="#FF0000">'.$level.'</font>';  
    else
      $lev = '<font color="#000000">'.$level.'</font>';

		$output .= "<tr>
		 <td><a href=\"char.php?id=$char[0]\"><span onmousemove='toolTip(\"".get_player_user_level($gm)."\",\"item_tooltip\")' onmouseout='toolTip()'>$char[1]</span></a></td>
         <td><img src='img/c_icons/{$char[2]}-{$char[10]}.gif' onmousemove='toolTip(\"".get_player_race($char[2])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
		 <td><img src='img/c_icons/{$char[3]}.gif' onmousemove='toolTip(\"".get_player_class($char[3])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
		 <td>$lev</td>
		 <td><span onmouseover='toolTip(\"".$CHAR_RANK[$CHAR_RACE[$char[2]][1]][pvp_ranks($char[6])]."\",\"item_tooltip\")' onmouseout='toolTip()' style='color: white;'><img src='img/ranks/rank".pvp_ranks($char[6],$CHAR_RACE[$char[2]][1]).".gif'></span></td>
		 <td><a href=\"guild.php?action=view_guild&amp;error=3&amp;id=$char[9]\">$guild_name[0]</a></td>
 		 <td>".get_map_name($char[5])."</td>
		 <td>".get_zone_name($char[4])."</td>
         </tr>";
	}
   $output .= "</table><br /></center>";
}

$sql->close();

require_once("footer.php");
?>