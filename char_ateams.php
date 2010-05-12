<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
//valid_login($action_permission['read']);

//########################################################################################################################
// SHOW CHAR REPUTATION
//########################################################################################################################
function char_teams(&$sqlr, &$sqlc)
{
  global $output, $lang_global, $lang_char,
    $realm_id, $characters_db, $mmfpm_db,
    $action_permission, $user_lvl, $user_name,
    $lang_arenateam;

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

	$id=intval($_GET['id']);
  //$id = $sqlc->quote_smart($_GET['id']);
  //if (is_numeric($id)); else $id = 0;

  $result = $sqlc->query('SELECT account, name, race, class, level, gender FROM characters WHERE guid = '.$id.' LIMIT 1');

  if ($sqlc->num_rows($result))
  {
    $char = $sqlc->fetch_assoc($result);

    // we get user permissions first
    $owner_acc_id = $sqlc->result($result, 0, 'account');
    $result = $sqlr->query('SELECT gmlevel, username FROM account WHERE id = '.$char['account'].'');
    $owner_gmlvl = $sqlr->result($result, 0, 'gmlevel');
    $owner_name = $sqlr->result($result, 0, 'username');

    if (($user_lvl >= $owner_gmlvl)||($owner_name === $user_name)||($owner_gmlvl <= 2))
    {
      $output .= '
          <center>
            <div id="tab">
              <ul>';
      if (($user_lvl > $owner_gmlvl)||($owner_name === $user_name))
      {
        $output .= '
                <li id="selected"><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
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
                  <li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>';
        if (char_get_class_name($char['class']) === 'Hunter' )
          $output .= '
                  <li><a href="char_pets.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['pets'].'</a></li>';
        $output .= '
                  <li><a href="char_rep.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['reputation'].'</a></li>
                  <li><a href="char_skill.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['skills'].'</a></li>
                  <li id="selected"><a href="char_ateams.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['arena_teams'].'</a></li>';
      }
      else
        $output .='<li><a href="char.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['char_sheet'].'</a></li>
            <li id="selected"><a href="char_ateams.php?id='.$id.'&amp;realm='.$realmid.'">'.$lang_char['arena_teams'].'</a></li>
              </ul>
            </div>
            <div id="tab_content">
              <div id="tab">
                <ul>';
      $output .= '</ul>
              </div>
              <div id="tab_content2">
                <font class="bold">
                  '.htmlentities($char['name']).' -
                  <img src="img/c_icons/'.$char['race'].'-'.$char['gender'].'.gif"
                    onmousemove="toolTip(\''.char_get_race_name($char['race']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" />
                  <img src="img/c_icons/'.$char['class'].'.gif"
                    onmousemove="toolTip(\''.char_get_class_name($char['class']).'\', \'item_tooltip\')" onmouseout="toolTip()" alt="" /> - lvl '.char_get_level_color($char['level']).'
                </font>
                <br /><br />';



      $sqlm = new SQL;
      $sqlm->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);
      $result = $sqlc->query('SELECT at.arenateamid, at.name, at.type, ats.rating, ats.games, ats.wins, ats.played, ats.wins2, ats.rank FROM arena_team at LEFT JOIN arena_team_stats ats ON at.arenateamid = ats.arenateamid WHERE at.arenateamid in (SELECT arenateamid FROM arena_team_member WHERE guid='.$id.') ORDER BY at.type ASC');
      $output .= '<tr><td colspan=3>';
      if ($sqlc->num_rows($result))
      {
      	$output .= '<table class="lined" style="width: 550px;"><tr><td>Team name</td><td>Type</td><td>Played</td><td>Wins</td><td>Loses</td><td>Rank</td><td>Rating</td></tr>';
      	while($r=$sqlc->fetch_assoc($result))
      	{
      		$output .= '<tr>
      				<td><a href="arenateam.php?action=view_team&id='.$r['arenateamid'].'">'.$r['name'].'</a></td>
      				<td>'.$lang_arenateam[$r['type']].'</td>
      				<td>'.$r['played'].'</td>
      				<td>'.$r['wins'].'</td>
      				<td>'.($r['played']-$r['wins']).'</td>
      				<td>'.$r['rank'].'</td>
      				<td>'.$r['rating'].'</td>
      				</tr>';
      	}
      	$output .= '</td></tr></table></td></tr>';
      }
      else
        $output .= '
                        <tr>
                          <td colspan="2"><br /><br />'.$lang_global['err_no_records_found'].'<br /><br /></td>
                        </tr>';


      $output .= '
                <br />
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


                  makebutton($lang_global['back'], 'javascript:window.history.back()" type="def', 130);
      $output .= '
                </td>
              </tr>
            </table>
            <br />
          </center>
          ';
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

char_teams($sqlr, $sqlc);

//unset($action);
unset($action_permission);
unset($lang_char);

require_once 'footer.php';


?>
