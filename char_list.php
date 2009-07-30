<?php
/*
 * Project Name: MiniManager for Mangos/Trinity Server
 * Date: 17.10.2006 inital version (0.0.1a)
 * Author: Q.SA
 * Copyright: Q.SA
 * Email: *****
 * License: GNU General Public License v2(GPL)
 */

require_once("header.php");
valid_login($action_permission['read']);
require_once("scripts/id_tab.php");
require_once("scripts/defines.php");

//########################################################################################################################
//  BROWSE  CHARS
//########################################################################################################################
function browse_chars() {
 global $lang_char_list, $lang_global, $output, $realm_db, $mmfpm_db, $characters_db, $realm_id, $itemperpage,
		$user_lvl,$user_name;

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);

 $start = (isset($_GET['start'])) ? $sql->quote_smart($_GET['start']) : 0;
 $order_by = (isset($_GET['order_by'])) ? $sql->quote_smart($_GET['order_by']) :"guid";

 $dir = (isset($_GET['dir'])) ? $sql->quote_smart($_GET['dir']) : 1;
 $order_dir = ($dir) ? "ASC" : "DESC";
 $dir = ($dir) ? 0 : 1;

 $query_1 = $sql->query("SELECT count(*) FROM `characters`");
 $all_record = $sql->result($query_1,0);

 $query = $sql->query("SELECT guid,name,account,race,class,zone,map,
		CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,
		online,CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
		mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender, logout_time
		FROM `characters` ORDER BY $order_by $order_dir LIMIT $start, $itemperpage");
 $this_page = $sql->num_rows($query) or die(error($lang_global['err_no_result']));

//==========================top tage navigaion starts here========================
  $output .="<script type=\"text/javascript\" src=\"js/check.js\"></script>
			<center><table class=\"top_hidden\">
			<tr><td>
				<table class=\"hidden\">
					<tr><td>";
 makebutton($lang_char_list['cleanup'], "cleanup.php", 110);
 makebutton($lang_global['back'], "javascript:window.history.back()", 110);

 $output .= "</td><td>
		<form action=\"char_list.php\" method=\"get\" name=\"form\">
	   <input type=\"hidden\" name=\"action\" value=\"search\" />
	   <input type=\"hidden\" name=\"error\" value=\"3\" />
	   <input type=\"text\" size=\"14\" maxlength=\"50\" name=\"search_value\" />
	   <select name=\"search_by\">
	    <option value=\"name\">{$lang_char_list['by_name']}</option>
	    <option value=\"guid\">{$lang_char_list['by_id']}</option>
		<option value=\"account\">{$lang_char_list['by_account']}</option>
		<option value=\"level\">{$lang_char_list['by_level']}</option>
		<option value=\"greater_level\">{$lang_char_list['greater_level']}</option>
		<option value=\"guild\">{$lang_char_list['by_guild']}</option>
		<option value=\"race\">{$lang_char_list['by_race_id']}</option>
		<option value=\"class\">{$lang_char_list['by_class_id']}</option>
		<option value=\"map\">{$lang_char_list['by_map_id']}</option>
		<option value=\"highest_rank\">{$lang_char_list['by_honor_kills']}</option>
		<option value=\"greater_rank\">{$lang_char_list['greater_honor_kills']}</option>
		<option value=\"online\">{$lang_char_list['by_online']}</option>
		<option value=\"gold\">{$lang_char_list['chars_gold']}</option>
		<option value=\"item\">{$lang_char_list['by_item']}</option>
	   </select>
	   </form></td><td>";
	   makebutton($lang_global['search'], "javascript:do_submit()",80);
 $output .= "</td></tr></table></td>
		<td align=\"right\" width=\"20%\">";
  $output .= generate_pagination("char_list.php?action=browse_chars&amp;order_by=$order_by&amp;dir=".!$dir, $all_record, $itemperpage, $start);
  $output .= "</td></tr></table>";
//==========================top tage navigaion ENDS here ========================

 $output .= "<form method=\"get\" action=\"char_list.php\" name=\"form1\">
	<input type=\"hidden\" name=\"action\" value=\"del_char_form\" />
	<input type=\"hidden\" name=\"start\" value=\"$start\" />
  <table class=\"lined\">
   <tr>
	<th width=\"5%\"><input name=\"allbox\" type=\"checkbox\" value=\"Check All\" onclick=\"CheckAll(document.form1);\" /></th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=guid&amp;start=$start&amp;dir=$dir\">".($order_by=='guid' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['id']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?order_by=name&amp;start=$start&amp;dir=$dir\">".($order_by=='name' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['char_name']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?order_by=account&amp;start=$start&amp;dir=$dir\">".($order_by=='account' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['account']}</a></th>
    <th width=\"5%\"><a href=\"char_list.php?order_by=race&amp;start=$start&amp;dir=$dir\">".($order_by=='race' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['race']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=class&amp;start=$start&amp;dir=$dir\">".($order_by=='class' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['class']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=level&amp;start=$start&amp;dir=$dir\">".($order_by=='level' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['level']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?order_by=map&amp;start=$start&amp;dir=$dir\">".($order_by=='map' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['map']}</a></th>
	<th width=\"15%\">{$lang_char_list['zone']}</th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=highest_rank&amp;start=$start&amp;dir=$dir\">".($order_by=='highest_rank' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['honor_kills']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=logout_time&amp;start=$start&amp;dir=$dir\">".($order_by=='logout_time' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."Last Seen</a></th>
	<th width=\"5%\"><a href=\"char_list.php?order_by=online&amp;start=$start&amp;dir=$dir\">".($order_by=='online' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['online']}</a></th>
	<th width=\"5%\">{$lang_global['country']}</th>
  </tr>";

 $looping = ($this_page < $itemperpage) ? $this_page : $itemperpage;

 for ($i=1; $i<=$looping; $i++)	{
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
    $char = $sql->fetch_row($query) or die(error($lang_global['err_no_user']));
 //to disalow lower lvl gm to  view accounts of other gms
	$result = $sql->query("SELECT gmlevel,username FROM account WHERE id ='$char[2]'");
	$owner_gmlvl = $sql->result($result, 0, 'gmlevel');
	$owner_acc_name = $sql->result($result, 0, 'username');
	$lastseen = date("Y-m-d G:i:s", $char[11]);

    $level = $char[9];

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

        $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
		$loc = $sql->query("SELECT `last_ip` FROM `account` WHERE `id`='$char[2]';");
		$location = $sql->fetch_row($loc);
		$ip = $location[0];

        $sql->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);
	   	$nation = $sql->query("SELECT c.code, c.country FROM ip2nationCountries c, ip2nation i WHERE i.ip < INET_ATON('".$ip."') AND c.code = i.country ORDER BY i.ip DESC LIMIT 0,1;");
		$country = $sql->fetch_row($nation);

	if (($user_lvl >= $owner_gmlvl)||($owner_acc_name == $user_name)){
		 $output .= "<tr>";
		 if (($user_lvl > $owner_gmlvl)||($owner_acc_name == $user_name))
			$output .= "<td><input type=\"checkbox\" name=\"check[]\" value=\"$char[0]\" onclick=\"CheckCheckAll(document.form1);\" /></td>";
			else $output .= "<td></td>";
			$output .= "<td>$char[0]</td>
			<td><a href=\"char.php?id=$char[0]\">".htmlentities($char[1])."</a></td>
			<td><a href=\"user.php?action=edit_user&amp;error=11&amp;id=$char[2]\">$owner_acc_name</a></td>
			<td><img src='img/c_icons/{$char[3]}-{$char[10]}.gif' onmousemove='toolTip(\"".get_player_race($char[3])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
			<td><img src='img/c_icons/{$char[4]}.gif' onmousemove='toolTip(\"".get_player_class($char[4])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
			<td>$lev</td>
			<td>".get_map_name($char[6])."</td>
			<td>".get_zone_name($char[5])."</td>
			<td>$char[7]</td>
			<td class=\"small\">$lastseen</td>
			<td>".(($char[8]) ? "<img src=\"img/up.gif\" alt=\"\" />" : "-")."</td>
			<td>".(($country[0]) ? "<img src='img/flags/".$country[0].".png' onmousemove='toolTip(\"".($country[1])."\",\"item_tooltip\")' onmouseout='toolTip()' alt=\"\" />" : "-")."</td>
            </tr>";
	}else{
		 $output .= "<tr><td>*</td><td>***</td><td>You</td><td>Have</td><td>No</td>
			<td class=\"small\">Permission</td><td>to</td><td>View</td><td>this</td><td>Data</td><td>***</td><td>*</td></tr>";
		 }
 }

 $output .= "<tr><td colspan=\"12\" class=\"hidden\"><br /></td></tr>
	<tr>
		<td colspan=\"5\" align=\"left\" class=\"hidden\">";
			makebutton($lang_char_list['del_selected_chars'], "javascript:do_submit('form1',0)",220);
 $output .= "</td>
      <td colspan=\"6\" align=\"right\" class=\"hidden\">{$lang_char_list['tot_chars']} : $all_record</td>
	 </tr>
 </table>
 </form><br /></center>";

 $sql->close();
}


//########################################################################################################################
//  SEARCH
//########################################################################################################################
function search() {
 global $lang_char_list, $lang_global, $output, $realm_db, $mmfpm_db, $characters_db, $realm_id, $itemperpage,
		$user_lvl, $user_name, $sql_search_limit;

 if(!isset($_GET['search_value'])) redirect("char_list.php?error=2");

 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 $search_value = $sql->quote_smart($_GET['search_value']);
 $search_by = (isset($_GET['search_by'])) ? $sql->quote_smart($_GET['search_by']) : "name";
 $search_menu = array('name', 'guid', 'account', 'level', 'greater_level', 'guild', 'race', 'class', 'map', 'highest_rank', 'greater_rank', 'online', 'gold', 'item');
 if (!array_key_exists($search_by, $search_menu)) $search_by = 'name';
 $order_by = (isset($_GET['order_by'])) ? $sql->quote_smart($_GET['order_by']) : "guid";

 $dir = (isset($_GET['dir'])) ? $sql->quote_smart($_GET['dir']) : 1;
 $order_dir = ($dir) ? "ASC" : "DESC";
 $dir = ($dir) ? 0 : 1;

 switch ($search_by){
 //need to get the acc id from other table since input comes as name
 case "account":
	$sql->db($realm_db['name']);
	$result = $sql->query("SELECT id FROM account WHERE username LIKE '%$search_value%' LIMIT $sql_search_limit");

	$where_out = " account IN (0 ";
    while ($char = $sql->fetch_row($result)) {
    	$where_out .= " ,";
    	$where_out .= $char[0];
    	};
	$where_out .= ") ";

	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender
	FROM `characters` WHERE $where_out ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

 case "level":
	if (!is_numeric($search_value)) $search_value = 1;
	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) = $search_value ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

case "greater_level":
	if (!is_numeric($search_value)) $search_value = 1;
	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) > $search_value ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

 case "gold":
	if (!is_numeric($search_value)) $search_value = 1;
	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_GOLD+1)."), ' ', -1) > $search_value ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

 case "guild":
	$sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
	$result = $sql->query("SELECT guildid FROM guild WHERE name LIKE '%$search_value%'");
	$guildid = $sql->result($result, 0, 'guildid');

	$Q1 = "SELECT guid FROM guild_member WHERE guildid = ";
	$Q1 .= $guildid;

	$result = $sql->query($Q1);

	$where_out = "guid IN (0 ";
    while ($char = $sql->fetch_row($result)) {
    	$where_out .= " ,";
    	$where_out .= $char[0];
    	};
	$where_out .= ") ";

	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE $where_out ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

  case "item":
	$sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
	$result = $sql->query("SELECT guid FROM character_inventory WHERE item_template = '$search_value'");

	$where_out = "guid IN (0 ";
    while ($char = $sql->fetch_row($result)) {
    	$where_out .= " ,";
    	$where_out .= $char[0];
    	};
	$where_out .= ") ";

	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE $where_out ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

 case "greater_rank":
	if (!is_numeric($search_value)) $search_value = 0;
	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) > $search_value ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;
  case "highest_rank":
	if (!is_numeric($search_value)) $search_value = 0;
	$sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) = $search_value ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 break;

 default:
    $sql_query = "SELECT guid,name,account,race,class,zone,map,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_HONOR_KILL+1)."), ' ', -1) AS UNSIGNED) AS highest_rank,online,
	CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
	mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender FROM `characters`
	WHERE $search_by LIKE '%$search_value%' ORDER BY $order_by $order_dir LIMIT $sql_search_limit";
 }

 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);

 $query = $sql->query($sql_query);
 $total_found = $sql->num_rows($query);

//==========================top tage navigaion starts here========================
  $output .="<script type=\"text/javascript\" src=\"js/check.js\"></script>
			<center><table class=\"top_hidden\">
			<tr><td>";
			makebutton($lang_char_list['characters'], "char_list.php", 120);
			makebutton($lang_char_list['cleanup'], "cleanup.php", 120);
			makebutton($lang_global['back'], "javascript:window.history.back()", 120);
  $output .= "</td><td>
		<form action=\"char_list.php\" method=\"get\" name=\"form\">
	   	<input type=\"hidden\" name=\"action\" value=\"search\" />
	   	<input type=\"hidden\" name=\"error\" value=\"3\" />
	   	<input type=\"text\" size=\"17\" maxlength=\"50\" name=\"search_value\" />
	   	<select name=\"search_by\">
			<option value=\"name\">{$lang_char_list['by_name']}</option>
			<option value=\"guid\">{$lang_char_list['by_id']}</option>
			<option value=\"account\">{$lang_char_list['by_account']}</option>
			<option value=\"level\">{$lang_char_list['by_level']}</option>
			<option value=\"greater_level\">{$lang_char_list['greater_level']}</option>
			<option value=\"guild\">{$lang_char_list['by_guild']}</option>
			<option value=\"race\">{$lang_char_list['by_race_id']}</option>
			<option value=\"class\">{$lang_char_list['by_class_id']}</option>
			<option value=\"map\">{$lang_char_list['by_map_id']}</option>
			<option value=\"highest_rank\">{$lang_char_list['by_honor_kills']}</option>
			<option value=\"greater_rank\">{$lang_char_list['greater_honor_kills']}</option>
			<option value=\"online\">{$lang_char_list['by_online']}</option>
			<option value=\"gold\">{$lang_char_list['chars_gold']}</option>
			<option value=\"item\">{$lang_char_list['by_item']}</option>
	   	</select></form></td>
		<td>";
	   makebutton($lang_global['search'], "javascript:do_submit()",100);
 $output .= "</td>
	</tr></table>";
//==========================top tage navigaion ENDS here ========================

  $output .= "<form method=\"get\" action=\"char_list.php\" name=\"form1\">
	 <input type=\"hidden\" name=\"action\" value=\"del_char_form\" />
   <table class=\"lined\">
   <tr>
	<th width=\"5%\"><input name=\"allbox\" type=\"checkbox\" value=\"Check All\" onclick=\"CheckAll(document.form1);\" /></td>
	<th width=\"5%\><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=guid&amp;dir=$dir\">".($order_by=='guid' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['id']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=name&amp;dir=$dir\">".($order_by=='name' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['char_name']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=account&amp;dir=$dir\">".($order_by=='account' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['account']}</a></th>
    <th width=\"5%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=race&amp;dir=$dir\">".($order_by=='race' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['race']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=class&amp;dir=$dir\">".($order_by=='class' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['class']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=level&amp;dir=$dir\">".($order_by=='level' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['level']}</a></th>
	<th width=\"15%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=map&amp;dir=$dir\">".($order_by=='map' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['map']}</a></th>
	<th width=\"15%\">{$lang_char_list['zone']}</th>
	<th width=\"5%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=highest_rank&amp;dir=$dir\">".($order_by=='highest_rank' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['honor_kills']}</a></th>
	<th width=\"5%\"><a href=\"char_list.php?action=search&amp;error=3&amp;search_value=$search_value&amp;search_by=$search_by&amp;order_by=online&amp;dir=$dir\">".($order_by=='online' ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" /> " : "")."{$lang_char_list['online']}</a></th>
	<th width=\"5%\">{$lang_global['country']}</th>
  </tr>";

 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 while ($char = $sql->fetch_row($query)){

	$result = $sql->query("SELECT gmlevel,username FROM account WHERE id ='$char[2]'");
	$owner_gmlvl = $sql->result($result, 0, 'gmlevel');
	$owner_acc_name = $sql->result($result, 0, 'username');

    $level = $char[9];

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

        $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
		$loc = $sql->query("SELECT `last_ip` FROM `account` WHERE `id`='$char[2]';");
		$location = $sql->fetch_row($loc);
		$ip = $location[0];

        $sql->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);
	   	$nation = $sql->query("SELECT c.code, c.country FROM ip2nationCountries c, ip2nation i WHERE i.ip < INET_ATON('".$ip."') AND c.code = i.country ORDER BY i.ip DESC LIMIT 0,1;");
		$country = $sql->fetch_row($nation);

  if (($user_lvl >= $owner_gmlvl)||($owner_acc_name == $user_name)){
		 $output .= "<tr>";
		 if (($user_lvl > $owner_gmlvl)||($owner_acc_name == $user_name))$output .= "
   		    <td><input type=\"checkbox\" name=\"check[]\" value=\"$char[0]\" onclick=\"CheckCheckAll(document.form1);\" /></td>";
			else $output .= "<td></td>";
		$output .= "
   	    <td>$char[0]</td>
        <td><a href=\"char.php?id=$char[0]\">$char[1]</a></td>
		<td><a href=\"user.php?action=edit_user&amp;error=11&amp;id=$char[2]\">$owner_acc_name</a></td>
		<td><img src='img/c_icons/{$char[3]}-{$char[10]}.gif' onmousemove='toolTip(\"".get_player_race($char[3])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
		<td><img src='img/c_icons/{$char[4]}.gif' onmousemove='toolTip(\"".get_player_class($char[4])."\",\"item_tooltip\")' onmouseout='toolTip()' /></td>
		<td>$lev</td>
		<td>".get_map_name($char[6])."</td>
		<td>".get_zone_name($char[5])."</td>
		<td>$char[7]</td>
		<td>".(($char[8]) ? "<img src=\"img/up.gif\" alt=\"\" />" : "-")."</td>
		<td>".(($country[0]) ? "<img src='img/flags/".$country[0].".png' onmousemove='toolTip(\"".($country[1])."\",\"item_tooltip\")' onmouseout='toolTip()' alt=\"\" />" : "-")."</td>
       </tr>";
	}else{
		 $output .= "<tr><td>*</td><td>***</td><td>You</td><td>Have</td><td>No</td>
			<td class=\"small\">Permission</td><td>to</td><td>View</td><td>this</td><td>Data</td><td>***</td><td>*</td></tr>";
		}
}

$output .= "<tr><td colspan=\"12\" class=\"hidden\"><br /></td></tr>
	<tr>
		<td colspan=\"5\" align=\"left\" class=\"hidden\">";
		makebutton($lang_char_list['del_selected_chars'], "javascript:do_submit('form1',0)",220);
$output .= "</td>
      <td colspan=\"6\" align=\"right\" class=\"hidden\">{$lang_char_list['tot_found']} : $total_found : {$lang_global['limit']} $sql_search_limit</td>
	 </tr>
 </table>
 </form><br /></center>";

 $sql->close();
}


//########################################################################################################################
//  DELETE CHAR
//########################################################################################################################
function del_char_form() {
global  $lang_global, $lang_char_list, $output, $realm_id, $characters_db;
 if(isset($_GET['check'])) $check = $_GET['check'];
	else redirect("char_list.php?error=1");

 $output .= "<center><img src=\"img/warn_red.gif\" width=\"48\" height=\"48\" alt=\"\" />
			<h1><font class=\"error\">{$lang_global['are_you_sure']}</font></h1><br />
			<font class=\"bold\">{$lang_char_list['char_ids']}: ";

 $pass_array = "";

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);

 for ($i=0; $i<count($check); $i++){
	$name = $sql->result($sql->query("SELECT name FROM `characters` WHERE guid = {$check[$i]}"),0);
	$output .= "<a href=\"char.php?id=$check[$i]\" target=\"_blank\">$name, </a>";
	$pass_array .= "&amp;check%5B%5D=$check[$i]";
	}

 $sql->close();

 $output .= "<br />{$lang_global['will_be_erased']}</font><br /><br />
		<table class=\"hidden\">
          <tr><td>";
			makebutton($lang_global['yes'], "char_list.php?action=dodel_char$pass_array",120);
			makebutton($lang_global['no'], "char_list.php",120);
 $output .= "</td>
          </tr>
        </table></center>";
}


//########################################################################################################################
//  DO DELETE CHARS
//########################################################################################################################
function dodel_char() {
 global $lang_global, $lang_char_list, $output, $realm_db, $characters_db, $realm_id, $user_lvl,
		$tab_del_user_characters, $user_name;

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);

 if(isset($_GET['check'])) $check = $sql->quote_smart($_GET['check']);
	else redirect("char_list.php?error=1");

 $deleted_chars = 0;
 require_once("./scripts/del_lib.php");

 for ($i=0; $i<count($check); $i++) {
    if ($check[$i] != "" ) {
		if (del_char($check[$i], $realm_id)) $deleted_chars++;
	}
}

 $sql->close();
 $output .= "<center>";
 if (!$deleted_chars) $output .= "<h1><font class=\"error\">{$lang_char_list['no_chars_del']}</font></h1>";
   else $output .= "<h1><font class=\"error\">{$lang_char_list['total']} <font color=blue>$deleted_chars</font> {$lang_char_list['chars_deleted']}</font></h1>";
 $output .= "<br /><br />";
 $output .= "<table class=\"hidden\">
				<tr><td>";
  makebutton($lang_char_list['back_browse_chars'], "char_list.php", 250);
  $output .= "</td></tr>
			</table><br /></center>";
}


//########################################################################################################################
// MAIN
//########################################################################################################################
$err = (isset($_GET['error'])) ? $_GET['error'] : NULL;

$output .= "<div class=\"top\">";
switch ($err) {
case 1:
   $output .= "<h1><font class=\"error\">{$lang_global['empty_fields']}</font></h1>";
   break;
case 2:
   $output .= "<h1><font class=\"error\">{$lang_global['err_no_search_passed']}</font></h1>";
   break;
case 3:
   $output .= "<h1>{$lang_char_list['search_results']}</h1>";
   break;

default: //no error
    $output .= "<h1>{$lang_char_list['browse_chars']}</h1>";
}
$output .= "</div>";

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($action) {
case "browse_chars":
   browse_chars();
   break;
case "search":
   search();
   break;
case "del_char_form":
   del_char_form();
   break;
case "dodel_char":
   dodel_char();
   break;
default:
    browse_chars();
}

require_once("footer.php");
?>
