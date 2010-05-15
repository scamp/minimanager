<?php
///////////////////////////////////////
define('ITEMS_PER_FILE', 50);
///////////////////////////////////////
define('JSON_PATH', 'json');
define('DB_MANGOS', 'mangos');
define('DB_MANAGER', 'wmanager_16');
$help = "php {$argv[0]} [spells | items] [mysql_host] mysql_user mysql_pass\n  spells/items - get spells/items inf from db\n";
///////////////////////////////////////
if(isset($argv[4]) && strlen($argv[4]) > 0){
    $db_host = $argv[2];
    $db_user = $argv[3];
    $db_pass = $argv[4];
}else{
    $db_host = 'localhost';
    $db_user = $argv[2];
    $db_pass = $argv[3];
}
if(!$argv[1] || !$argv[3] || !$db_host)
    die($help);

$mangos_db = mysql_connect($db_host, $db_user, $db_pass) or die($help);



switch($argv[1]){

case 'spells':
    $count = mysql_fetch_row(mysql_query('SELECT count(*), max(t2.id) FROM '.DB_MANAGER.'.dbc_spellicon t1 JOIN '.DB_MANAGER.'.dbc_spell t2 ON t1.id = t2.field_139'));
    echo 'Count: ',$count[0] , "\n",
         'Max: ',$count[1] , "\n";
    
        for($i = 1; $i <= $count[1]; $i+=ITEMS_PER_FILE){
        $res = array();
        $sql = mysql_query('SELECT t2.id, t1.field_1 FROM '.DB_MANAGER.'.dbc_spellicon t1 JOIN '.DB_MANAGER.'.dbc_spell t2 ON t1.id = t2.field_139  WHERE t2.id >= '.$i.' AND t2.id < '.($i+ITEMS_PER_FILE));

        if($sql && mysql_num_rows($sql))
            while($sql2 = mysql_fetch_array($sql))
                $res[$sql2[0]] = substr($sql2[1], 16);
                
                    $f = fopen(JSON_PATH.'/spells/items_'.($i==1?'00':($i-1)).'.json', 'w');
                    fwrite($f, json_encode($res));
                    fclose($f);
                    if(($i-1) % (ITEMS_PER_FILE*50) == 0 && $i>1)
                        bar($i, $count[1]);

    }
    bar(0, 0);
break;

case 'items':
    $count = mysql_fetch_row(mysql_query('SELECT count(*), max(entry) FROM '.DB_MANGOS.'.item_template;'));
    echo 'Count: ',$count[0] , "\n",
         'Max: ',$count[1] , "\n";
    
    for($i = 1; $i <= $count[1]; $i+=ITEMS_PER_FILE){
        $res = array();
        $sql = mysql_query('SELECT entry, DisplayId, Quality FROM '.DB_MANGOS.'.item_template WHERE entry >= '.$i.' AND entry < '.($i+ITEMS_PER_FILE));

        if($sql && mysql_num_rows($sql))
            while($modelid = mysql_fetch_row($sql)){
                $sql2 = mysql_query('SELECT field_5 FROM '.DB_MANAGER.'.dbc_itemdisplayinfo WHERE id='.intval($modelid[1]).';');
                if($sql2 && mysql_num_rows($sql2) == 1)
                            $res[$modelid[0]] = array(mysql_result($sql2, 0), $modelid[2]);

            }

                    $f = fopen(JSON_PATH.'/items/items_'.($i==1?'00':($i-1)).'.json', 'w');
                    fwrite($f, json_encode($res));
                    fclose($f);
                    if(($i-1) % (ITEMS_PER_FILE*50) == 0 && $i>1)
                        bar($i, $count[1]);

    }
    bar(0, 0);
    break;
    default:
        echo $help;
}

mysql_close($mangos_db);

function bar($current = 0, $total = 100)
{
  if($total != 0)
    $p = round($current*100/$total);
  else
      $p = 100;
  if($p>10){
      echo "\x08";
      if($p>=100)
          echo "\x08";
      }
  echo "\x08\x08", $p, '%';

  if($current == $total)
        echo "\n";
}
?>