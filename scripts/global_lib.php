<?php
/*
 * Project Name: MiniManager for Mangos Server
 * Date: 17.10.2006 inital version (0.0.1a)
 * Author: Q.SA
 * Copyright: Q.SA
 * Email: *****
 * License: GNU General Public License v2(GPL)
 */
 
 //global output string - hands off...
$output = "";

//to avoid Strict Standards notices in php 5.1
if (function_exists ('date_default_timezone_set') ) {
	date_default_timezone_set('UTC');
}

//##########################################################################################
//validates session's vars and restricting access to given level
function valid_login($restrict_lvl){
 if (isset($_SESSION['user_lvl']) && isset($_SESSION['user_id']) && isset($_SESSION['realm_id']) && isset($_SESSION['uname'])) {
	$user_lvl = $_SESSION['user_lvl'];
	$ip = ( !empty($_SERVER['REMOTE_ADDR']) ) ? $_SERVER['REMOTE_ADDR'] : getenv('REMOTE_ADDR');
	if ($ip != $_SESSION['client_ip']) redirect("login.php");
 } else redirect("login.php");

 if ($user_lvl < $restrict_lvl) redirect("login.php?error=5");
}


//##########################################################################################
//making buttons - just to make them all look the same
function makebutton($xtext, $xlink, $xwidth) {
 global $output;
 $output .="<div><a class=\"button\" style=\"width:".$xwidth."px;\" href=\"$xlink\">$xtext</a></div>";
}

//##########################################################################################
//make javascript tooltip
function maketooltip($text, $link, $tip, $class, $target = "target=\"_self\"") {
 global $output;
 $output .="<a style=\"padding:2px;\" href=\"$link\" $target onmouseover=\"toolTip('".addslashes($tip)."','$class')\" onmouseout=\"toolTip()\">$text</a>";
}

//##########################################################################################
// Fix reditection error under MS-IIS fuckedup-servers.
function redirect($url) {
 if ( strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') === false ){
	header("Location: ".$url);
	exit();
	} else die('<meta http-equiv="refresh" content="0;URL='.$url.'" />');
}
	
//##########################################################################################
// Generate paging navigation.
// Original from PHPBB with some modifications to make them more simple
function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = TRUE) {
	if ( !$num_items ) return "";
	$total_pages = ceil($num_items/$per_page);
	if ( $total_pages == 1 ) {
		return "";
	}
	$on_page = floor($start_item / $per_page) + 1;
	$page_string = "";
	if ( $total_pages > 10 ) {
		$init_page_max = ( $total_pages > 3 ) ? 3 : $total_pages;
		for($i = 1; $i < $init_page_max + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>" : "<a href=\"$base_url&amp;start=" . ( ( $i - 1 ) * $per_page )  . "\">" . $i . "</a>";
			if ( $i <  $init_page_max ) {
				$page_string .= ", ";
			}
		}
		if ( $total_pages > 3 ) {
			if ( $on_page > 1  && $on_page < $total_pages ) {
				$page_string .= ( $on_page > 5 ) ? " ... " : ", ";
				$init_page_min = ( $on_page > 4 ) ? $on_page : 5;
				$init_page_max = ( $on_page < $total_pages - 4 ) ? $on_page : $total_pages - 4;

				for($i = $init_page_min - 1; $i < $init_page_max + 2; $i++) {
					$page_string .= ($i == $on_page) ? "<b>" . $i . "</b>" : "<a href=\"$base_url&amp;start=" . ( ( $i - 1 ) * $per_page )  . "\">" . $i . "</a>";
					if ( $i <  $init_page_max + 1 ) {
						$page_string .= ", ";
					}
				}
				$page_string .= ( $on_page < $total_pages - 4 ) ? " ... " : ", ";
			} 
			else {
				$page_string .= " ... ";
			}
			for($i = $total_pages - 2; $i < $total_pages + 1; $i++) {
				$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>"  : "<a href=\"$base_url&amp;start=" . ( ( $i - 1 ) * $per_page )  . "\">" . $i . "</a>";
				if( $i <  $total_pages ) {
					$page_string .= ", ";
				}
			}
		}
	}
	else {
		for($i = 1; $i < $total_pages + 1; $i++) {
			$page_string .= ( $i == $on_page ) ? "<b>" . $i . "</b>" : "<a href=\"$base_url&amp;start=" . ( ( $i - 1 ) * $per_page )  . "\">" . $i . "</a>";
			if ( $i <  $total_pages ) {
				$page_string .= ", ";
			}
		}
	}
	if ( $add_prevnext_text ) {
		if ( $on_page > 1 ) {
			$page_string = " <a href=\"$base_url&amp;start=" . ( ( $on_page - 2 ) * $per_page )  . "\">Prev</a>&nbsp;&nbsp;" . $page_string;
		}

		if ( $on_page < $total_pages ) {
			$page_string .= "&nbsp;&nbsp;<a href=\"$base_url&amp;start=" . ( $on_page * $per_page )  . "\">Next</a>";
		}
	}
	$page_string = "Page: " . $page_string;
	return $page_string;
}


//##########################################################################################
//redirects to error page with error code
function error($err){
	//$err = addslashes($err);
	redirect("error.php?err=$err");
}


//##########################################################################################
//testing for open port
function test_port($server,$port){
 $sock = @fsockopen($server, $port, $ERROR_NO, $ERROR_STR, (float)0.5);
 if($sock){
	 @fclose($sock);
	 return true;
	} else return false;
}

//##########################################################################################
function aasort(&$array, $field, $order = false){
	if(is_string($field)) $field = "'$field'";
	$order = ($order ? '<' : '>');
	usort($array, 
		create_function('$a, $b', 
		'return ($a['.$field.'] == $b['.$field.'] ? 0 :($a['.$field.'] '.$order.' $b['.$field.']) ? 1 : -1);') 
	);
}
?>