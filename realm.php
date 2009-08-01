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

$icon_type = Array(
	0 => array(0,$lang_realm['normal']),
	1 => array(1,$lang_realm['pvp']),
	4 => array(4,$lang_realm['normal']),
	6 => array(6,$lang_realm['rp']),
	8 => array(8,$lang_realm['rppvp']),
	16 => array(16,$lang_realm['ffapvp']),
);
$timezone_type = Array(
	1 => array(1,$lang_realm['development']),
	2 => array(2,$lang_realm['united_states']),
	3 => array(3,$lang_realm['oceanic']),
	4 => array(4,$lang_realm['latin_america']),
	5 => array(5,$lang_realm['tournament']),
	6 => array(6,$lang_realm['korea']),
	8 => array(8,$lang_realm['english']),
	9 => array(9,$lang_realm['german']),
	10 => array(10,$lang_realm['french']),
	11 => array(11,$lang_realm['spanish']),
	12 => array(12,$lang_realm['russian']),
	14 => array(14,$lang_realm['taiwan']),
	16 => array(16,$lang_realm['china']),
	26 => array(26,$lang_realm['test_server']),
	28 => array(28,$lang_realm['qa_server']),
);


//####################################################################################################
// SHOW REALMS
//####################################################################################################
function show_realm() {
 global $lang_global, $lang_realm, $output, $realm_db, $user_name, $server, $realm_id, $icon_type, $timezone_type, $action_permission, $user_lvl;
valid_login($action_permission['read']);

$sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 $order_by = (isset($_GET['order_by'])) ? $sql->quote_smart($_GET['order_by']) : "name";
 $dir = (isset($_GET['dir'])) ? $sql->quote_smart($_GET['dir']) : 1;
 $order_dir = ($dir) ? "ASC" : "DESC";
 $dir = ($dir) ? 0 : 1;
 
 $result = $sql->query("SELECT `realmlist`.id AS rid,name,address,port,icon,color,timezone,
						(SELECT SUM(numchars) FROM realmcharacters WHERE realmid = rid)
						FROM `realmlist` ORDER BY $order_by $order_dir");
 $total_realms = $sql->num_rows($result);

 $output .= "<center><table class=\"top_hidden\">
       <tr><td>";
       if($user_lvl >= $action_permission['insert'])
		makebutton($lang_realm['add_realm'], "realm.php?action=add_realm",135);
		makebutton($lang_global['back'], "javascript:window.history.back()",135);
 $output .= "</td>
     <td align=\"right\">{$lang_realm['tot_realms']} : $total_realms</td></tr>
   </table>
   <table class=\"lined\">
   <tr>";
   if($user_lvl >= $action_permission['delete'])
   $output .="
	<th width=\"5%\">{$lang_global['delete_short']}</th>";
	$output .="<th width=\"40%\"><a href=\"realm.php?order_by=name&amp;dir=$dir\"".($order_by=='name' ? " class=\"$order_dir\"" : "").">{$lang_realm['name']}</a></th>
	<th width=\"5%\">{$lang_realm['online']}</th>
	<th width=\"10%\">{$lang_realm['tot_char']}</th>
	<th width=\"10%\"><a href=\"realm.php?order_by=address&amp;dir=$dir\"".($order_by=='address' ? " class=\"$order_dir\"" : "").">{$lang_realm['address']}</a></th>
	<th width=\"5%\"><a href=\"realm.php?order_by=port&amp;dir=$dir\"".($order_by=='port' ? " class=\"$order_dir\"" : "").">{$lang_realm['port']}</a></th>
	<th width=\"8%\"><a href=\"realm.php?order_by=icon&amp;dir=$dir\"".($order_by=='icon' ? " class=\"$order_dir\"" : "").">{$lang_realm['icon']}</a></th>
    <th width=\"5%\"><a href=\"realm.php?order_by=color&amp;dir=$dir\"".($order_by=='color' ? " class=\"$order_dir\"" : "").">{$lang_realm['color']}</a></th>
	<th width=\"7%\"><a href=\"realm.php?order_by=timezone&amp;dir=$dir\"".($order_by=='timezone' ? " class=\"$order_dir\"" : "").">{$lang_realm['timezone']}</a></th>
   </tr>";

 while ($realm = $sql->fetch_row($result)){
 if($user_lvl >= $action_permission['delete'])
	$output .= "<tr><td><a href=\"realm.php?action=del_realm&amp;id=$realm[0]\"><img src=\"img/aff_cross.png\" alt=\"\" /></a></td>";
	if (isset($server[$realm[0]]['game_port'])) {
	if($user_lvl >= $action_permission['update'])
		$output .= "<td><a href=\"realm.php?action=edit_realm&amp;id=$realm[0]\">$realm[1]</a></td>";
	else
	$output .= "<td>$realm[1]</td>";
		if (test_port($server[$realm[0]]['addr'],$server[$realm[0]]['game_port']))  $output .= "<td><img src=\"img/up.gif\" alt=\"\" /></td>";
			else $output .= "<td><img src=\"img/down.gif\" alt=\"\" /></td>";
	}
	else
	{  
	  $output .= "<td>";
	  if($user_lvl >= $action_permission['update'])
	    $output .= "<a href=\"realm.php?action=edit_realm&amp;id=$realm[0]\">$realm[1] (Not Configured yet)</a>";
	  else
	    $output .="$realm[1] (Not Configured yet)";
	 $output .="</td><td>***</td>";
	}
	 
  $output .= "<td>$realm[7]</td>
				<td>$realm[2]</td>
				<td>$realm[3]</td>
				<td>{$icon_type[$realm[4]][1]}</td>
				<td>$realm[5]</td>
				<td>{$timezone_type[$realm[6]][1]}</td>
			</tr>";
  }
 $output .= "</table><br /></center>";

 $sql->close();
}


//####################################################################################################
//  EDIT REALM
//####################################################################################################
function edit_realm() {
 global $lang_global, $lang_realm, $output, $realm_db, $icon_type, $timezone_type,$server, $action_permission, $user_lvl;
valid_login($action_permission['update']);

 if(!isset($_GET['id'])) redirect("realm.php?error=1");
 
 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 $id = $sql->quote_smart($_GET['id']);
 
 $result = $sql->query("SELECT realmlist.id AS rid,name,address,port,icon,color,timezone,
						(SELECT SUM(numchars) FROM realmcharacters WHERE realmid = rid)
						FROM realmlist WHERE id ='$id'");

 if ($realm = $sql->fetch_row($result)) {
	$output .= "<center>
	<fieldset class=\"half_frame\">
	<legend>{$lang_realm['edit_realm']}</legend>
    <form method=\"get\" action=\"realm.php\" name=\"form\">
	<input type=\"hidden\" name=\"action\" value=\"doedit_realm\">
	<input type=\"hidden\" name=\"id\" value=\"$id\">
	<table class=\"flat\">
      <tr>
        <td>{$lang_realm['id']}</td>
        <td>$realm[0]</td>
      </tr>
      <tr>
        <td>{$lang_realm['name']}</td>
        <td><input type=\"text\" name=\"new_name\" size=\"40\" maxlength=\"32\" value=\"$realm[1]\" /></td>
      </tr>
	  <tr>
        <td>{$lang_realm['address']}</td>
        <td><input type=\"text\" name=\"new_address\" size=\"40\" maxlength=\"32\" value=\"$realm[2]\" /></td>
      </tr>
      <tr>
        <td>{$lang_realm['port']}</td>
        <td><input type=\"text\" name=\"new_port\" size=\"40\" maxlength=\"5\" value=\"$realm[3]\" /></td>
      </tr>
	  <tr>
        <td>{$lang_realm['icon']}</td>
        <td><select name=\"new_icon\">";
			foreach ($icon_type as $icon){ 
					$output .= "<option value=\"{$icon[0]}\" ";
					if ($realm[4]==$icon[0]) $output .= "selected=\"selected\" ";
					$output .= ">{$icon[1]}</option>";
					}
			$output .= "</select></td>
      </tr>
      <tr>
        <td>{$lang_realm['color']}</td>
        <td><input type=\"text\" name=\"new_color\" size=\"40\" maxlength=\"3\" value=\"$realm[5]\" /></td>
      </tr>
      <tr>
        <td>{$lang_realm['timezone']}</td>
        <td><select name=\"new_timezone\">";
			foreach ($timezone_type as $zone){ 
					$output .= "<option value=\"{$zone[0]}\" ";
					if ($realm[6]==$zone[0]) $output .= "selected=\"selected\" ";
					$output .= ">{$zone[1]}</option>";
					}
			$output .= "</select></td>
      </tr>";
 if (isset($server[$realm[0]]['game_port'])){
	$output .= "<tr><td>{$lang_realm['status']}</td><td>";
 	$output .= (test_port($server[$realm[0]]['addr'],$server[$realm[0]]['game_port'])) ? $lang_realm['online'] : $lang_realm['offline']; 
	$output .= "</tr></td>
				<tr><td>";
	$output .= "<tr><td>{$lang_realm['tot_char']}</td><td>$realm[7]</td></tr>";
} else $output .= "<tr><td colspan=\"2\">{$lang_realm['conf_from_file']}</td><td>";

 $output .= "<tr><td>";
			makebutton($lang_realm['update'], "javascript:do_submit()",120);
 $output .= "</td><td>
		  <table class=\"hidden\">
          <tr><td>";
          if($user_lvl >= $action_permission['delete'])
			makebutton($lang_realm['delete'], "realm.php?action=del_realm&amp;id=$realm[0]",142);
			makebutton($lang_global['back'], "realm.php",142);
 $output .= "</td></tr>
        </table>";
	$output .= "</td></tr>
     </table>
     </form></fieldset><br /><br /></center>";
  } else error($lang_global['err_no_result']);
  
 $sql->close();
}


//####################################################################################################
//  DO EDIT REALM
//####################################################################################################
function doedit_realm() {
 global $realm_db, $action_permission;
valid_login($action_permission['update']);

 if( empty($_GET['new_name']) || empty($_GET['new_address']) || empty($_GET['new_port']) || empty($_GET['id']) ) {
   redirect("realm.php?error=1");
 } 
 
 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
 
 $id = $sql->quote_smart($_GET['id']);
 $new_name = $sql->quote_smart($_GET['new_name']);
 $new_address = $sql->quote_smart($_GET['new_address']);
 $new_port = $sql->quote_smart($_GET['new_port']);
 $new_icon = $sql->quote_smart($_GET['new_icon']);
 $new_color = $sql->quote_smart($_GET['new_color']);
 $new_timezone = $sql->quote_smart($_GET['new_timezone']);

 $query = $sql->query("UPDATE realmlist SET name='$new_name', address ='$new_address' , port ='$new_port', icon ='$new_icon', color ='$new_color', timezone ='$new_timezone' WHERE id = '$id'");

 if ($sql->affected_rows()) {
	$sql->close();
	redirect("realm.php?error=3");
    } else {
		$sql->close();
		redirect("realm.php?action=edit_realm&id=$id&error=4");
	}
}


//####################################################################################################
// DELETE REALM
//####################################################################################################
function del_realm() {
global $lang_realm, $lang_global, $output, $action_permission;
valid_login($action_permission['delete']);
 if(isset($_GET['id'])) $id = addslashes($_GET['id']);
	else redirect("realm.php?error=1");

 $output .= "<center><h1><font class=\"error\">{$lang_global['are_you_sure']}</font></h1><br />";
 $output .= "<font class=\"bold\">{$lang_realm['realm_id']}: $id<br />{$lang_global['will_be_erased']}</font><br /><br />";
 $output .= "<table class=\"hidden\">
          <tr>
            <td>";
			makebutton($lang_global['yes'], "realm.php?action=dodel_realm&amp;id=$id",120);
			makebutton($lang_global['no'], "realm.php",120);
 $output .= "</td>
          </tr>
        </table></center><br />";
}


//####################################################################################################
// DO DELETE REALM
//####################################################################################################
function dodel_realm() {
 global $realm_db, $action_permission;
valid_login($action_permission['delete']);
 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 if(isset($_GET['id'])) $id = $sql->quote_smart($_GET['id']);
	else redirect("realm.php?error=1");

 $sql->query("DELETE FROM realmlist WHERE id = '$id'");

 if ($sql->affected_rows()) {
	$sql->close();
	redirect("realm.php");
    } else {
		$sql->close();
		redirect("realm.php?error=2");
	}
}


//####################################################################################################
//  ADD NEW REALM
//####################################################################################################
function add_realm() {
 global $realm_db, $action_permission;
valid_login($action_permission['insert']);

 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

 $result = $sql->query("INSERT INTO realmlist (id, name, address, port, icon, color, timezone)
  VALUES (NULL,'MANGOS','127.0.0.1', 8085 ,0 ,0 ,1)");

 $sql->close();

 if ($result) redirect("realm.php");
	else redirect("realm.php?error=4");
}


//####################################################################################################
// SET REALM TO DEFAULT IN CMS
//####################################################################################################
function set_def_realm() {
 global $realm_db, $action_permission;
valid_login($action_permission['read']);

 $sql = new SQL;
 $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
 
 $id = (isset($_GET['id'])) ? $sql->quote_smart($_GET['id']) : 1;
 
 $result = $sql->query("SELECT id FROM realmlist WHERE id ='$id'");
 if ($sql->num_rows($result)) $_SESSION['realm_id'] = $id;
 $sql->close();

 $url = (isset($_GET['url'])) ? $_GET['url'] : "index.php";
 redirect($url);
}

//####################################################################################################
// MAIN
//####################################################################################################
$err = (isset($_GET['error'])) ? $_GET['error'] : NULL;

$output .= "<div class=\"top\">";
switch ($err) {
case 1:
   $output .= "<h1><font class=\"error\">{$lang_global['empty_fields']}</font></h1>";
   break;
case 2:
   $output .= "<h1><font class=\"error\">{$lang_realm['err_deleting']}</font></h1>";
   break;
case 3:
   $output .= "<h1><font class=\"error\">{$lang_realm['update_executed']}</font></h1>";
   break;
case 4:
   $output .= "<h1><font class=\"error\">{$lang_realm['update_err']}</font></h1>";
   break;
default: //no error
   $output .= "<h1>{$lang_realm['realm_data']}</h1>";
}
$output .= "</div>";

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($action) {
case "edit_realm": 
	edit_realm();
	break;
case "doedit_realm":
	doedit_realm(); 
	break;
case "del_realm":
	del_realm(); 
	break;
case "dodel_realm":
	dodel_realm(); 
	break;
case "add_realm":
	add_realm(); 
	break;
case "set_def_realm":
	set_def_realm(); 
	break;
default:
    show_realm();
}

require_once("footer.php");
?>
