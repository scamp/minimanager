<?php
///////////////////////////////////////
define('ITEMS_PER_FILE', 50);
///////////////////////////////////////
define('DEFAULT_PATH', 'json');
define('DB_MANGOS', 'mangos');
define('DB_MANAGER', 'wmanager_16');
define('DB_USER', 'mangos');
define('DB_PASS', 'mangos');
define('DB_HOST', 'localhost');
///////////////////////////////////////

$mangos_db = mysql_connect(DB_HOST, DB_USER, DB_PASS);
$path = (empty($argv[2]) || $argv[2] == '')?DEFAULT_PATH:$argv[2];

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
                
                    $f = fopen($path.'/spells/items_'.($i==1?'00':($i-1)).'.json', 'w');
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

                    $f = fopen($path.'/items/items_'.($i==1?'00':($i-1)).'.json', 'w');
                    fwrite($f, json_encode($res));
                    fclose($f);
                    if(($i-1) % (ITEMS_PER_FILE*50) == 0 && $i>1)
                        bar($i, $count[1]);

    }
    bar(0, 0);
    break;
    default:
        echo "php {$argv[0]} [spells | items] [path]\n     spells/items - get spells/items inf from db\n	path - path to json directory(default: json/)\n";
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