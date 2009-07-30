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
require_once("scripts/bbcode_lib.php");
valid_login($action_permission['read']);

//#####################################################################################################
// ADD MOTD
//#####################################################################################################
function add_motd(){
 global $lang_motd, $lang_global, $output;
 
 $output .= "<center>
			<form action=\"motd.php?action=do_add_motd\" method=\"post\" name=\"form\">		
			<table class=\"top_hidden\">
				<tr><td colspan=\"4\">";
					add_bbcode_editor();
 $output .= " </td></tr>
				<tr><td colspan=\"4\">
					<textarea id=\"msg\" name=\"msg\" rows=\"10\" cols=\"93\"></textarea>
				</td></tr>
				<tr>
					<td>{$lang_motd['post_rules']}</td>
					<td>";
					makebutton($lang_motd['post_motd'], "javascript:do_submit()",220);
$output .= "	<td/><td>";
					makebutton($lang_global['back'], "javascript:window.history.back()",220);
$output .= "	</td>
				</tr>
			</table>
			</form>
			<br /><br />
			</center>";
}


//#####################################################################################################
// DO ADD MOTD
//#####################################################################################################
function do_add_motd(){
 global  $lang_global, $characters_db, $realm_id, $user_name;
 
if (empty($_POST['msg'])) redirect("motd.php?error=1");

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
 
 $msg = $sql->quote_smart($_POST['msg']);

 if (strlen($msg) > 4096){
	$sql->close();
    redirect("motd.php?error=2");
   }
 
  $by = date("m/d/y H:i:s")." Posted by: $user_name";

 $sql->query("INSERT INTO bugreport (type, content) VALUES ('$by','$msg')");
 $sql->close();

 redirect("index.php");
}

//#####################################################################################################
// EDIT MOTD
//#####################################################################################################
function edit_motd(){
 global $lang_motd,$lang_global, $output, $characters_db, $realm_id;
 
 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
 
 if(isset($_GET['id'])) $id = $sql->quote_smart($_GET['id']);
	else redirect("motd.php?error=1");
 
 $result = $sql->query("SELECT content FROM bugreport WHERE id = '$id'");
 $msg = $sql->result($result, 0);
 $sql->close();
 
 $output .= "<center>
			<form action=\"motd.php?action=do_edit_motd\" method=\"post\" name=\"form\">
			<input type=\"hidden\" name=\"id\" value=\"$id\" />
			<table class=\"top_hidden\">
				<tr><td colspan=\"4\">";
					add_bbcode_editor();
 $output .= "</td></tr>
				<tr>
					<td colspan=\"4\">
					<textarea id=\"msg\" name=\"msg\" rows=\"10\" cols=\"93\">$msg</textarea>
					</td>
				</tr>
				<tr>
					<td>{$lang_motd['post_rules']}</td>
					<td>";
					makebutton($lang_motd['post_motd'], "javascript:do_submit()",220);
$output .= "	<td/><td>";
					makebutton($lang_global['back'], "javascript:window.history.back()",220);
$output .= "</td>
				</tr>
			</table>
			</form>
			<br /><br />
			</center>";
}


//#####################################################################################################
// DO EDIT MOTD
//#####################################################################################################
function do_edit_motd(){
 global  $lang_global, $characters_db, $realm_id, $user_name;
 
 if (empty($_POST['msg']) || empty($_POST['id'])) redirect("motd.php?error=1");

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
 
 $msg = $sql->quote_smart($_POST['msg']);
 $id = $sql->quote_smart($_POST['id']);
 
 $by = $sql->result($sql->query("SELECT type FROM bugreport WHERE id = '$id'"), 0, 'type');

 if (strlen($msg) > 4096){
	$sql->close();
    redirect("motd.php?error=2");
   }
 
 $by = split("<br />", $by, 2);
 $by = "{$by[0]}<br />".date("m/d/y H:i:s")." Edited by: $user_name";

 $sql->query("UPDATE bugreport SET type = '$by', content = '$msg' WHERE id = '$id'");
 $sql->close();

 redirect("index.php");
}


//#####################################################################################################
// DELETE MOTD
//#####################################################################################################
function delete_motd(){
 global  $lang_global, $characters_db, $realm_id;

 if (empty($_GET['id'])) redirect("index.php");

 $sql = new SQL;
 $sql->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
 
 $id = $sql->quote_smart($_GET['id']);

 $query = $sql->query("DELETE FROM bugreport WHERE id ='$id'");
 
 $sql->close();
 redirect("index.php");
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
   $output .= "<h1><font class=\"error\">{$lang_motd['err_max_len']}</font></h1>";
   break;
default: //no error
   $output .= "<h1>{$lang_motd['add_motd']}</h1>";
}
$output .= "</div>";

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($action) {
case "delete_motd":
	delete_motd();
	break;
case "add_motd":
	add_motd();
	break;
case "do_add_motd":
	do_add_motd();
	break;
case "edit_motd":
	edit_motd();
	break;
case "do_edit_motd":
	do_edit_motd();
	break;
default:
    add_motd();
}

require_once("footer.php");
?>