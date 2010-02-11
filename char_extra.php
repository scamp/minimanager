<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
require_once 'libs/item_lib.php';
valid_login($action_permission['read']);

//########################################################################################################################^M
// SHOW CHARACTER EXTRA INV
//########################################################################################################################^M
function char_extra(&$sqlr, &$sqlc, &$sqlw)
{
  global $output, $lang_global, $lang_char,
    $realm_id, $characters_db, $world_db,
    $action_permission, $user_lvl, $user_name,
	$item_datasite;
  wowhead_tt();

  if (empty($_GET['id']))
    error($lang_global['empty_fields']);

  if (empty($_GET['realm']))
    $realmid = $realm_id;
  else
  {
    $realmid = $sqlr->quote_smart($_GET['realm']);
    if (is_numeric($realmid))
      $sqlc->connect($characters_db[$realmid]['addr'], $characters_db[$realmid]['user'], $characters_db[$realmid]['pass'], $characters_db[$realmid]['name']);
    else
      $realmid = $realm_id;
  }

  $id = $sqlc->quote_smart($_GET['id']);
  if (is_numeric($id));
  else error($lang_global['empty_fields']);

  $result = $sqlc->query('SELECT account, name, race, class, gender, level
    FROM characters WHERE guid = '.$id.' LIMIT 1');

  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_assoc($result);

    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $result = $sqlr->query('SELECT gmlevel, username FROM account WHERE id = '.$char['account'].'');
    $owner_gmlvl = $sqlr->result($result, 0, 'gmlevel');
    $owner_name = $sqlr->result($result, 0, 'username');

    if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name))
    {
      $output .= '
          <center>
           <div id="tab_content">
              <div id="tab">
                <ul>
                  <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
                  <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['inventory'].'</a></li>
                  '.(($char['level'] < 10) ? '' : '<li><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['talents'].'</a></li>').'
                  <li><a href="char_achieve.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['achievements'].'</a></li>
                  <li><a href="char_rep.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['reputation'].'</a></li>
                  <li><a href="char_skill.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['skills'].'</a></li>
                  <li><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['quests'].'</a></li>';
        if (char_get_class_name($char['class']) === 'Hunter' )
          $output .= '
                  <li><a href="char_pets.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['pets'].'</a></li>';
          $output .= '
                  <li><a href="char_friends.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['friends'].'</a></li>
                </ul>
                <ul>';
          // selected char tab at last 
          $output .= '
                  <li id="selected"><a href="char_extra.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['extra'].'</a></li>';
          $output .= '
              </ul>
            </div>
            <div id="tab_content2">
              <font class="bold">
                '.htmlentities($char['name']).' -
                <img src="img/c_icons/'.$char['race'].'-'.$char['gender'].'.gif"
                  onmousemove="toolTip(\''.char_get_race_name($char['race']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />
                <img src="img/c_icons/'.$char['class'].'.gif"
                  onmousemove="toolTip(\''.char_get_class_name($char['class']).'\',\'item_tooltip\')" onmouseout="toolTip()" alt="" /> - lvl '.char_get_level_color($char['level']).'
              </font>
              <br /><br />
                <table class="lined" style="width: 450px;">
                  <tr>
                    <th width="15%">'.$lang_char['icon'].'</th>
                    <th width="15%">'.$lang_char['quantity'].'</th>
                    <th width="70%">'.$lang_char['name'].'</th>
                  </tr>
                </table>';

  $sqlw = new SQL;
  $sqlw->connect($world_db[$realm_id]['addr'], $world_db[$realm_id]['user'], $world_db[$realm_id]['pass'], $world_db[$realm_id]['name']);
			  
  $result = $sqlw->query('SELECT entry, description FROM item_template WHERE BagFamily = 8192');
    while($bag = $sqlw->fetch_assoc($result))
    {
      $result_2 = $sqlc->query('SELECT item, item_template FROM character_inventory WHERE guid = '.$id.' AND item_template = '.$bag['entry'].' ');
        while ($char = $sqlc->fetch_assoc($result_2))
        {
		
		$result_3 = $sqlc->query('SELECT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, " ", 15), " ", -1) AS UNSIGNED) AS item FROM item_instance WHERE guid = '.$char['item'].' ');
		$items = $sqlc->fetch_row($result_3);
          $output .= '
                <table class="lined" style="width: 450px;">
                  <tr>
                    <td width="15%">
                      <a style="padding:2px;" href="'.$item_datasite.$char['item_template'].'" target="_blank">
                        <img src="'.get_item_icon($char['item_template'], $sqlm).'" alt="'.$char['item_template'].'" class="icon_border_0" />
                      </a>
					</td>
					
					<td width="15%">
					  '.$items['0'].'
					</td>
					<td width="70%">
                      <span onmousemove="toolTip(\''.$bag['description'].'\', \'item_tooltip\')" onmouseout="toolTip()">'.get_item_name($char['item_template'], $sqlw).'</span>
					</td>
                  </tr>
                </table>';
        }
    }
    unset($bag);

      $output .= '
              </div>
            </div>
            <br />
            <table class="hidden">
              <tr>
                <td>';
                  // button to user account page, user account page has own security
                  makebutton($lang_char['chars_acc'], 'user.php?action=edit_user&amp;id='.$owner_acc_id.'', 130);
      $output .= '
                </td>
                <td>';

      // only higher level GM with delete access can edit character
      //  character edit allows removal of character items, so delete permission is needed
      if ( ($user_lvl > $owner_gmlvl) && ($user_lvl >= $action_permission['delete']) )
      {
                  makebutton($lang_char['edit_button'], 'char_edit.php?id='.$id.'&amp;realm='.$realmid.'', 130);
        $output .= '
                </td>
                <td>';
      }
      // only higher level GM with delete access, or character owner can delete character
      if ( ( ($user_lvl > $owner_gmlvl) && ($user_lvl >= $action_permission['delete']) ) || ($owner_name === $user_name) )
      {
                  makebutton($lang_char['del_char'], 'char_list.php?action=del_char_form&amp;check%5B%5D='.$id.'" type="wrn', 130);
        $output .= '
                </td>
                <td>';
      }
      // only GM with update permission can send mail, mail can send items, so update permission is needed
      if ($user_lvl >= $action_permission['update'])
      {
                  makebutton($lang_char['send_mail'], 'mail.php?type=ingame_mail&amp;to='.$char['name'].'', 130);
        $output .= '
                </td>
                <td>';
      }
                  makebutton($lang_global['back'], 'javascript:window.history.back()" type="def', 130);
      $output .= '
                </td>
              </tr>
            </table>
            <br />
          </center>
          <!-- end of char_pets.php -->';
    }
    else
      error($lang_char['no_permission']);
  }
  else
    error($lang_char['no_char_found']);

}
  unset($char);

//########################################################################################################################
// MAIN
//########################################################################################################################

// action variable reserved for future use
//$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

// load language
$lang_char = lang_char();

$output .= '
          <div class="top">
            <h1>'.$lang_char['character'].'</h1>
          </div>';

// we getting links to realm database and character database left behind by header
// header does not need them anymore, might as well reuse the link
char_extra($sqlr, $sqlc, $sqlw);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
