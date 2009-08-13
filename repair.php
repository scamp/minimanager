<?php


require_once("header.php");
valid_login($action_permission['read']);

//##############################################################################################
// PRINT REPAIR/OPTIMIZE FORM
//##############################################################################################
function repair_form()
{
  global $lang_global, $lang_repair, $output, $realm_db, $realm_id, $world_db, $characters_db, $mmfpm_db, $action_permission, $user_lvl;

  $output .= "
        <center>
          <fieldset class=\"tquarter_frame\">
            <legend>{$lang_repair['repair_optimize']}</legend>";
  if($user_lvl >= $action_permission['update'])
  {
    $output .= "
            <form action=\"repair.php?action=do_repair\" method=\"post\" name=\"form\">
              <table class=\"hidden\">
                <tr>
                  <td>
                    <select name=\"repair_action\">
                      <option value=\"REPAIR\">{$lang_repair['repair']}</option>
                      <option value=\"OPTIMIZE\">{$lang_repair['optimize']}</option>
                    </select>
                  </td>
                  <td>";
                  makebutton($lang_repair['start'], "javascript:do_submit()\" type=\"wrn",130);
    $output .= "
                  </td>
                  <td>";
                  makebutton($lang_global['back'], "javascript:window.history.back()\" type=\"def",130);
    $output .= "
                  </td>
                </tr>
              </table>
              <p>{$lang_repair['select_tables']}</p>";
  }
  $output .="
              <script type=\"text/javascript\" src=\"js/check.js\"></script>
              <table style=\"width: 550px;\" class=\"lined\">
                <tr>";
  $sql = new SQL;
  if($user_lvl >= $action_permission['update'])
    $output .= "
                  <th width=\"5%\"><input name=\"allbox\" type=\"checkbox\" value=\"Check All\" onclick=\"CheckAll(document.form);\" /></th>";
  $output .= "
                  <th width=\"25%\">{$lang_repair['table_name']}</th>
                  <th width=\"35%\">{$lang_repair['status']}</th>
                  <th width=\"15%\">{$lang_repair['num_records']}</th>
                </tr>
              </table>";
  $mm_dbs=array($realm_db, $mmfpm_db);
  foreach ($mm_dbs as $db)
  {
    $output.="
              <table style=\"width: 550px;\" class=\"lined\">
                <tr class=\"large_bold\">
                  <td colspan=\"3\" class=\"hidden\" align=\"left\">
                    <a href=\"#\" onclick=\"showHide('{$db['name']}')\">Show/Hide </a>
                    {$db['name']} {$lang_repair['tables']} :
                  </td>
                </tr>
              </table>
              <table id=\"".$db['name']."\" style=\"width: 550px; display: none;\" class=\"lined\">";
    $sql->connect($db['addr'], $db['user'], $db['pass'], $db['name']);
    $result = $sql->query("SHOW TABLES FROM {$db['name']}");

    while ($table = $sql->fetch_row($result))
    {
      $result1 = $sql->query("SELECT count(*) FROM `$table[0]`");
      $result2 = $sql->query("CHECK TABLE `$table[0]` CHANGED");
      $output .= "
                <tr>";
      if($user_lvl >= $action_permission['update'])
        $output .= "
                  <td>
                    <input type=\"checkbox\" name=\"check[]\" value=\"db~0~{$db['name']}~$table[0]\" onclick=\"CheckCheckAll(document.form);\" />
                  </td>";
      $output .= "
                  <td>$table[0]</td>
                  <td>".$sql->result($result2, 0, 'Msg_type')." : ".$sql->result($result2, 0, 'Msg_text')."</td>
                  <td>".$sql->result($result1, 0)."</td>
                </tr>";
    }
    $output .="
              </table>";
  }
  $mm_dbs=array($world_db, $characters_db);
  foreach ($mm_dbs as $dbs)
  {
    foreach ($dbs as $db)
    {
      $output .= "
              <table style=\"width: 550px;\" class=\"lined\">
                <tr class=\"large_bold\">
                  <td colspan=\"3\" class=\"hidden\" align=\"left\">
                    <a href=\"#\" onclick=\"showHide('{$db['name']}{$db['id']}')\">{$lang_repair['showhide']} </a>
                    {$db['name']} Realm {$db['id']} Tables :
                  </td>
                </tr>
              </table>
              <table id=\"".$db['name']."".$db['id']."\" style=\"width: 550px; display: none;\" class=\"lined\">";
      $sql->connect($db['addr'], $db['user'], $db['pass'], $db['name']);
      $result = $sql->query("SHOW TABLES FROM {$db['name']}");

      while ($table = $sql->fetch_row($result))
      {
        $result1 = $sql->query("SELECT count(*) FROM `$table[0]`");
        $result2 = $sql->query("CHECK TABLE `$table[0]` CHANGED");
        $output .= "
                <tr>";
        if($user_lvl >= $action_permission['update'])
          $output .= "
                  <td>
                    <input type=\"checkbox\" name=\"check[]\" value=\"db~{$db['id']}~{$db['name']}~$table[0]\" onclick=\"CheckCheckAll(document.form);\" />
                  </td>";
        $output .= "
                  <td>$table[0]</td>
                  <td>".$sql->result($result2, 0, 'Msg_type')." : ".$sql->result($result2, 0, 'Msg_text')."</td>
                  <td>".$sql->result($result1, 0)."</td>
                </tr>";
      }
      $output .="
              </table>";
    }
  }
  $output .= "
            </form>
          </fieldset>
          <br /><br />
        </center>
";

}


//##############################################################################################
// EXECUTE TABLE REPAIR OR OPTIMIZATION
//##############################################################################################
function do_repair()
{
  global $output, $realm_db, $mmfpm_db, $world_db, $characters_db, $server_type, $action_permission;
  valid_login($action_permission['update']);
  if ((!isset($_POST['repair_action']) && $_POST['repair_action'] === '') || (!isset($_POST['check'])) )
  {
    redirect("repair.php?error=1");
  }
  else
  {
    $table_list = $_POST['check'];
    $table_action = addslashes($_POST['repair_action']);
  }

  $sql = new SQL;
  $counter = 0;

  foreach($table_list as $table)
  {
    $table_data = explode("~", $table);
    if ($table_data[2] == "realmd")
    {
      $sql->connect($realm_db['addr'], $realm_db['user'], $realm_db['pass'], $realm_db['name']);
    }
    elseif ($table_data[2] == "mmfpm")
    {
      $sql->connect($mmfpm_db['addr'], $mmfpm_db['user'], $mmfpm_db['pass'], $mmfpm_db['name']);
    }
    elseif ($server_type && $table_data[2] == "trinity")
    {
      $sql->connect($world_db[$table_data[1]]['addr'], $world_db[$table_data[1]]['user'], $world_db[$table_data[1]]['pass']);
    }
    elseif (!$server_type && $table_data[2] == "mangos")
    {
      $sql->connect($world_db[$table_data[1]]['addr'], $world_db[$table_data[1]]['user'], $world_db[$table_data[1]]['pass']);
    }
    elseif  ($table_data[2] == "characters")
    {
      $sql->connect($characters_db[$table_data[1]]['addr'], $characters_db[$table_data[1]]['user'], $characters_db[$table_data[1]]['pass']);
    }

     $result = $sql->query("$table_action TABLE {$table_data[2]}.`{$table_data[3]}`");
     $action_result = $sql->fetch_row($result);

     if ($action_result[3] === "OK") $counter++;
      else $err = $action_result[3];
  }

  if ($counter)
    redirect("repair.php?error=2&num=$counter");
  else
    redirect("repair.php?error=4&rep_err=$err");

}


//########################################################################################################################
// MAIN
//########################################################################################################################
$err = (isset($_GET['error'])) ? $_GET['error'] : NULL;
$num = (isset($_GET['num'])) ? $_GET['num'] : NULL;
$rep_err = (isset($_GET['rep_err'])) ? $_GET['rep_err'] : NULL;

$output .= "
        <div class=\"top\">";

$lang_repair = lang_repair();

switch ($err)
{
  case 1:
    $output .= "
          <h1><font class=\"error\">{$lang_global['empty_fields']}</font></h1>";
    break;
  case 2:
    $output .= "
          <h1><font class=\"error\">{$lang_repair['repair_finished']} : $num {$lang_repair['tables']}</font></h1>";
    break;
  case 3:
    $output .= "
          <h1><font class=\"error\">{$lang_repair['no_table_selected']}</font></h1>";
    break;
  case 4:
    $output .= "
          <h1><font class=\"error\">{$lang_repair['repair_error']} : $rep_err</font></h1>";
    break;
  default: //no error
    $output .= "
          <h1>{$lang_repair['repair_optimize']}</h1>";
}
$output .= "
        </div>";

unset($err);
unset($num);
unset($rep_err);

$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($action)
{
  case "repair_form":
    repair_form();
    break;
  case "do_repair":
    do_repair();
    break;
  default:
    repair_form();
}

unset($action);
unset($action_permission);
unset($lang_repair);

include_once("footer.php");

?>
