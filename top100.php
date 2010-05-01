<?php


require_once 'header.php';
require_once 'libs/char_lib.php';
valid_login($action_permission['read']);

function top100($realmid, &$sqlr, &$sqlc)
{
  global $output, $lang_top,
    $realm_db, $characters_db, $server,
    $itemperpage, $developer_test_mode, $multi_realm_mode;

  $realm_id = $realmid;

  $sqlc->connect($characters_db[$realm_id]['addr'], $characters_db[$realm_id]['user'], $characters_db[$realm_id]['pass'], $characters_db[$realm_id]['name']);
  $where='';
  //==========================$_GET and SECURE========================
  $type = (isset($_GET['type'])) ? $sqlc->quote_smart($_GET['type']) : 'level';
  if (preg_match('/^[_[:lower:]]{1,10}$/', $type)); else $type = 'level';

  $start = (isset($_GET['start'])) ? $sqlc->quote_smart($_GET['start']) : 0;
  if (is_numeric($start)); else $start=0;

  if(isset($_GET['order_by']) && in_array($_GET['order_by'], array('level', 'totaltime', 'online', 'money', 'health', 'mana', 'str', 'agi', 'sta', 'intel', 'spi', 'armor', 'block', 'dodge', 'parry', 'ap', 'ranged_ap', 'min_dmg', 'max_dmg', 'min_ranged_dmg', 'max_ranged_dmg', 'expertise', 'off_expertise', 'holy', 'fire', 'nature', 'frost', 'shadow','arcane', 'melee_crit', 'range_crit', 'melee_hit', 'range_hit', 'spell_hit', 'honor', 'kills', 'arena', 'spell_dmg_0', 'spell_dmg_1', 'spell_dmg_2', 'spell_dmg_3', 'spell_dmg_4', 'spell_dmg_5', 'spell_dmg_min', 'spell_crit_0', 'spell_crit_1', 'spell_crit_2', 'spell_crit_3', 'spell_crit_4', 'spell_crit_5', 'spell_crit'))) $order_by=$_GET['order_by'];
  else $order_by = 'level';

  $dir = (isset($_GET['dir'])) ? intval($_GET['dir'])%2 : 1;

  $order_dir = ($dir) ? 'DESC' : 'ASC';
  //$dir = ($dir) ? 0 : 1;
  //==========================$_GET and SECURE end========================
  if($order_by=='ap')$where=' WHERE (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_AP+1).'),                " ", -1) AS UNSIGNED) + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_AP_MOD+1).')," ", -1) AS UNSIGNED))<>2147483647 ';

  $type_list = array('level', 'stat', 'defense', 'attack', 'resist', 'crit_hit', 'pvp', 'spd');
  if (in_array($type, $type_list));
    else $type = 'level';

  $result = $sqlc->query('SELECT count(*) FROM characters');
  $all_record = $sqlc->result($result, 0);
  $all_record = (($all_record < 100) ? $all_record : 100);

  $result = $sqlc->query('SELECT guid, name, race, class, gender, level, totaltime, online, money,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_GUILD_ID+1).'),          " ", -1) AS UNSIGNED) as gname,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MAX_HEALTH+1).'),        " ", -1) AS UNSIGNED) AS health,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MAX_MANA+1).'),          " ", -1) AS UNSIGNED) AS mana,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_STR+1).'),               " ", -1) AS UNSIGNED) AS str,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_AGI+1).'),               " ", -1) AS UNSIGNED) AS agi,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_STA+1).'),               " ", -1) AS UNSIGNED) AS sta,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_INT+1).'),               " ", -1) AS UNSIGNED) AS intel,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPI+1).'),               " ", -1) AS UNSIGNED) AS spi,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_ARMOR+1).'),             " ", -1) AS UNSIGNED) AS armor,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_BLOCK+1).'),             " ", -1) AS UNSIGNED) AS block,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_DODGE+1).'),             " ", -1) AS UNSIGNED) AS dodge,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_PARRY+1).'),             " ", -1) AS UNSIGNED) AS parry,
   (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_AP+1).'),                " ", -1) AS UNSIGNED)
  + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_AP_MOD+1).'),            " ", -1) AS UNSIGNED)) AS ap,
   (CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RANGED_AP+1).'),         " ", -1) AS UNSIGNED)
  + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RANGED_AP_MOD+1).'),     " ", -1) AS UNSIGNED)) AS ranged_ap,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MINDAMAGE+1).'),         " ", -1) AS UNSIGNED) AS min_dmg,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MAXDAMAGE+1).'),         " ", -1) AS UNSIGNED) AS max_dmg,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MINRANGEDDAMAGE+1).'),   " ", -1) AS UNSIGNED) AS min_ranged_dmg,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MAXRANGEDDAMAGE+1).'),   " ", -1) AS UNSIGNED) AS max_ranged_dmg,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_EXPERTISE+1).'),         " ", -1) AS UNSIGNED) AS expertise,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_OFFHAND_EXPERTISE+1).'), " ", -1) AS UNSIGNED) AS off_expertise,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_HOLY+1).'),          " ", -1) AS UNSIGNED) AS holy,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_FIRE+1).'),          " ", -1) AS UNSIGNED) AS fire,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_NATURE+1).'),        " ", -1) AS UNSIGNED) AS nature,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_FROST+1).'),         " ", -1) AS UNSIGNED) AS frost,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_SHADOW+1).'),        " ", -1) AS UNSIGNED) AS shadow,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RES_ARCANE+1).'),        " ", -1) AS UNSIGNED) AS arcane,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MELEE_CRIT+1).'),        " ", -1) AS UNSIGNED) AS melee_crit,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RANGE_CRIT+1).'),        " ", -1) AS UNSIGNED) AS range_crit,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_MELEE_HIT+1).'),         " ", -1) AS UNSIGNED) AS melee_hit,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_RANGE_HIT+1).'),         " ", -1) AS UNSIGNED) AS range_hit,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_HIT+1).'),         " ", -1) AS UNSIGNED) AS spell_hit,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_HONOR_POINTS+1).'),      " ", -1) AS UNSIGNED) AS honor,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_HONOR_KILL+1).'),        " ", -1) AS UNSIGNED) AS kills,
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_ARENA_POINTS+1).'),      " ", -1) AS UNSIGNED) AS arena,
	@spell_dmg_1:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+2).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_0,
	@spell_dmg_2:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+3).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_1,
	@spell_dmg_3:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+4).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_2,
	@spell_dmg_4:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+5).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_3,
	@spell_dmg_5:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+6).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_4,
	@spell_dmg_6:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_DAMAGE+7).'),      " ", -1)  AS UNSIGNED) AS spell_dmg_5,
	CAST(IF(IF(IF(IF(IF(@spell_dmg_2<@spell_dmg_1,@cmin:=@spell_dmg_2,@cmin:=@spell_dmg_1)>@spell_dmg_3,@cmin:=@spell_dmg_3,@cmin)<@spell_dmg_4,@cmin:=@spell_dmg_4,@cmin)>@spell_dmg_5,@cmin:=@spell_dmg_5,@cmin)>@spell_dmg_6,@cmin:=@spell_dmg_6,@cmin) as UNSIGNED) AS spell_dmg_min,
	@spell_crit_1:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+2).'),      " ", -1)  AS UNSIGNED) AS spell_crit_0,
	@spell_crit_2:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+3).'),      " ", -1)  AS UNSIGNED) AS spell_crit_1,
	@spell_crit_3:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+4).'),      " ", -1)  AS UNSIGNED) AS spell_crit_2,
	@spell_crit_4:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+5).'),      " ", -1)  AS UNSIGNED) AS spell_crit_3,
	@spell_crit_5:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+6).'),      " ", -1)  AS UNSIGNED) AS spell_crit_4,
	@spell_crit_6:=CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(data, " ", '.(CHAR_DATA_OFFSET_SPELL_CRIT+7).'),      " ", -1)  AS UNSIGNED) AS spell_crit_5,
CAST(if(if(if(if(if(@spell_crit_2<@spell_crit_1,@cmin:=@spell_crit_2,@cmin:=@spell_crit_1)>@spell_crit_3,@cmin:=@spell_crit_3,@cmin)<@spell_crit_4,@cmin:=@spell_crit_4,@cmin)>@spell_crit_5,@cmin:=@spell_crit_5,@cmin)>@spell_crit_6,@cmin:=@spell_crit_6,@cmin) as UNSIGNED) AS spell_crit
    FROM characters '.$where.' ORDER BY '.$order_by.' '.$order_dir.' LIMIT '.$start.', '.$itemperpage.'');


  //==========================top tage navigaion starts here========================
  $output .= '
          <center>
            <div id="tab">
              <ul>
                <li'.(($type === 'level') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'">
                    '.$lang_top['general'].'
                  </a>
                </li>
                <li'.(($type === 'stat') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=stat&amp;order_by=health">
                    '.$lang_top['stats'].'
                  </a>
                </li>
                <li'.(($type === 'defense') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=defense&amp;order_by=armor">
                    '.$lang_top['defense'].'
                  </a>
                </li>
                <li'.(($type === 'resist') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=resist&amp;order_by=holy">
                    '.$lang_top['resist'].'
                  </a>
                </li>
                <li'.(($type === 'attack') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=attack&amp;order_by=ap">
                    '.$lang_top['melee'].'
                  </a>
                </li>
                <li'.(($type === 'crit_hit') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=crit_hit&amp;order_by=ranged_ap">
                    '.$lang_top['ranged'].'
                  </a>
                </li>
                <li'.(($type === 'spd') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=spd&amp;order_by=spell_dmg_min">
                    '.$lang_top['spell'].'
                  </a>
                </li>
                <li'.(($type === 'pvp') ? ' id="selected"' : '' ).'>
                  <a href="top100.php?start='.$start.'&amp;type=pvp&amp;order_by=honor">
                    '.$lang_top['pvp'].'
                  </a>
                </li>
              </ul>
            </div>
            <div id="tab_content">
            <table class="top_hidden" style="width: 720px">';
  if($developer_test_mode && $multi_realm_mode)
  {
    $realms = $sqlr->query('SELECT count(*) FROM realmlist');
    $tot_realms = $sqlr->result($realms, 0);
    if (1 < $tot_realms && 1 < count($server))
    {
      $output .= '
              <tr>
                <td colspan="2" align="left">';
                  makebutton('View', 'javascript:do_submit(\'form'.$realm_id.'\',0)', 130);
      $output .= '
                  <form action="top100.php?type='.$type.'" method="post" name="form'.$realm_id.'">
                    Number of Realms :
                    <input type="hidden" name="action" value="realms" />
                    <select name="n_realms">';
      for($i=1;$i<=$tot_realms;++$i)
        $output .= '
                      <option value="'.$i.'">'.htmlentities($i).'</option>';
      $output .= '
                    </select>
                  </form>
                </td>
              </tr>';
    }
  }
  $output .= '
              <tr>
                <td align="right">Total: '.$all_record.'</td>
                <td align="right" width="25%">';
  $output .= generate_pagination('top100.php?type='.$type.'&amp;order_by='.$order_by.'&amp;dir='.(($dir) ? 0 : 1).'', $all_record, $itemperpage, $start);
  $output .= '
                </td>
              </tr>
            </table>';
  //==========================top tage navigaion ENDS here ========================
  $output .= '
            <table class="lined" style="width: 720px">
              <tr>
                <th width="5%">#</th>
                <th width="14%">'.$lang_top['name'].'</th>
                <th width="11%">'.$lang_top['race'].' '.$lang_top['class'].'</th>
                <th width="8%"><a href="top100.php?type='.$type.'&amp;order_by=level&amp;start='.$start.'&amp;dir='.direct('level').'"'.($order_by==='level' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['level'].'</a></th>';
  if ($type === 'level')
  {
    $output .= '
                <th width="22%">'.$lang_top['guild'].'</th>
                <th width="20%"><a href="top100.php?type='.$type.'&amp;order_by=money&amp;start='.$start.'&amp;dir='.direct('money').'"'.($order_by==='money' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['money'].'</a></th>
                <th width="20%"><a href="top100.php?type='.$type.'&amp;order_by=totaltime&amp;start='.$start.'&amp;dir='.direct('totaltime').'"'.($order_by==='totaltime' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['time_played'].'</a></th>';
  }
  elseif ($type === 'stat')
  {
    $output .= '
                <th width="11%"><a href="top100.php?type='.$type.'&amp;order_by=health&amp;start='.$start.'&amp;dir='.direct('health').'"'.($order_by==='health' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['health'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=mana&amp;start='.$start.'&amp;dir='.direct('mana').'"'.($order_by==='mana' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['mana'].'</a></th>
                <th width="9%"><a href="top100.php?type='.$type.'&amp;order_by=str&amp;start='.$start.'&amp;dir='.direct('str').'"'.($order_by==='str' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['str'].'</a></th>
                <th width="8%"><a href="top100.php?type='.$type.'&amp;order_by=agi&amp;start='.$start.'&amp;dir='.direct('agi').'"'.($order_by==='agi' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['agi'].'</a></th>
                <th width="8%"><a href="top100.php?type='.$type.'&amp;order_by=sta&amp;start='.$start.'&amp;dir='.direct('sta').'"'.($order_by==='sta' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['sta'].'</a></th>
                <th width="8%"><a href="top100.php?type='.$type.'&amp;order_by=intel&amp;start='.$start.'&amp;dir='.direct('intel').'"'.($order_by==='intel' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['intel'].'</a></th>
                <th width="8%"><a href="top100.php?type='.$type.'&amp;order_by=spi&amp;start='.$start.'&amp;dir='.direct('spi').'"'.($order_by==='spi' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['spi'].'</a></th>';
  }
  elseif ($type === 'defense')
  {
    $output .= '
                <th width="16%"><a href="top100.php?type='.$type.'&amp;order_by=armor&amp;start='.$start.'&amp;dir='.direct('armor').'"'.($order_by==='armor' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['armor'].'</a></th>
                <th width="16%"><a href="top100.php?type='.$type.'&amp;order_by=block&amp;start='.$start.'&amp;dir='.direct('block').'"'.($order_by==='block' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['block'].'</a></th>
                <th width="15%"><a href="top100.php?type='.$type.'&amp;order_by=dodge&amp;start='.$start.'&amp;dir='.direct('dodge').'"'.($order_by==='dodge' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['dodge'].'</a></th>
                <th width="15%"><a href="top100.php?type='.$type.'&amp;order_by=parry&amp;start='.$start.'&amp;dir='.direct('parry').'"'.($order_by==='parry' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['parry'].'</a></th>';
  }
  elseif ($type === 'resist')
  {
    $output .= '
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=holy&amp;start='.$start.'&amp;dir='.direct('holy').'"'.($order_by==='holy' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['holy'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=fire&amp;start='.$start.'&amp;dir='.direct('fire').'"'.($order_by==='fire' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['fire'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=nature&amp;start='.$start.'&amp;dir='.direct('nature').'"'.($order_by==='nature' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['nature'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=frost&amp;start='.$start.'&amp;dir='.direct('frost').'"'.($order_by==='frost' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['frost'].'</a></th>
                <th width="11%"><a href="top100.php?type='.$type.'&amp;order_by=shadow&amp;start='.$start.'&amp;dir='.direct('shadow').'"'.($order_by==='shadow' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['shadow'].'</a></th>
                <th width="11%"><a href="top100.php?type='.$type.'&amp;order_by=arcane&amp;start='.$start.'&amp;dir='.direct('arcane').'"'.($order_by==='arcane' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['arcane'].'</a></th>';
  }
  elseif ($type === 'attack')
  {
    $output .= '
                <th width="20%"><a href="top100.php?type='.$type.'&amp;order_by=ap&amp;start='.$start.'&amp;dir='.direct('ap').'"'.($order_by==='ap' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['ap'].'</a></th>
                <th width="6%"><a href="top100.php?type='.$type.'&amp;order_by=min_dmg&amp;start='.$start.'&amp;dir='.direct('min_dmg').'"'.($order_by==='min_dmg' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['min_dmg'].'</a></th>
                <th width="6%"><a href="top100.php?type='.$type.'&amp;order_by=max_dmg&amp;start='.$start.'&amp;dir='.direct('max_dmg').'"'.($order_by==='max_dmg' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['max_dmg'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=melee_crit&amp;start='.$start.'&amp;dir='.direct('melee_crit').'"'.($order_by==='melee_crit' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['crit'].'</a></th>
                <th width="5%"><a href="top100.php?type='.$type.'&amp;order_by=melee_hit&amp;start='.$start.'&amp;dir='.direct('melee_hit').'"'.($order_by==='melee_hit' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['hit'].'</a></th>
                <th width="5%"><a href="top100.php?type='.$type.'&amp;order_by=expertise&amp;start='.$start.'&amp;dir='.direct('expertise').'"'.($order_by==='expertise' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['expertise'].'</a></th>
                <th width="9%"><a href="top100.php?type='.$type.'&amp;order_by=off_expertise&amp;start='.$start.'&amp;dir='.direct('off_expertise').'"'.($order_by==='off_expertise' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['off_expertise'].'</a></th>
              </tr>';
  }
  elseif ($type === 'crit_hit')
  {
    $output .= '
                <th width="18%"><a href="top100.php?type='.$type.'&amp;order_by=ranged_ap&amp;start='.$start.'&amp;dir='.direct('ranged_ap').'"'.($order_by==='ranged_ap' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['ap'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=min_ranged_dmg&amp;start='.$start.'&amp;dir='.direct('min_ranged_dmg').'"'.($order_by==='min_ranged_dmg' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['min_dmg'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=max_ranged_dmg&amp;start='.$start.'&amp;dir='.direct('max_ranged_dmg').'"'.($order_by==='max_ranged_dmg' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['max_dmg'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=range_crit&amp;start='.$start.'&amp;dir='.direct('range_crit').'"'.($order_by==='range_crit' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['crit'].'</a></th>
                <th width="10%"><a href="top100.php?type='.$type.'&amp;order_by=range_hit&amp;start='.$start.'&amp;dir='.direct('range_hit').'"'.($order_by==='range_hit' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['hit'].'</a></th>';
  }
  elseif ($type === 'pvp')
  {
    $output .= '
                <th width="20%"><a href="top100.php?type='.$type.'&amp;order_by=honor&amp;start='.$start.'&amp;dir='.direct('honor').'"'.($order_by==='honor' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['rank'].'</a></th>
                <th width="14%">'.$lang_top['honor_points'].'</th>
                <th width="14%"><a href="top100.php?type='.$type.'&amp;order_by=kills&amp;start='.$start.'&amp;dir='.direct('kills').'"'.($order_by==='kills' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['kills'].'</a></th>
                <th width="14%"><a href="top100.php?type='.$type.'&amp;order_by=arena&amp;start='.$start.'&amp;dir='.direct('arena').'"'.($order_by==='arena' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['arena_points'].'</a></th>';
  }
  elseif ($type === 'spd')
  {
    $output .= '
                <th width="18%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_min&amp;start='.$start.'&amp;dir='.direct('spell_dmg_min').'"'.($order_by==='spell_dmg_min' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['spd'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_0&amp;start='.$start.'&amp;dir='.direct('spell_dmg_0').'"'.($order_by==='spell_dmg_0' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['holy'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_1&amp;start='.$start.'&amp;dir='.direct('spell_dmg_1').'"'.($order_by==='spell_dmg_1' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['fire'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_2&amp;start='.$start.'&amp;dir='.direct('spell_dmg_2').'"'.($order_by==='spell_dmg_2' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['nature'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_3&amp;start='.$start.'&amp;dir='.direct('spell_dmg_3').'"'.($order_by==='spell_dmg_3' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['frost'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_4&amp;start='.$start.'&amp;dir='.direct('spell_dmg_4').'"'.($order_by==='spell_dmg_4' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['shadow'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_dmg_5&amp;start='.$start.'&amp;dir='.direct('spell_dmg_5').'"'.($order_by==='spell_dmg_5' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['arcane'].'</a></th>
                <th width="12%"><a href="top100.php?type='.$type.'&amp;order_by=spell_crit&amp;start='.$start.'&amp;dir='.direct('spell_crit').'"'.($order_by==='spell_crit' ? ' class="'.$order_dir.'"' : '').'>'.$lang_top['crit'].'</a></th>';
  }
  $output .= '
              </tr>';
  $i=0;
  while($char = $sqlc->fetch_assoc($result))
  {
    $output .= '
              <tr valign="top">
                <td>'.(++$i+$start).'</td>
                <td><a href="char.php?id='.$char['guid'].'&amp;realm='.$realm_id.'">'.htmlentities($char['name']).'</a></td>
                <td>
                  <img src="img/c_icons/'.$char['race'].'-'.$char['gender'].'.gif" alt="'.char_get_race_name($char['race']).'" onmousemove="toolTip(\''.char_get_race_name($char['race']).'\', \'item_tooltip\')" onmouseout="toolTip()" />
                  <img src="img/c_icons/'.$char['class'].'.gif" alt="'.char_get_class_name($char['class']).'" onmousemove="toolTip(\''.char_get_class_name($char['class']).'\', \'item_tooltip\')" onmouseout="toolTip()" />
                </td>
                <td>'.char_get_level_color($char['level']).'</td>';
    if ($type === 'level')
    {
      $guild_name = $sqlc->result($sqlc->query('SELECT name FROM guild WHERE guildid = '.$char['gname'].''), 0);
      $days  = floor(round($char['totaltime'] / 3600)/24);
      $hours = round($char['totaltime'] / 3600) - ($days * 24);
      $time = '';
      if ($days)
        $time .= $days.' days ';
      if ($hours)
        $time .= $hours.' hours';

      $output .= '
                <td><a href="guild.php?action=view_guild&amp;realm='.$realm_id.'&amp;error=3&amp;id='.$char['gname'].'">'.htmlentities($guild_name).'</a></td>
                <td align="right">
                  '.substr($char['money'],  0, -4).'<img src="img/gold.gif" alt="" align="middle" />
                  '.substr($char['money'], -4,  2).'<img src="img/silver.gif" alt="" align="middle" />
                  '.substr($char['money'], -2).'<img src="img/copper.gif" alt="" align="middle" />
                </td>
                <td align="right">'.$time.'</td>';
    }
    elseif ($type === 'stat')
    {
      $output .= '
                <td>'.$char['health'].'</td>
                <td>'.$char['mana'].'</td>
                <td>'.$char['str'].'</td>
                <td>'.$char['agi'].'</td>
                <td>'.$char['sta'].'</td>
                <td>'.$char['intel'].'</td>
                <td>'.$char['spi'].'</td>';
    }
    elseif ($type === 'defense')
    {
      $block = unpack('f', pack('L', $char['block']));
      $block = round($block[1],2);
      $dodge = unpack('f', pack('L', $char['dodge']));
      $dodge = round($dodge[1],2);
      $parry = unpack('f', pack('L', $char['parry']));
      $parry = round($parry[1],2);

      $output .= '
                <td>'.$char['armor'].'</td>
                <td>'.$block.'%</td>
                <td>'.$dodge.'%</td>
                <td>'.$parry.'%</td>';
    }
    elseif ($type === 'resist')
    {
      $output .= '
                <td>'.$char['holy'].'</td>
                <td>'.$char['fire'].'</td>
                <td>'.$char['nature'].'</td>
                <td>'.$char['frost'].'</td>
                <td>'.$char['shadow'].'</td>
                <td>'.$char['arcane'].'</td>';
    }
    elseif ($type === 'attack')
    {
      $melee = unpack('f', pack('L', $char['melee_crit']));
      $melee = round($melee[1],2);
      $mindamage = unpack('f', pack('L', $char['min_dmg']));
      $mindamage = round($mindamage[1],0);
      $maxdamage = unpack('f', pack('L', $char['max_dmg']));
      $maxdamage = round($maxdamage[1],0);

      $output .= '
                <td>'.$char['ap'].'</td>
                <td>'.$mindamage.'</td>
                <td>'.$maxdamage.'</td>
                <td>'.$melee.'%</td>
                <td>'.$char['melee_hit'].'</td>
                <td>'.$char['expertise'].'</td>
                <td>'.$char['off_expertise'].'</td>';
    }
    elseif ($type === 'crit_hit')
    {
      $range = unpack('f', pack('L', $char['range_crit']));
      $range = round($range[1],2);
      $minrangeddamage = unpack('f', pack('L', $char['min_ranged_dmg']));
      $minrangeddamage = round($minrangeddamage[1],0);
      $maxrangeddamage = unpack('f', pack('L', $char['max_ranged_dmg']));
      $maxrangeddamage = round($maxrangeddamage[1],0);

      $output .= '
                <td>'.$char['ranged_ap'].'</td>
                <td>'.$minrangeddamage.'</td>
                <td>'.$maxrangeddamage.'</td>
                <td>'.$range.'%</td>
                <td>'.$char['range_hit'].'</td>';
    }
    elseif ($type === 'pvp')
    {
      $output .= '
                <td align="left"><img src="img/ranks/rank'.char_get_pvp_rank_id($char['honor'], char_get_side_id($char['race'])).'.gif" alt=""></img> '.char_get_pvp_rank_name($char['honor'], char_get_side_id($char['race'])).'</td>
                <td>'.$char['honor'].'</td>
                <td>'.$char['kills'].'</td>
                <td>'.$char['arena'].'</td>';
    }
    elseif ($type === 'spd')
    {
    $tmp=unpack('f', pack('L', $char['spell_crit']));
      $output .= '
                <td>'.$char['spell_dmg_min'].'</td>
                <td>'.$char['spell_dmg_0'].'</td>
                <td>'.$char['spell_dmg_1'].'</td>
                <td>'.$char['spell_dmg_2'].'</td>
                <td>'.$char['spell_dmg_3'].'</td>
                <td>'.$char['spell_dmg_4'].'</td>
                <td>'.$char['spell_dmg_5'].'</td>
                <td>'.round($tmp[1], 2).'%</td>';
    }
    $output .= '
              </tr>';
  }
  $output .= '
            </table>
            <table class="top_hidden" style="width: 720px">
              <tr>
                <td align="right">Total: '.$all_record.'</td>
                <td align="right" width="25%">';
  $output .= generate_pagination('top100.php?type='.$type.'&amp;order_by='.$order_by.'&amp;dir='.(($dir) ? 0 : 1).'', $all_record, $itemperpage, $start);
  unset($all_record);
  $output .= '
                </td>
              </tr>
            </table>
            </div>
            <br />
          </center>';

}

function direct($col)
{
	$o=(isset($_GET['order_by'])?$_GET['order_by']:'');
	$d=(isset($_GET['dir'])?intval($_GET['dir']):0);
	return ($col==$o?($d?0:1):1);
}
//#############################################################################
// MAIN
//#############################################################################

//$err = (isset($_GET['error'])) ? $_GET['error'] : NULL;

//$output .= '
//          <div class="top">';

$lang_top = lang_top();

//if(1 == $err);
//else
//  $output .= "
//            <h1>'.$lang_top['top100'].'</h1>;

//unset($err);

//$output .= '
//          </div>';

$action = (isset($_POST['action'])) ? $_POST['action'] : NULL;

if ('realms' === $action)
{
  if (isset($_POST['n_realms']))
  {
    $n_realms = $_POST['n_realms'];

    $realms = $sqlr->query('SELECT id, name FROM realmlist LIMIT 10');

    if (1 < $sqlr->num_rows($realms) && 1 < (count($server)))
    {
      for($i=1;$i<=$n_realms;++$i)
      {
        $realm = $sqlr->fetch_assoc($realms);
        if(isset($server[$realm['id']]))
        {
          $output .= '
          <div class="top"><h1>Top 100 of '.$realm['name'].'</h1></div>';
          top100($realm['id'], $sqlr, $sqlc);
        }
      }
    }
    else
    {
      $output .= '
          <div class="top"><h1>'.$lang_top['top100'].'</h1></div>';
      top100($realm_id, $sqlr, $sqlc);
    }
  }
  else
  {
    $output .= '
          <div class="top"><h1>'.$lang_top['top100'].'</h1></div>';
    top100($realm_id, $sqlr, $sqlc);
  }
}
else
{
  $output .= '
          <div class="top"><h1>'.$lang_top['top100'].'</h1></div>';
  top100($realm_id, $sqlr, $sqlc);
}

unset($action);
unset($action_permission);
unset($lang_top);

require_once 'footer.php';


?>
