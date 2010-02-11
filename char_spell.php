<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
require_once 'libs/spell_lib.php';
valid_login($action_permission['read']);

//########################################################################################################################
// SHOW CHARACTERS QUESTS
//########################################################################################################################
function char_spell(&$sqlr, &$sqlc)
{
  global $output, $lang_global, $lang_char,
    $realm_id, $characters_db, $mmfpm_db,
    $action_permission, $user_lvl, $user_name,
    $spell_datasite, $itemperpage;
  wowhead_tt();

  if (empty($_GET['id'])) error($lang_global['empty_fields']);

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
  if (is_numeric($id)); else $id = 0;

  $start = (isset($_GET['start'])) ? $sqlc->quote_smart($_GET['start']) : 0;
  if (is_numeric($start)); else $start=0;

  $result = $sqlc->query('SELECT account, name, race, class, level, gender
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
      $all_record = $sqlc->result($sqlc->query('SELECT count(spell) FROM character_spell WHERE guid = '.$id.' and active = 1'), 0);
      $result = $sqlc->query('SELECT spell FROM character_spell WHERE guid = '.$id.' and active = 1 order by spell ASC LIMIT '.$start.', '.$itemperpage.'');

      $output .= '
          <center>
           <div id="tab_content">
              <div id="tab">
                <ul>
                  <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
                  <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['inventory'].'</a></li>
                  <li><a href="char_extra.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['extra'].'</a></li>
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
                  <li id="selected"><a href="char_spell.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['spells'].'</a></li>';
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
              <br /><br />';

      if ($sqlc->num_rows($result))
      {
        $output .= '
              <table class="lined" style="width: 550px;">
                <tr align="right">
                  <td colspan="4">';
        $output .= generate_pagination('char_spell.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'', $all_record, $itemperpage, $start);
        $output .= '
                  </td>
                </tr>
                <tr>
                  <th>'.$lang_char['icon'].'</th>
                  <th>'.$lang_char['name'].'</th>
                  <th>'.$lang_char['icon'].'</th>
                  <th>'.$lang_char['name'].'</th>
                </tr>';

        $sqlm = new SQL;
        $sqlm->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);

        while ($spell = $sqlc->fetch_assoc($result))
        {
          $output .= '
                <tr>
                  <td><a href="'.$spell_datasite.$spell['spell'].'"><img src="'.spell_get_icon($spell['spell'], $sqlm).'" class="icon_border_0" /></a></td>
                  <td align="left"><a href="'.$spell_datasite.$spell['spell'].'">'.spell_get_name($spell['spell'], $sqlm).'</a></td>';
          if($spell = $sqlc->fetch_assoc($result))
            $output .='
                  <td><a href="'.$spell_datasite.$spell['spell'].'"><img src="'.spell_get_icon($spell['spell'], $sqlm).'" class="icon_border_0" /></a></td>
                  <td align="left"><a href="'.$spell_datasite.$spell['spell'].'">'.spell_get_name($spell['spell'], $sqlm).'</a></td>
                </tr>';
          else
            $output .='
                  <td></td>
                  <td></td>
                </tr>';
        }
        $output .= '
                <tr align="right">
                  <td colspan="4">';
        $output .= generate_pagination('char_spell.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'', $all_record, $itemperpage, $start);
        $output .= '
                  </td>
                </tr>
              </table>';
      }
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
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
          <!-- end of char_spell.php -->';
    }
    else
      error($lang_char['no_permission']);
  }
  else
    error($lang_char['no_char_found']);

}


//########################################################################################################################
// MAIN
//########################################################################################################################

//$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

$lang_char = lang_char();

char_spell($sqlr, $sqlc);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
