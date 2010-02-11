<?php


// page header, and any additional required libraries
require_once 'header.php';
require_once 'libs/char_lib.php';
require_once 'libs/item_lib.php';
// minimum permission to view page
valid_login($action_permission['read']);

//#############################################################################
// SHOW INV. AND BANK ITEMS
//#############################################################################
function char_inv(&$sqlr, &$sqlc)
{
  global $output, $lang_global, $lang_char, $lang_item,
    $realm_id, $characters_db, $world_db, $mmfpm_db,
    $action_permission, $user_lvl, $user_name,
    $item_datasite;

  // this page uses wowhead tooltops
  wowhead_tt();

  // we need at least an id or we would have nothing to show
  if (empty($_GET['id']))
    error($lang_global['empty_fields']);

  // this is multi realm support, as of writing still under development
  //  this page is already implementing it
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

  //-------------------SQL Injection Prevention--------------------------------
  // no point going further if we don have a valid ID
  $id = $sqlc->quote_smart($_GET['id']);
  if (is_numeric($id));
  else error($lang_global['empty_fields']);

  // getting character data from database
  $result = $sqlc->query('SELECT account, name, race, class, level, gender, money
    FROM characters WHERE guid = '.$id.' LIMIT 1');

  // no point going further if character does not exist
  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_assoc($result);

    // we get user permissions first
    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $result = $sqlr->query('SELECT gmlevel, username FROM account WHERE id = '.$char['account'].'');
    $owner_gmlvl = $sqlr->result($result, 0, 'gmlevel');
    $owner_name = $sqlr->result($result, 0, 'username');

    // check user permission
    if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name))
    {
      // main data that we need for this page, character inventory
      $result = $sqlc->query('SELECT ci.bag, ci.slot, ci.item, ci.item_template,
        SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", 15), " ", -1) as stack_count
        FROM character_inventory ci INNER JOIN item_instance ii on ii.guid = ci.item
        WHERE ci.guid = '.$id.' ORDER BY ci.bag,ci.slot');

      //---------------Page Specific Data Starts Here--------------------------
      // lets start processing first before we display anything
      //  we have lots to do for inventory

      // character bags, 1 main + 4 additional
      $bag = array
      (
        0=>array(),
        1=>array(),
        2=>array(),
        3=>array(),
        4=>array()
      );

      // character bang, 1 main + 7 additional
      $bank = array
      (
        0=>array(),
        1=>array(),
        2=>array(),
        3=>array(),
        4=>array(),
        5=>array(),
        6=>array(),
        7=>array()
      );

      // this is where we will put items that are in main bag
      $bag_id = array();
      // this is where we will put items that are in main bank
      $bank_bag_id = array();
      // this is where we will put items that are in character bags, 4 arrays, 1 for each
      $equiped_bag_id = array(0,0,0,0,0);
      // this is where we will put items that are in bank bangs, 7 arrays, 1 for each
      $equip_bnk_bag_id = array(0,0,0,0,0,0,0,0);

      $sqlw = new SQL;
      $sqlw->connect($world_db[$realmid]['addr'], $world_db[$realmid]['user'], $world_db[$realmid]['pass'], $world_db[$realmid]['name']);

      // we load the things in each bag slot
      while ($slot = $sqlc->fetch_assoc($result))
      {
        if ($slot['bag'] == 0 && $slot['slot'] > 18)
        {
          if($slot['slot'] < 23) // SLOT 19 TO 22 (Bags)
          {
            $bag_id[$slot['item']] = ($slot['slot']-18);
            $equiped_bag_id[$slot['slot']-18] = array($slot['item_template'],
              $sqlw->result($sqlw->query('SELECT ContainerSlots FROM item_template
                WHERE entry = '.$slot['item_template'].''), 0, 'ContainerSlots'), $slot['stack_count']);
          }
          elseif($slot['slot'] < 39) // SLOT 23 TO 38 (BackPack)
          {
            if(isset($bag[0][$slot['slot']-23]))
              $bag[0][$slot['slot']-23][0]++;
            else $bag[0][$slot['slot']-23] = array($slot['item_template'],0,$slot['stack_count']);
          }
          elseif($slot['slot'] < 67) // SLOT 39 TO 66 (Bank)
          {
            $bank[0][$slot['slot']-39] = array($slot['item_template'],0,$slot['stack_count']);
          }
          elseif($slot['slot'] < 74) // SLOT 67 TO 73 (Bank Bags)
          {
            $bank_bag_id[$slot['item']] = ($slot['slot']-66);
            $equip_bnk_bag_id[$slot['slot']-66] = array($slot['item_template'],
              $sqlw->result($sqlw->query('SELECT ContainerSlots FROM item_template
                WHERE entry = '.$slot['item_template'].''), 0, 'ContainerSlots'), $slot['stack_count']);
          }
        }
        else
        {
          // Bags
          if (isset($bag_id[$slot['bag']]))
          {
            if(isset($bag[$bag_id[$slot['bag']]][$slot['slot']]))
            $bag[$bag_id[$slot['bag']]][$slot['slot']][1]++;
            else
              $bag[$bag_id[$slot['bag']]][$slot['slot']] = array($slot['item_template'],0,$slot['stack_count']);
          }
          // Bank Bags
          elseif (isset($bank_bag_id[$slot['bag']]))
          {
            $bank[$bank_bag_id[$slot['bag']]][$slot['slot']] = array($slot['item_template'],0,$slot['stack_count']);
          }
        }
      }
      unset($slot);
      unset($bag_id);
      unset($bank_bag_id);
      unset($result);

      //------------------------Character Tabs---------------------------------
      // we start with a lead of 10 spaces,
      //  because last line of header is an opening tag with 8 spaces
      //  keep html indent in sync, so debuging from browser source would be easy to read
      $output .= '
          <!-- start of char_inv.php -->
          <center>
            <div id="tab_content">
              <div id="tab">
                <ul>
                  <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
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
                  <li id="selected"><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['inventory'].'</a></li>';
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
              <table class="lined" style="width: 700px;">
                <tr>';

      //---------------Page Specific Data Starts Here--------------------------
      $sqlm = new SQL;
      $sqlm->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);

      // equipped bags
      for($i=1; $i < 5; ++$i)
      {
        $output .= '
                  <th>';
        if($equiped_bag_id[$i])
        {
          $output .='
                    <a style="padding:2px;" href="'.$item_datasite.$equiped_bag_id[$i][0].'" target="_blank">
                      <img class="bag_icon" src="'.get_item_icon($equiped_bag_id[$i][0], $sqlm, $sqlw).'" alt="" />
                    </a>
                    '.$lang_item['bag'].' '.$i.'<br />
                    <font class="small">'.$equiped_bag_id[$i][1].' '.$lang_item['slots'].'</font>';
        }
        $output .= '
                  </th>';
      }
      $output .= '
                </tr>
                <tr>';

      // equipped bag slots
      for($t = 1; $t < 5; ++$t)
      {
        $output .= '
                  <td class="bag" valign="bottom" align="center">
                    <div style="width:'.(4*43).'px;height:'.(ceil($equiped_bag_id[$t][1]/4)*41).'px;">';
        $dsp = $equiped_bag_id[$t][1]%4;
        if ($dsp)
          $output .= '
                      <div class="no_slot"></div>';
        foreach ($bag[$t] as $pos => $item)
        {
          $item[2] = $item[2] == 1 ? '' : $item[2];
          $output .= '
                      <div style="left:'.(($pos+$dsp)%4*42).'px;top:'.(floor(($pos+$dsp)/4)*41).'px;">
                        <a style="padding:2px;" href="'.$item_datasite.$item[0].'" target="_blank">
                          <img src="'.get_item_icon($item[0], $sqlm, $sqlw).'" alt="" />
                        </a>
                        <div style="width:25px;margin:-20px 0px 0px 18px;color: black; font-size:14px">'.$item[2].'</div>
                        <div style="width:25px;margin:-21px 0px 0px 17px;font-size:14px">'.$item[2].'</div>
                      </div>';
        }
        $output .= '
                    </div>
                  </td>';
      }
      unset($equiped_bag_id);
      $output .= '
                </tr>
                <tr>
                  <th colspan="2" align="left">
                    <img class="bag_icon" src="'.get_item_icon(3960, $sqlm, $sqlw).'" alt="" align="middle" style="margin-left:100px;" />
                    <font style="margin-left:30px;">'.$lang_char['backpack'].'</font>
                  </th>
                  <th colspan="2">
                    '.$lang_char['bank_items'].'
                  </th>
                </tr>
                <tr>
                  <td colspan="2" class="bag" align="center" height="220px">
                    <div style="width:'.(4*43).'px;height:'.(ceil(16/4)*41).'px;">';

      // inventory items
      foreach ($bag[0] as $pos => $item)
      {
        $item[2] = $item[2] == 1 ? '' : $item[2];
        $output .= '
                      <div style="left:'.($pos%4*42).'px;top:'.(floor($pos/4)*41).'px;">
                        <a style="padding:2px;" href="'.$item_datasite.$item[0].'" target="_blank">
                          <img src="'.get_item_icon($item[0], $sqlm, $sqlw).'" alt="" />
                        </a>
                        <div style="width:25px;margin:-20px 0px 0px 18px;color: black; font-size:14px\">'.$item[2].'</div>
                        <div style="width:25px;margin:-21px 0px 0px 17px;font-size:14px">'.$item[2].'</div>
                      </div>';
      }
      unset($bag);
      $output .= '
                    </div>
                    <div style="text-align:right;width:168px;background-image:none;background-color:#393936;padding:2px;">
                      <b>
                        '.substr($char['money'],  0, -4).'<img src="img/gold.gif" alt="" align="middle" />
                        '.substr($char['money'], -4,  2).'<img src="img/silver.gif" alt="" align="middle" />
                        '.substr($char['money'], -2).'<img src="img/copper.gif" alt="" align="middle" />
                      </b>
                    </div>
                  </td>
                  <td colspan="2" class="bank" align="center">
                    <div style="width:'.(7*43).'px;height:'.(ceil(24/7)*41).'px;">';

      // bank items
      foreach ($bank[0] as $pos => $item)
      {
        $item[2] = $item[2] == 1 ? '' : $item[2];
        $output .= '
                      <div style="left:'.($pos%7*43).'px;top:'.(floor($pos/7)*41).'px;">
                        <a style="padding:2px;" href="'.$item_datasite.$item[0].'" target="_blank">
                          <img src="'.get_item_icon($item[0], $sqlm, $sqlw).'" class="inv_icon" alt="" />
                        </a>
                        <div style="width:25px;margin:-20px 0px 0px 18px;color: black; font-size:14px">'.$item[2].'</div>
                        <div style="width:25px;margin:-21px 0px 0px 17px;font-size:14px">'.$item[2].'</div>
                      </div>';
      }
      $output .= '
                    </div>
                  </td>
                </tr>
                <tr>';

      // equipped bank bags, first 4
      for($i=1; $i < 5; ++$i)
      {
        $output .= '
                  <th>';
        if($equip_bnk_bag_id[$i])
        {
          $output .= '
                    <a style="padding:2px;" href="'.$item_datasite.$equip_bnk_bag_id[$i][0].'" target="_blank">
                      <img class="bag_icon" src="'.get_item_icon($equip_bnk_bag_id[$i][0], $sqlm, $sqlw).'" alt="" />
                    </a>
                    '.$lang_item['bag'].' '.$i.'<br />
                    <font class="small">'.$equip_bnk_bag_id[$i][1].' '.$lang_item['slots'].'</font>';
        }
        $output .= '
                  </th>';
      }
      $output .= '
                </tr>
                <tr>';

      // equipped bank bag slots
      for($t=1; $t < 8; ++$t)
      {
        // equipped bank bags, last 3
        if($t===5)
        {
          $output .= '
                </tr>
                <tr>';
          for($i=5; $i < 8; ++$i)
          {
            $output .= '
                  <th>';
            if($equip_bnk_bag_id[$i])
            {
              $output .= '
                    <a style="padding:2px;" href="'.$item_datasite.$equip_bnk_bag_id[$i][0].'" target="_blank">
                      <img class="bag_icon" src="'.get_item_icon($equip_bnk_bag_id[$i][0], $sqlm, $sqlw).'" alt="" />
                    </a>
                    '.$lang_item['bag'].' '.$i.'<br />
                    <font class="small">'.$equip_bnk_bag_id[$i][1].' '.$lang_item['slots'].'</font>';
            }
            $output .= '
                  </th>';
          }
          $output .= '
                  <th>
                  </th>
                </tr>
                <tr>';
        }
        $output .= '
                  <td class="bank" align="center">
                    <div style="width:'.(4*43).'px;height:'.(ceil($equip_bnk_bag_id[$t][1]/4)*41).'px;">';
        $dsp=$equip_bnk_bag_id[$t][1]%4;
        if ($dsp)
          $output .= '
                      <div class="no_slot"></div>';
        foreach ($bank[$t] as $pos => $item)
        {
          $item[2] = $item[2] == 1 ? '' : $item[2];
          $output .= '
                      <div style="left:'.(($pos+$dsp)%4*43).'px;top:'.(floor(($pos+$dsp)/4)*41).'px;">
                        <a style="padding:2px;" href="'.$item_datasite.$item[0].'" target="_blank">
                          <img src="'.get_item_icon($item[0], $sqlm, $sqlw).'" alt="" />
                        </a>
                        <div style="width:25px;margin:-20px 0px 0px 18px;color: black; font-size:14px">'.$item[2].'</div>
                        <div style="width:25px;margin:-21px 0px 0px 17px;font-size:14px">'.$item[2].'</div>
                      </div>';
        }

        $output .= '
                    </div>
                  </td>';
      }
      unset($equip_bnk_bag_id);
      unset($bank);
      $output .= '
                  <td class="bank"></td>';
      //---------------Page Specific Data Ends here----------------------------
      //---------------Character Tabs Footer-----------------------------------
      $output .= '
                </tr>
              </table>
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
          <!-- end of char_inv.php -->';
    }
    else
      error($lang_char['no_permission']);
  }
  else
    error($lang_char['no_char_found']);

}


//#############################################################################
// MAIN
//#############################################################################

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
char_inv($sqlr, $sqlc);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
