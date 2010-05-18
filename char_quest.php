<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
valid_login($action_permission['read']);

define('QUEST_STATUS_NONE', 0);         //Quest isn't shown in quest list; default
define('QUEST_STATUS_COMPLETE', 1);     //Quest has been completed
define('QUEST_STATUS_UNAVAILABLE', 2);  //Quest is unavailable to the character
define('QUEST_STATUS_INCOMPLETE', 3);   //Quest is active in quest log but incomplete
define('QUEST_STATUS_AVAILABLE', 4);    //Quest is available to be taken by character
define('QUEST_TYPE_ALL', '');
define('QUEST_TYPE_CLASS', '&amp;class');
define('QUEST_TYPE_RACE', '&amp;race');

//########################################################################################################################
// SHOW CHARACTERS QUESTS
//########################################################################################################################
function char_quest(&$sqlr, &$sqlc)
{
  global $output, $lang_global, $lang_char,
    $realm_id, $world_db, $characters_db,
    $action_permission, $user_lvl, $user_name,
    $quest_datasite, $itemperpage;
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

  //==========================$_GET and SECURE=================================
  $start = (isset($_GET['start'])) ? $sqlc->quote_smart($_GET['start']) : 0;
  if (is_numeric($start)); else $start=0;

  $order_by = (isset($_GET['order_by'])) ? $sqlc->quote_smart($_GET['order_by']) : 1;
  if (is_numeric($order_by)); else $order_by=1;

  $dir = (isset($_GET['dir'])) ? intval($_GET['dir']) : 1;

  $order_dir = ($dir) ? "DESC" : "ASC";
  //==========================$_GET and SECURE end=============================

  $result = $sqlc->query('SELECT account, name, race, class, level, gender
    FROM characters WHERE guid = '.$id.' LIMIT 1');

  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_assoc($result);

    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $result = $sqlr->query('SELECT gmlevel, username FROM account WHERE id = '.$char['account'].'');
    $owner_gmlvl = $sqlr->result($result, 0, 'gmlevel');
    $owner_name = $sqlr->result($result, 0, 'username');

    if(isset($_GET['remove']) && (int)$_GET['remove'] > 0)
         remove_quest($sqlc, $id, (int)$_GET['remove']);

    if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name)||$user_lvl>=2)
    {
      $output .= '
          <center>
            <div id="tab">
              <ul>
                <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
                <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['inventory'].'</a></li>
                '.(($char['level'] < 10) ? '' : '<li><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['talents'].'</a></li>').'
                <li><a href="char_achieve.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['achievements'].'</a></li>
                <li id="selected"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['quests'].'</a></li>
                <li><a href="char_friends.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['friends'].'</a></li>
              </ul>
            </div>';
      if($user_lvl >= 2)
          $output .= '<div id="tab_content">
                  <div id="tab">
                    <ul>
                      <li '.(!$type == QUEST_TYPE_CLASS?'id="selected"':'').'><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['quests'].'</a></li>
                      <li '.($type == QUEST_TYPE_CLASS?'id="selected"':'').'><a href="char_quest.php?id='.$id.'&amp;class&amp;realm='.$realmid.'">Class '.$lang_char['quests'].'</a></li>
                      <li '.($type == QUEST_TYPE_RACE?'id="selected"':'').'><a href="char_quest.php?id='.$id.'&amp;race&amp;realm='.$realmid.'">Race '.$lang_char['quests'].'</a></li>
                    </ul>
                  </div>
              <div id="tab_content2">';
      else
          $output .= '<div id="tab_content">';
      $output .= '<font class="bold">
                '.$char['name'].' -
                <img src="img/c_icons/'.$char['race'].'-'.$char['gender'].'.gif"
                  onmousemove="toolTip(\''.char_get_race_name($char['race']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />
                <img src="img/c_icons/'.$char['class'].'.gif"
                  onmousemove="toolTip(\''.char_get_class_name($char['class']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" /> - lvl '.char_get_level_color($char['level']).'
              </font>
              <br /><br />
              <table class="lined" style="width: 550px;">
                <tr>
                  <th width="10%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=0&amp;dir='.$dir.'"'.($order_by == 0 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_id'].'</a></th>
                  <th width="7%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=1&amp;dir='.$dir.'"'.($order_by == 1 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_level'].'</a></th>
                  <th width="78%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=2&amp;dir='.$dir.'"'.($order_by == 2 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_title'].'</a></th>
                  <th width="5%"><img src="img/aff_qst.png" width="14" height="14" border="0" alt="" /></th>';
      if($user_lvl >= 2)
        $output .= '<th width="5%"><img src="img/aff_cross.png" width="14" height="14" border="0" alt="" /></th>';
      $output .= '</tr>';
      if($user_lvl >= 2 && $type == QUEST_TYPE_CLASS){
        $sqlw = new SQL;
        $sqlw->connect($world_db[$realmid]['addr'], $world_db[$realmid]['user'], $world_db[$realmid]['pass'], $world_db[$realmid]['name']);
        $class_quests = $sqlw->query('SELECT entry FROM quest_template WHERE SkillOrClass = -'.$char['class']);
        $class_quest_list = array();
        if($sqlw->num_rows($class_quests))
            while($ql = $sqlw->fetch_assoc($class_quests))
                    array_push($class_quest_list, $ql['entry']);
        else
            $class_quest_list[0] = 0;

        $result = $sqlc->query('SELECT quest, status, rewarded FROM character_queststatus WHERE guid = '.$id.' AND quest IN ('.join(',',$class_quest_list).') ORDER BY status DESC');

      }elseif($user_lvl >= 2 && $type == QUEST_TYPE_RACE){
        $sqlw = new SQL;
        $sqlw->connect($world_db[$realmid]['addr'], $world_db[$realmid]['user'], $world_db[$realmid]['pass'], $world_db[$realmid]['name']);
        $class_quests = $sqlw->query('SELECT entry FROM quest_template WHERE RequiredRaces = '.pow(2, $char['race']-1));
        $class_quest_list = array();
        if($sqlw->num_rows($class_quests))
            while($ql = $sqlw->fetch_assoc($class_quests))
                    array_push($class_quest_list, $ql['entry']);
        else
            $class_quest_list[0] = 0;

        $result = $sqlc->query('SELECT quest, status, rewarded FROM character_queststatus WHERE guid = '.$id.' AND quest IN ('.join(',',$class_quest_list).') ORDER BY status DESC');

      }else
        $result = $sqlc->query('SELECT quest, status, rewarded FROM character_queststatus WHERE guid = '.$id.' AND ( status = '.QUEST_STATUS_COMPLETE.' OR status = '.QUEST_STATUS_INCOMPLETE.' ) ORDER BY status DESC');

      $quests_1 = array();
      $quests_3 = array();

      if ($sqlc->num_rows($result))
      {
        while ($quest = $sqlc->fetch_assoc($result))
        {
          $deplang = get_lang_id();
          $query1 = $sqlc->query('SELECT QuestLevel, IFNULL('.($deplang<>0 ? '`title_loc'.$deplang.'`' : 'NULL').', title) as Title FROM `'.$world_db[$realmid]['name'].'`.`quest_template` LEFT JOIN `'.$world_db[$realmid]['name'].'`.`locales_quest` ON `quest_template`.`entry` = `locales_quest`.`entry` WHERE `quest_template`.`entry` = \''.$quest['quest'].'\'');
          $quest_info = $sqlc->fetch_assoc($query1);
          if(QUEST_STATUS_COMPLETE == $quest['status'])
            array_push($quests_1, array($quest['quest'], $quest_info['QuestLevel'], $quest_info['Title'], $quest['rewarded']));
          else
            array_push($quests_3, array($quest['quest'], $quest_info['QuestLevel'], $quest_info['Title']));
        }
        unset($quest);
        unset($quest_info);
        aasort($quests_1, $order_by, $dir);
        $orderby = $order_by;
        if (2 < $orderby)
          $orderby = 1;
        aasort($quests_3, $orderby, $dir);
        $all_record = count($quests_1);

        foreach ($quests_3 as $data)
        {
          $output .= '
                <tr>
                  <td>'.$data[0].'</td>
                  <td>('.$data[1].')</td>
                  <td align="left"><a href="'.$quest_datasite.$data[0].'" target="_blank">'.$data[2].'</a></td>
                  <td><img src="img/aff_qst.png" width="14" height="14" alt="" /></td>';
          if($user_lvl >= 2)
        $output .= '<td width="5%"><a href="char_quest.php?id='.$id.$type.'&amp;realm='.$realmid.'&amp;remove='.$data[0].'"><img src="img/aff_cross.png" width="14" height="14" border="0" alt="" /></a></td>';
          $output .= '</tr>';
        }
        unset($quest_3);
        if(count($quests_1))
        {
          $output .= '
              </table>
              <table class="hidden" style="width: 550px;">
                <tr align="right">
                  <td>';
          $output .= generate_pagination('char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by='.$order_by.'&amp;dir='.($dir ? 0 : 1), $all_record, $itemperpage, $start);
          $output .= '
                  </td>
                </tr>
              </table>
              <table class="lined" style="width: 550px;">
                <tr>
                  <th width="10%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=0&amp;dir='.$dir.'"'.($order_by == 0 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_id'].'</a></th>
                  <th width="7%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=1&amp;dir='.$dir.'"'.($order_by == 1 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_level'].'</a></th>
                  <th width="68%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=2&amp;dir='.$dir.'"'.($order_by == 2 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['quest_title'].'</a></th>
                  <th width="10%"><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by=3&amp;dir='.$dir.'"'.($order_by == 3 ? ' class="'.$order_dir.'"' : '').'>'.$lang_char['rewarded'].'</a></th>
                  <th width="5%"><img src="img/aff_tick.png" width="14" height="14" border="0" alt="" /></th>';
          if($user_lvl >= 2)
        $output .= '<th width="5%"><img src="img/aff_cross.png" width="14" height="14" border="0" alt="" /></th>';
          $output .= '</tr>';
          $i = 0;
          foreach ($quests_1 as $data)
          {
            if($i < ($start+$itemperpage) && $i >= $start)
            {
              $output .= '
                <tr>
                  <td>'.$data[0].'</td>
                  <td>('.$data[1].')</td>
                  <td align="left"><a href="'.$quest_datasite.$data[0].'" target="_blank">'.$data[2].'</a></td>
                  <td><img src="img/aff_'.($data[3] ? 'tick' : 'qst' ).'.png" width="14" height="14" alt="" /></td>
                  <td><img src="img/aff_tick.png" width="14" height="14" alt="" /></td>';
          if($user_lvl >= 2)
        $output .= '<td width="5%"><a href="char_quest.php?id='.$id.$type.'&amp;realm='.$realmid.'&amp;remove='.$data[0].'"><img src="img/aff_cross.png" width="14" height="14" border="0" alt="" /></a></td>';
          $output .= '</tr>';
            }
            $i++;
          }
          unset($data);
          unset($quest_1);
          $output .= '
                <tr align="right">
                  <td colspan="'.($user_lvl >= 2?6:5).'">';
          $output .= generate_pagination('char_quest.php?id='.$id.'&amp;realm='.$realmid.'&amp;start='.$start.'&amp;order_by='.$order_by.'&amp;dir='.($dir ? 0 : 1), $all_record, $itemperpage, $start);
          $output .= '
                  </td>
                </tr>';
        }
      }
      else
        $output .= '
                <tr>
                  <td colspan="'.($user_lvl >= 2?5:4).'"><p>'.$lang_char['no_act_quests'].'</p></td>
                </tr>';
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
      $output .= '
              </table>
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
          <!-- end of char_quest.php -->';
    }
    else
      error($lang_char['no_permission']);
  }
  else
    error($lang_char['no_char_found']);

}

function remove_quest(&$sqlc, $id, $quest_id)
{
    global $user_lvl;
    $quest_id = intval($quest_id);

    if($user_lvl >= 2 & $quest_id > 0)
        $sqlc->query('DELETE FROM character_queststatus WHERE quest = '.$quest_id.' AND guid = '.intval($id));

}
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
char_quest($sqlr, $sqlc);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
