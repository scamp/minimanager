<?php


require_once("header.php");
require_once("scripts/defines.php");
require_once("libs/char_lib.php");
valid_login($action_permission['read']);

//########################################################################################################################
// SHOW CHARACTER TALENTS
//########################################################################################################################
function char_talent()
{
  require_once("libs/talent_lib.php");
  global $lang_global, $lang_char, $output, $realm_id, $realm_db, $characters_db, $mmfpm_db,
    $action_permission, $user_lvl, $user_name, $spell_datasite, $talent_calculator_datasite, $developer_test_mode, $new_talent_tab;
  wowhead_tt();

  if (empty($_GET['id']))
    error($lang_global['empty_fields']);

  $sqlr = new SQL;
  $sqlr->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);

  if (empty($_GET['realm']))
    $realmid = $realm_id;
  else
  {
    $realmid = $sqlr->quote_smart($_GET['realm']);
    if (!is_numeric($realmid)) $realmid = $realm_id;
  }

  $sqlm = new SQL;
  $sqlm->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);

  //check for php gmp extension
  //if (extension_loaded('gmp'))
  //  $GMP=1;
  //else
  //  $GMP=0;
  //end of gmp check

  $sqlc = new SQL;
  $sqlc->connect($characters_db[$realmid]['addr'], $characters_db[$realmid]['user'], $characters_db[$realmid]['pass'], $characters_db[$realmid]['name']);

  $id = $sqlc->quote_smart($_GET['id']);
  if (!is_numeric($id))
    $id = 0;

  $order_by = (isset($_GET['order_by'])) ? $sqlc->quote_smart($_GET['order_by']) : 1;
  $dir = (isset($_GET['dir'])) ? $sqlc->quote_smart($_GET['dir']) : 0;
  $dir = ($dir) ? 0 : 1;

  $result = $sqlc->query("SELECT account, name, race, class, CAST( SUBSTRING_INDEX(SUBSTRING_INDEX(`data`, ' ', ".(CHAR_DATA_OFFSET_LEVEL+1)."), ' ', -1) AS UNSIGNED) AS level,
    mid(lpad( hex( CAST(substring_index(substring_index(data,' ',".(CHAR_DATA_OFFSET_GENDER+1)."),' ',-1) as unsigned) ),8,'0'),4,1) as gender
    FROM `characters` WHERE guid = $id LIMIT 1");

  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_row($result);

    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $result = $sqlr->query("SELECT gmlevel,username FROM account WHERE id ='$char[0]'");
    $owner_gmlvl = $sqlr->result($result, 0, 'gmlevel');
    $owner_name = $sqlr->result($result, 0, 'username');

    if (($user_lvl > $owner_gmlvl)||($owner_name == $user_name))
    {
      $result = $sqlc->query("SELECT spell FROM `character_spell` WHERE guid = $id AND active = 1 ORDER BY spell DESC");

      $output .= "
        <center>
            <div id=\"tab\">
            <ul>
              <li><a href=\"char.php?id=$id&amp;realm=$realmid\">{$lang_char['char_sheet']}</a></li>
              <li><a href=\"char_inv.php?id=$id&amp;realm=$realmid\">{$lang_char['inventory']}</a></li>
              <li id=\"selected\"><a href=\"char_talent.php?id=$id&amp;realm=$realmid\">{$lang_char['talents']}</a></li>
              <li><a href=\"char_achieve.php?id=$id&amp;realm=$realmid\">{$lang_char['achievements']}</a></li>
              <li><a href=\"char_quest.php?id=$id&amp;realm=$realmid\">{$lang_char['quests']}</a></li>
              <li><a href=\"char_friends.php?id=$id&amp;realm=$realmid\">{$lang_char['friends']}</a></li>
            </ul>
          </div>
          <div id=\"tab_content\">
            <font class=\"bold\">".htmlentities($char[1])." - <img src='img/c_icons/{$char[2]}-{$char[5]}.gif' onmousemove='toolTip(\"".char_get_race_name($char[2])."\",\"item_tooltip\")' onmouseout='toolTip()' alt=\"\" /> <img src='img/c_icons/{$char[3]}.gif' onmousemove='toolTip(\"".char_get_class_name($char[3])."\",\"item_tooltip\")' onmouseout='toolTip()' alt=\"\" /> - lvl ".char_get_level_color($char[4])."</font>
            <br /><br />
            <table class=\"lined\" style=\"width: 550px;\">";
      if($developer_test_mode && $new_talent_tab)
      {
        $tabs = array();
        if ($sqlc->num_rows($result))
        {
          while ($talent = $sqlc->fetch_row($result))
          {
            if($tab = $sqlm->fetch_row($sqlm->query("SELECT tab, row, col from dbc_talent where rank5 = $talent[0]")))
              $tabs[$tab[0]][$tab[1]][$tab[2]] = array($talent[0],'5');
            elseif($tab = $sqlm->fetch_row($sqlm->query("SELECT tab, row, col from dbc_talent where rank4 = $talent[0]")))
              $tabs[$tab[0]][$tab[1]][$tab[2]] = array($talent[0],'4');
            elseif($tab = $sqlm->fetch_row($sqlm->query("SELECT tab, row, col from dbc_talent where rank3 = $talent[0]")))
              $tabs[$tab[0]][$tab[1]][$tab[2]] = array($talent[0],'3');
            elseif($tab = $sqlm->fetch_row($sqlm->query("SELECT tab, row, col from dbc_talent where rank2 = $talent[0]")))
              $tabs[$tab[0]][$tab[1]][$tab[2]] = array($talent[0],'2');
            elseif($tab = $sqlm->fetch_row($sqlm->query("SELECT tab, row, col from dbc_talent where rank1 = $talent[0]")))
              $tabs[$tab[0]][$tab[1]][$tab[2]] = array($talent[0],'1');
          }
          $output .="
                <tr valign=\"top\">";
          foreach ($tabs as $data)
          {
            $output .="
                  <td>
                    <table style=\"width: 0px;\">";
            for($i=0;$i<11;$i++)
            {
              $output .="
                      <tr>";
              for($j=0;$j<4;$j++)
              {
                if(isset($data[$i][$j]))
                  $output .="
                        <td>
                          <a href=\"$spell_datasite".$data[$i][$j][0]."\" target=\"_blank\">
                            <img src=\"".get_spell_icon($data[$i][$j][0])."\" width=\"36\" height=\"36\" alt=\"\" />
                          </a>
                        </td>";
                else
                  $output .="
                        <td>
                          <a href=#>
                            <img src=\"img/blank.gif\" width=\"36\" height=\"36\" alt=\"\" />
                          </a>
                        </td>";
              }
              $output .="
                      </tr>";
            }
            $output .="
                    </table>
                  </td>";
          }
        }
        $output .= "
                </tr>";
      }
      else
      {
        $output .="
                <tr>
                  <th><a href=\"char_talent.php?id=$id&amp;realm=$realmid&amp;order_by=0&amp;dir=$dir\">".($order_by==0 ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."{$lang_char['talent_id']}</a></th>
                  <th align=\"left\"><a href=\"char_talent.php?id=$id&amp;realm=$realmid&amp;order_by=1&amp;dir=$dir\">".($order_by==1 ? "<img src=\"img/arr_".($dir ? "up" : "dw").".gif\" alt=\"\" /> " : "")."{$lang_char['talent_name']}</a></th>
                </tr>";
        $talents_1 = array();
        if ($sqlc->num_rows($result))
        {
          while ($talent = $sqlc->fetch_row($result))
          {
            if(talent_get_value($talent[0]))
              array_push($talents_1, array($talent[0], get_spell_name($talent[0])));
          }
          aasort($talents_1, $order_by, $dir);
          //if ($GMP)
          //  $talent_sum = gmp_init(0);
          foreach ($talents_1 as $data)
          {
            $output .= "
                <tr>
                  <td>$data[0]</td>
                  <td align=\"left\">
                    <a style=\"padding:2px;\" href=\"$spell_datasite$data[0]\" target=\"_blank\">
                      <img src=\"".get_spell_icon($data[0])."\" alt=\"\" />
                    </a>
                    <a href=\"$spell_datasite$data[0]\" target=\"_blank\">$data[1]</a>
                  </td>";
            //if ($GMP)
            //  $talent_sum = gmp_add($talent_sum,sprintf('%s',talent_get_value($data[0])));
            $output .= "
                </tr>";
          }
        }
        /*
        // reserved till we get to calculate talent points using the new data we have in db
        $playerclass = strtolower(char_get_class_name($char[3]));
        switch ($playerclass)
        {
          case "shaman":
            $padlength = 61;
            break;
          case "druid":
            $padlength = 62;
            break;
          case "warlock":
          case "paladin":
          case "hunter":
          case "priest":
            $padlength = 64;
            break;
          case "warrior":
            $padlength = 66;
            break;
          case "rogue":
          case "mage":
            $padlength = 67;
            break;
        }
        if ($GMP)
          $output .= "
                <tr>
                  <td>
                    <a href=\"".$talent_calculator_datasite.$char[3]."&tal=".str_pad(sprintf('%s',gmp_strval($talent_sum)), "0", "0", STR_PAD_LEFT)."\" target=\"_blank\">Talent Calculator</a>
                  </td>
                </tr>";
        */
      }
      $output .= "
            </table>
          </div>
          <br />
          <table class=\"hidden\">
            <tr>
              <td>";
                makebutton($lang_char['chars_acc'], "user.php?action=edit_user&amp;id=$owner_acc_id",130);
      $output .= "
              </td>
              <td>";
      if (($user_lvl > $owner_gmlvl)&&($user_lvl >= $action_permission['delete']))
      {
                makebutton($lang_char['edit_button'], "char_edit.php?id=$id&amp;realm=$realmid",130);
        $output .= "
              </td>
              <td>";
      }
      if ((($user_lvl > $owner_gmlvl)&&($user_lvl >= $action_permission['delete']))||($owner_name == $user_name))
      {
                makebutton($lang_char['del_char'], "char_list.php?action=del_char_form&amp;check%5B%5D=$id\" type=\"wrn",130);
        $output .= "
              </td>
              <td>";
      }
      if ($user_lvl >= $action_permission['update'])
      {
                makebutton($lang_char['send_mail'], "mail.php?type=ingame_mail&amp;to=$char[1]",130);
        $output .= "
              </td>
              <td>";
      }
                makebutton($lang_global['back'], "javascript:window.history.back()\" type=\"def",130);
      $output .= "
              </td>
            </tr>
          </table>
          <br />
        </center>
";
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

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

$lang_char = lang_char();

switch ($action)
{

  case "unknown":
    break;
  default:
    char_talent();
}

unset($action);
unset($action_permission);
unset($lang_char);

require_once("footer.php");

?>
