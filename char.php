<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
//require_once 'libs/item_lib.php';
//require_once 'libs/spell_lib.php';
require_once 'libs/map_zone_lib.php';
valid_login($action_permission['read']);

//########################################################################################################################
// SHOW GENERAL CHARACTERS INFO
//########################################################################################################################
function char_main(&$sqlr, &$sqlc)
{
  global $output, $lang_global, $lang_char, $lang_item,
    $realm_id, $realm_db, $characters_db, $world_db, $server, $mmfpm_db,
    $action_permission, $user_lvl, $user_name, $user_id,
    $item_datasite, $spell_datasite , $showcountryflag;

    define('DATA_0', 11-CHAR_DATA_OFFSET_MAX_ENERGY-1);
    define('DATA_1', 11+85-CHAR_DATA_OFFSET_GUILD_RANK-1);
    define('DATA_2', 11+85+15-(CHAR_DATA_OFFSET_SPELL_CRIT+6)-1);
    define('DATA_3', 11+85+15+88-CHAR_DATA_OFFSET_ARENA_POINTS-1);

  // this page uses wowhead tooltops
  wowhead_tt();

  // we need at least an id or we would have nothing to show
  if (empty($_GET['id']) && empty($_GET['name']))
    error($lang_global['empty_fields']);

  // this is multi realm support, as of writing still under development
  //  this page is already implementing it
  if (empty($_GET['realm']))
    $realmid = $realm_id;
  else
  {
    $realmid = $sqlr->quote_smart($_GET['realm']);
    if (is_numeric($realmid) && isset($characters_db[$realmid]['addr']))
      $sqlc->connect($characters_db[$realmid]['addr'], $characters_db[$realmid]['user'], $characters_db[$realmid]['pass'], $characters_db[$realmid]['name']);
    else
      $realmid = $realm_id;
  }

  if (isset($_GET['id']) && is_numeric($_GET['id'])){
        $sql_id = ' guid='.intval($_GET['id']);
  }elseif(isset($_GET['name'])){
        $sql_id = ' name="'.$sqlc->quote_smart($_GET['name']).'"';
  }else error($lang_global['empty_fields']);

  $result = $sqlc->query('SELECT
     guid, account, name, race, class, level, online, gender, map, zone, totaltime,
     CONCAT_WS(" ",
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", 36 ), " ", -11),
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", 153 ), " ", -85),
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", 1013 ), " ", -15),
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", 1253 ), " ", -88)
     ) AS data,
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+6+1).' ), " ", -6) AS spd,
     SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_EQU_TABARD+2).'), " ", '.(CHAR_DATA_OFFSET_EQU_HEAD-CHAR_DATA_OFFSET_EQU_TABARD-2).') AS items
     FROM characters WHERE'.$sql_id);
// data:11, 85, 15, 88; items:38;
  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_assoc($result);
    $id = $char['guid'];
    $char_data = explode(' ', $char['data']);
    //resrict by owner's gmlvl
    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $query = $sqlr->query('SELECT gmlevel, username FROM account WHERE id = '.$owner_acc_id.'');
    $owner_gmlvl = $sqlr->result($query, 0, 'gmlevel');
    $owner_name = $sqlr->result($query, 0, 'username');

    if($user_lvl || $server[$realmid]['both_factions'])
    {
      $side_v = 0;
      $side_p = 0;
    }
    else
    {
      $side_p = (in_array($sqlc->result($result, 0, 'race'),array(2,5,6,8,10))) ? 1 : 2;
      $result_1 = $sqlc->query('SELECT race FROM characters WHERE account = '.$user_id.' LIMIT 1');
      if ($sqlc->num_rows($result))
        $side_v = (in_array($sqlc->result($result_1, 0, 'race'), array(2,5,6,8,10))) ? 1 : 2;
      else
        $side_v = 0;
      unset($result_1);
    }

    if ($user_lvl >= $owner_gmlvl && (($side_v === $side_p) || !$side_v) || ($owner_gmlvl <= 2))
    {

      $online = ($char['online']) ? $lang_char['online'] : $lang_char['offline'];

      if($char_data[CHAR_DATA_OFFSET_GUILD_ID+DATA_1])
      {
        $guild_name = $sqlc->result($sqlc->query('SELECT name FROM guild WHERE guildid ='.$char_data[CHAR_DATA_OFFSET_GUILD_ID+DATA_1].''), 0, 'name');
        $guild_name = '<a href="guild.php?action=view_guild&amp;realm='.$realmid.'&amp;error=3&amp;id='.$char_data[CHAR_DATA_OFFSET_GUILD_ID+DATA_1].'" >'.$guild_name.'</a>';
        $mrank = $char_data[CHAR_DATA_OFFSET_GUILD_RANK+DATA_1];
        $guild_rank = $sqlc->result($sqlc->query('SELECT rname FROM guild_rank WHERE guildid ='.$char_data[CHAR_DATA_OFFSET_GUILD_ID+DATA_1].' AND rid='.$mrank.''), 0, 'rname');
      }
      else
      {
        $guild_name = $lang_global['none'];
        $guild_rank = $lang_global['none'];
      }

      $block = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_BLOCK+DATA_2]));
      $block = round($block[1],2);
      $dodge = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_DODGE+DATA_2]));
      $dodge = round($dodge[1],2);
      $parry = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_PARRY+DATA_2]));
      $parry = round($parry[1],2);
      $crit = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_MELEE_CRIT+DATA_2]));
      $crit = round($crit[1],2);
      $mindamage = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_MINDAMAGE+DATA_1]));
      $mindamage = round($mindamage[1],0);
      $maxdamage = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_MAXDAMAGE+DATA_1]));
      $maxdamage = round($maxdamage[1],0);
      $ranged_crit = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_RANGE_CRIT+DATA_2]));
      $ranged_crit = round($ranged_crit[1],2);
      $minrangeddamage = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_MINRANGEDDAMAGE+DATA_1]));
      $minrangeddamage = round($minrangeddamage[1],0);
      $maxrangeddamage = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_MAXRANGEDDAMAGE+DATA_1]));
      $maxrangeddamage = round($maxrangeddamage[1],0);

      $spell_crit = 100;
      for ($i=0; $i<6; ++$i)
      {
        $temp = unpack('f', pack('L', $char_data[CHAR_DATA_OFFSET_SPELL_CRIT+DATA_2+1+$i]));
        if ($temp[1] < $spell_crit)
        $spell_crit = $temp[1];
      }
      $spell_crit = round($spell_crit,2);
      $spell_damage = min(explode(' ', $char['spd']));

      $rage       = round($char_data[CHAR_DATA_OFFSET_RAGE+DATA_0] / 10);
      $maxrage    = round($char_data[CHAR_DATA_OFFSET_MAX_RAGE+DATA_0] / 10);
      $expertise  = ''.$char_data[CHAR_DATA_OFFSET_EXPERTISE+DATA_2].' / '.$char_data[CHAR_DATA_OFFSET_OFFHAND_EXPERTISE+DATA_2].'';

      $equip = explode(' ',$char['items']);

      $sqlm = new SQL;
      $sqlm->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);

      $sqlw = new SQL;
      $sqlw->connect($world_db[$realmid]['addr'], $world_db[$realmid]['user'], $world_db[$realmid]['pass'], $world_db[$realmid]['name']);

      $set_items='';//$equip[0].':'.$equip[CHAR_DATA_OFFSET_EQU_SHOULDER - CHAR_DATA_OFFSET_EQU_HEAD].':'.$equip[CHAR_DATA_OFFSET_EQU_CHEST - CHAR_DATA_OFFSET_EQU_HEAD].':'.$equip[CHAR_DATA_OFFSET_EQU_GLOVES - CHAR_DATA_OFFSET_EQU_HEAD].':'.$equip[CHAR_DATA_OFFSET_EQU_LEGS- CHAR_DATA_OFFSET_EQU_HEAD];

      $a_results = $sqlc->query('SELECT DISTINCT spell FROM character_aura WHERE guid = '.$id.'');
      $spell_list =array();
      $spells_div = '';
      if ($sqlc->num_rows($a_results))
      {
        while ($aura = $sqlc->fetch_assoc($a_results))
        {
            array_push($spell_list,$aura['spell']);
                 $spells_div .= '
                        <a style="padding:2px;" href="'.$spell_datasite.$aura['spell'].'" target="_blank">
                          <img src="img/INV/INV_blank_32.gif" name="spell'.$aura['spell'].'" alt="'.$aura['spell'].'" width="24" height="24" />
                        </a>';
        }
      }

      $output .= '<script>get_object_inf("'.join(',', $spell_list).'", "spells");get_object_inf("'.join(',', $equip).'", "items");</script>';
      $output .= '
          <!-- start of char.php -->
          <center>
            <div id="tab">
              <ul>
                <li id="selected"><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>';

      if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name)||$user_lvl==3)
      {
        $output .= '
                <li><a href="char_inv.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['inventory'].'</a></li>
                '.(($char['level'] < 10) ? '' : '<li><a href="char_talent.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['talents'].'</a></li>').'
                <li><a href="char_achieve.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['achievements'].'</a></li>
                <li><a href="char_quest.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['quests'].'</a></li>
                <li><a href="char_friends.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['friends'].'</a></li>
              </ul>
            </div>
            <div id="tab_content">
              <div id="tab">
                <ul>
                  <li id="selected"><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>';
        if (char_get_class_name($char['class']) === 'Hunter' )
          $output .= '
                  <li><a href="char_pets.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['pets'].'</a></li>';
        $output .= '
                  <li><a href="char_rep.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['reputation'].'</a></li>
                  <li><a href="char_skill.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['skills'].'</a></li>
                  <li><a href="char_ateams.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['arena_teams'].'</a></li>';
      }
      else
        $output .='<li><a href="char_ateams.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['arena_teams'].'</a></li>
              </ul>
            </div>
            <div id="tab_content">
              <div id="tab">
                <ul>';
      $output .='
                </ul>
              </div>
              <div id="tab_content2">
                <table class="lined" style="width: 580px;">
                  <tr>
                    <td colspan="2">
                      <div>
                        <img src="'.char_get_avatar_img($char['level'], $char['gender'], $char['race'], $char['class'], 0).'" alt="avatar" />
                      </div>
                      <div id="spell_div">';
      $output .= $spells_div;
      unset($spells_div);
      $output .= '
                      </div>
                    </td>
                    <td colspan="4">
                      <font class="bold">
                        '.htmlentities($char['name']).' -
                        <img src="img/c_icons/'.$char['race'].'-'.$char['gender'].'.gif" onmousemove="toolTip(\''.char_get_race_name($char['race']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />
                        <img src="img/c_icons/'.$char['class'].'.gif" onmousemove="toolTip(\''.char_get_class_name($char['class']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />
                        - lvl '.char_get_level_color($char['level']).'
                      </font>
                      <br />'.get_map_name($char['map'], $sqlm).' - '.get_zone_name($char['zone'], $sqlm).'
                      <br />'.$lang_char['honor_points'].': '.$char_data[CHAR_DATA_OFFSET_HONOR_POINTS+DATA_3].' / '.$char_data[CHAR_DATA_OFFSET_ARENA_POINTS+DATA_3].' - '.$lang_char['honor_kills'].': '.$char_data[CHAR_DATA_OFFSET_HONOR_KILL+DATA_3].'
                      <br />'.$lang_char['guild'].': '.$guild_name.' | '.$lang_char['rank'].': '.htmlentities($guild_rank).'
                      <br />'.(($char['online']) ? '<img src="img/up.gif" onmousemove="toolTip(\'Online\', \'item_tooltip\')" onmouseout="toolTip()" alt="online" />' : '<img src="img/down.gif" onmousemove="toolTip(\'Offline\', \'item_tooltip\')" onmouseout="toolTip()" alt="offline" />');
      if ($showcountryflag)
      {
        require_once 'libs/misc_lib.php';
        $country = misc_get_country_by_account($char['account'], $sqlr, $sqlm);
        $output .= ' - '.(($country['code']) ? '<img src="img/flags/'.$country['code'].'.png" onmousemove="toolTip(\''.($country['country']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />' : '-');
        unset($country);
      }
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="6%">';
      if ($equip[0])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[0].'" target="_blank" rel="ench='.$equip[1].'&amp;pcs='.$set_items.'">
                        <img src="img/INV/INV_empty_head.png"  name="itm'.$equip[0].'" alt="Head" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_head.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" align="center" width="50%">
                      <div class="gradient_p">'.$lang_item['health'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_MAX_HEALTH+DATA_0].'</div>';
      if ($char['class'] == 11) //druid
        $output .= '
                      </br>
                      <div class="gradient_p">'.$lang_item['energy'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_ENERGY+DATA_0].'/'.round($char_data[CHAR_DATA_OFFSET_MAX_ENERGY+DATA_0]).'</div>';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" align="center" width="50%">';
      if ($char['class'] == 1) // warrior
      {
        $output .= '
                      <div class="gradient_p">'.$lang_item['rage'].':</div>
                      <div class="gradient_pp">'.$rage.'/'.$maxrage.'</div>';
      }
      elseif ($char['class'] == 4) // rogue
      {
        $output .= '
                      <div class="gradient_p">'.$lang_item['energy'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_ENERGY+DATA_0].'/'.round($char_data[CHAR_DATA_OFFSET_MAX_ENERGY+DATA_0]).'</div>';
      }
      elseif ($char['class'] == 6) // death knight
      {
        // Don't know if FOCUS is the right one need to verify with Death Knight player.
        $output .= '
                      <div class="gradient_p">'.$lang_item['runic'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_FOCUS+DATA_0].'/'.$char_data[CHAR_DATA_OFFSET_MAX_FOCUS+DATA_0].'</div>';
      }
      elseif ($char['class'] == 11) // druid
      {
        $output .= '
                      <div class="gradient_p">'.$lang_item['mana'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_MAX_MANA+DATA_0].'</div>
                      </br>
                      <div class="gradient_p">'.$lang_item['rage'].':</div>
                      <div class="gradient_pp">'.$rage.'/'.$maxrage.'</div>';
      }
      elseif ($char['class'] == 2 || // paladin
              $char['class'] == 3 || // hunter
              $char['class'] == 5 || // priest
              $char['class'] == 7 || // shaman
              $char['class'] == 8 || // mage
              $char['class'] == 9)   // warlock
      {
        $output .= '
                      <div class="gradient_p">'.$lang_item['mana'].':</div>
                      <div class="gradient_pp">'.$char_data[CHAR_DATA_OFFSET_MAX_MANA+DATA_0].'</div>';
      }
      $output .= '
                    </td>
                    <td width="6%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_GLOVES - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_GLOVES - CHAR_DATA_OFFSET_EQU_HEAD].'" target="_blank" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_GLOVES - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;pcs='.$set_items.'">
                        <img src="img/INV/INV_empty_gloves.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_GLOVES - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Gloves" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_gloves.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_NECK - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_NECK - CHAR_DATA_OFFSET_EQU_HEAD].'" target="_blank" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_NECK - CHAR_DATA_OFFSET_EQU_HEAD+1].'">
                        <img src="img/INV/INV_empty_neck.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_NECK - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Neck" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_neck.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="3" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_item['strength'].':<br />
                        '.$lang_item['agility'].':<br />
                        '.$lang_item['stamina'].':<br />
                        '.$lang_item['intellect'].':<br />
                        '.$lang_item['spirit'].':<br />
                        '.$lang_item['armor'].':
                      </div>
                      <div class="gradient_pp">
                        '.$char_data[CHAR_DATA_OFFSET_STR+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_AGI+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_STA+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_INT+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_SPI+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_ARMOR+DATA_1].'
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="3" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_item['res_holy'].':<br />
                        '.$lang_item['res_arcane'].':<br />
                        '.$lang_item['res_fire'].':<br />
                        '.$lang_item['res_nature'].':<br />
                        '.$lang_item['res_frost'].':<br />
                        '.$lang_item['res_shadow'].':
                      </div>
                      <div class="gradient_pp">
                        '.$char_data[CHAR_DATA_OFFSET_RES_HOLY+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RES_ARCANE+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RES_FIRE+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RES_NATURE+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RES_FROST+DATA_1].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RES_SHADOW+DATA_1].'
                      </div>
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_BELT - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_BELT - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_BELT - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_waist.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_BELT - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Belt" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_waist.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_SHOULDER - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_SHOULDER - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_SHOULDER - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;pcs='.$set_items.'" target="_blank">
                        <img src="img/INV/INV_empty_shoulder.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_SHOULDER - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Shoulder" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_shoulder.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_LEGS - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_LEGS - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_LEGS - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;pcs='.$set_items.'" target="_blank">
                        <img src="img/INV/INV_empty_legs.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_LEGS - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Legs" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_legs.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_BACK - CHAR_DATA_OFFSET_EQU_HEAD])
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_BACK - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_BACK - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_chest_back.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_BACK - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Back" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_chest_back.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_FEET - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_FEET - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_FEET - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_feet.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_FEET - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Feet" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_feet.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_CHEST - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_CHEST - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_CHEST - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;pcs='.$set_items.'" target="_blank">
                        <img <img src="img/INV/INV_empty_chest_back.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_CHEST - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Chest" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_chest_back.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_char['melee_d'].':<br />
                        '.$lang_char['melee_ap'].':<br />
                        '.$lang_char['melee_hit'].':<br />
                        '.$lang_char['melee_crit'].':<br />
                        '.$lang_char['expertise'].':<br />
                      </div>
                      <div class="gradient_pp">
                        '.$mindamage.'-'.$maxdamage.'<br />
                        '.($char_data[CHAR_DATA_OFFSET_AP+DATA_1]+$char_data[CHAR_DATA_OFFSET_AP_MOD+DATA_1]).'<br />
                        '.$char_data[CHAR_DATA_OFFSET_MELEE_HIT+DATA_3].'<br />
                        '.$crit.'%<br />
                        '.$expertise.'<br />
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_char['spell_d'].':<br />
                        '.$lang_char['spell_heal'].':<br />
                        '.$lang_char['spell_hit'].':<br />
                        '.$lang_char['spell_crit'].':<br />
                        '.$lang_char['spell_haste'].'
                      </div>
                      <div class="gradient_pp">
                        '.$spell_damage.'<br />
                        '.$char_data[CHAR_DATA_OFFSET_SPELL_HEAL+DATA_3].'<br />
                        '.$char_data[CHAR_DATA_OFFSET_SPELL_HIT+DATA_3].'<br />
                        '.$spell_crit.'%<br />
                        '.$char_data[CHAR_DATA_OFFSET_SPELL_HASTE_RATING+DATA_3].'
                      </div>
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_FINGER1 - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_FINGER1 - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_FINGER1 - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_finger.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_FINGER1 - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Finger1" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_finger.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_SHIRT - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_SHIRT - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_SHIRT - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_shirt.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_SHIRT - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Shirt" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_shirt.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_FINGER2 - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_FINGER2 - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_FINGER2 - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img ssrc="img/INV/INV_empty_finger.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_FINGER2 - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Finger2" />
                      </a>';
      else $output .= '
                      <img src="img/INV/INV_empty_finger.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_TABARD - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_TABARD - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_TABARD - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_tabard.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_TABARD - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Tabard" />
                      </a>';
      else $output .= '
                      <img src="img/INV/INV_empty_tabard.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_char['dodge'].':<br />
                        '.$lang_char['parry'].':<br />
                        '.$lang_char['block'].':<br />
                        '.$lang_char['resilience'].':
                      </div>
                      <div class="gradient_pp">
                        '.$dodge.'%<br />
                        '.$parry.'%<br />
                        '.$block.'%<br />
                        '.$char_data[CHAR_DATA_OFFSET_RESILIENCE+DATA_3].'
                      </div>
                    </td>
                    <td class="half_line" colspan="2" rowspan="2" align="center" width="50%">
                      <div class="gradient_p">
                        '.$lang_char['ranged_d'].':<br />
                        '.$lang_char['ranged_ap'].':<br />
                        '.$lang_char['ranged_hit'].':<br />
                        '.$lang_char['ranged_crit'].':<br />
                      </div>
                      <div class="gradient_pp">
                        '.$minrangeddamage.'-'.$maxrangeddamage.'<br />
                        '.($char_data[CHAR_DATA_OFFSET_RANGED_AP+DATA_1]+$char_data[CHAR_DATA_OFFSET_RANGED_AP_MOD+DATA_1]).'<br />
                        '.$char_data[CHAR_DATA_OFFSET_RANGE_HIT+DATA_3].'<br />
                        '.$ranged_crit.'%<br />
                      </div>
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_TRINKET1 - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_TRINKET1 - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_TRINKET1 - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_trinket.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_TRINKET1 - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Trinket1" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_trinket.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_WRIST - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_WRIST - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_WRIST - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_wrist.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_WRIST - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Wrist" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_wrist.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="1%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_TRINKET2 - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_TRINKET2 - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_TRINKET2 - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_trinket.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_TRINKET2 - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Trinket2" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_trinket.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                  </tr>
                  <tr>
                    <td></td>
                    <td width="15%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_MAIN_HAND - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_MAIN_HAND - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_MAIN_HAND - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_main_hand.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_MAIN_HAND - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="MainHand" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_main_hand.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="15%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_OFF_HAND - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_OFF_HAND - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_OFF_HAND - CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_off_hand.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_OFF_HAND - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="OffHand" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_off_hand.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="15%">';
      if ($equip[CHAR_DATA_OFFSET_EQU_RANGED - CHAR_DATA_OFFSET_EQU_HEAD] > 0)
        $output .= '
                      <a style="padding:2px;" href="'.$item_datasite.$equip[CHAR_DATA_OFFSET_EQU_RANGED - CHAR_DATA_OFFSET_EQU_HEAD].'" rel="ench='.$equip[CHAR_DATA_OFFSET_EQU_RANGED- CHAR_DATA_OFFSET_EQU_HEAD+1].'&amp;" target="_blank">
                        <img src="img/INV/INV_empty_ranged.png" name="itm'.$equip[CHAR_DATA_OFFSET_EQU_RANGED - CHAR_DATA_OFFSET_EQU_HEAD].'" alt="Ranged" />
                      </a>';
      else
        $output .= '
                      <img src="img/INV/INV_empty_ranged.png" class="icon_border_0" alt="empty" />';
      $output .= '
                    </td>
                    <td width="15%"></td>
                    <td></td>
                  </tr>';
      if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name))
      {
        //total time played
        $tot_time = $char['totaltime'];
        $tot_days = (int)($tot_time/86400);
        $tot_time = $tot_time - ($tot_days*86400);
        $total_hours = (int)($tot_time/3600);
        $tot_time = $tot_time - ($total_hours*3600);
        $total_min = (int)($tot_time/60);

        $output .= '
                  <tr>
                    <td colspan="6">
                      '.$lang_char['tot_paly_time'].': '.$tot_days.' '.$lang_char['days'].' '.$total_hours.' '.$lang_char['hours'].' '.$total_min.' '.$lang_char['min'].'
                    </td>
                  </tr>';
      }
      $output .= '
                </table>
              </div>
              <br />
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
          <!-- end of char.php -->';
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
char_main($sqlr, $sqlc);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
