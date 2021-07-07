<?php

require_once("tools.inc.php"); 

define('ARBCOM_EDITS',             400);
define('ARBCOM_FIRST_EDIT', '-4 month');
define('ADMIN_EDITS',              200);
define('ADMIN_FIRST_EDIT',  '-2 month');
define('ADMIN_RECENT_EDITS',        50);
define('ADMIN_RECENT_TIME',  '-1 year');
define('IMAGE_EDITS',               60);
define('IMAGE_AGE',         '-6 month');

define('DATE_FORMAT', 'd.m.Y \u\m H:i:s');
define('TIMESTAMP_FORMAT', 'YmdHis');

/*
 * Get the timestamp passed via GET, if set
 */ 
function getTimestamp()
{
  $year  = getVar('year');
  $month = getVar('mon');
  $day   = getVar('day');
  $hour  = getVar('hour');
  $min   = getVar('min');

  if (isset($year) && isset($month) && isset($day) && isset($hour) && isset($min))
    return mktime($hour,$min,0,$month,$day,$year);
  else
    return time();
}

function printForm()
{
  echo
  '<form method="get" action=""> 
   <p><label>Benutzer:&nbsp;<input type="text" name="user" value="' . getVar('user','').'"></label></p>
   <p><label>Tag:&nbsp;<input type="number" name="day" value="'.getVar('day', date('d')).'" size="2" min="1" maxlength="2"></label>&nbsp;
      <label>Monat:&nbsp;<input type="number" name="mon" value="'.getVar('mon', date('m')).'" size="2" min="1" maxlength="2" /></label>&nbsp;
      <label>Jahr:&nbsp;<input type="number" name="year" value="'.getVar('year', date('Y')).'" size="4" min="2001" maxlength="4" /></label>&nbsp;
      <label>Uhrzeit:&nbsp;<input type="number" name="hour" value="'.getVar('hour', date('H')).'" size="2" min="0" maxlegth="2" />&nbsp;:&nbsp;
                           <input type="number" name="min" value="'.getVar('min', date('i')).'" size="2" mmin="0" maxlength="2" /></label></p>
   <p style="font-size:smaller;">Bitte die Uhrzeit als <a href="https://de.wikipedia.org/wiki/Koordinierte_Weltzeit">koordinierte Weltzeit (UTC)</a> eintragen. 
     UTC ist hierbei im Sommer (MESZ / CEST) 2, im Winter (MEZ / CET) 1 Stunde zurück.<br />
     &nbsp;<br />
     Sollte dieses Stimmberechtigungstool in eine Seite in der Wikipedia eingebunden werden, muss ebenfalls auf die 
     UTC-Angabe geachtet werden. Empfehlenswert ist es, dort folgenden Kommentar hinter dem Tool einzubauen, damit 
     die Zeit nicht wieder in MEZ oder MESZ geändert wird:<br />
     &nbsp;<br />
     &lt;!-- Die Angabe der Uhrzeit beim Tool ist als „UTC“ einzutragen (UTC entspricht MEZ - 1 Stunde bzw. MESZ - 2 Stunden). --&gt;
   </p>
   <p><input type="submit" value="Überprüfen" /></p>
  </form>';
}  

function getUserData($name)
{ 
  global $database;
  $query = $database->prepare('select actor_id,user_registration,group_concat(ug_group) as groups '.
                              'from user join user_groups on (ug_user = user_id) '.
                              'join actor on actor_user = user_id '.
                              'where user_name = ?');
  $query->execute(array(htmlspecialchars_decode($name)));
  return $query->fetch();
}

function verifyUserEditCount($user,$start,$end,$allNS,$required)
{
  global $database;
  $query = $database->prepare('select count(1) as num from ('.
                              'select rev_id from revision_userindex join page on (page_id = rev_page) '.
                              'where '.
                              ($allNS ? '' : 'page_namespace=0 and ').
                              'rev_actor=? and rev_timestamp between ? and ? '.
                              'LIMIT '.(integer)$required.') i');
  $query->execute(array($user,date(TIMESTAMP_FORMAT,$start),date(TIMESTAMP_FORMAT,$end)));
  $result = $query->fetch();
  if ($result === false)
    return 0;
  else
    return $result['num'];
}

function getFirstEdit($user)
{
  global $database;
  $query = $database->prepare('select rev_timestamp from revision_userindex '.
                              'where rev_actor=? '.
                              'ORDER BY rev_timestamp ASC LIMIT 1');
  $query->execute(array($user));
  $result = $query->fetch();
  if ($result === false)
    return 0;
  else
    return $result['rev_timestamp'];
}

function oneResult($type,$failReasons)
{
  if (!sizeof($failReasons))
    echo '<div style="color:green; font-size:130%;margin-top:1em;">'.$type.': stimmberechtigt</div>'."\n";
  else
    echo '<div style="color:red;   font-size:130%;margin-top:1em;">'.$type.": nicht stimmberechtigt</div><ul><li>\n".
         implode($failReasons,"</li>\n<li>")."</li></ul>\n";
}

function printResults($forBot,$date,$signup,$firstEdit,$ArbCom,$Admin,$AdminRecent)
{
  $newSB = $date >= strtotime('2010-01-27');

  $reasons = array();
  if ($Admin < ADMIN_EDITS)
    $reasons[] = $Admin.' von '.ADMIN_EDITS.' Artikel-Bearbeitungen';
  if ($firstEdit > date(TIMESTAMP_FORMAT,strtotime(ADMIN_FIRST_EDIT,$date)))
    $reasons[] = 'Erster Edit am '.$firstEdit[6].$firstEdit[7].'.'.$firstEdit[4].$firstEdit[5].'.'.$firstEdit[0].$firstEdit[1].$firstEdit[2].$firstEdit[3].
                 ' um '.$firstEdit[8].$firstEdit[9].':'.$firstEdit[10].$firstEdit[11].
                 ', vor '.date(DATE_FORMAT,strtotime(ADMIN_FIRST_EDIT,$date)).' benötigt.';
  if ($newSB && ($AdminRecent < ADMIN_RECENT_EDITS))
    $reasons[] = $AdminRecent.' von '.ADMIN_RECENT_EDITS.' Artikel-Bearbeitungen seit dem '.
                 date(DATE_FORMAT,strtotime(ADMIN_RECENT_TIME,$date));
  $AdminOK = empty($reasons);
  if ($forBot)
  {
    echo('Allgemeine Stimmberechtigung: '.(sizeof($reasons) ? 'Nein' : 'Ja')."\r\n");
  } else {
    if ($newSB)
      oneResult('Allgemeine Stimmberechtigung <a href="https://de.wikipedia.org/w/index.php?title=Wikipedia:Stimmberechtigung&oldid=69822923#Allgemeine_Stimmberechtigung">(neu)</a>', $reasons);
    else
      oneResult('Allgemeine Stimmberechtigung <a href="https://de.wikipedia.org/w/index.php?title=Wikipedia:Stimmberechtigung&oldid=75178547#Allgemeine_Stimmberechtigung">(alt)</a>', $reasons);
    echo("<!-- ".$Admin." edits / ".$AdminRecent." current edits -->\n");
  }

  $reasons = array();
  if (!$AdminOK)
    $reasons[] = 'Allgemeine Stimmberechtigung nicht erfüllt';
  if ($ArbCom < ARBCOM_EDITS)
    $reasons[] = $ArbCom.' von '.ARBCOM_EDITS.' Bearbeitungen';
  if ($firstEdit > date(TIMESTAMP_FORMAT,strtotime(ARBCOM_FIRST_EDIT,$date)))
    $reasons[] = 'Erster Edit am '.$firstEdit[6].$firstEdit[7].'.'.$firstEdit[4].$firstEdit[5].'.'.$firstEdit[0].$firstEdit[1].$firstEdit[2].$firstEdit[3].
                 ' um '.$firstEdit[8].$firstEdit[9].':'.$firstEdit[10].$firstEdit[11].
                 ', vor '.date(DATE_FORMAT,strtotime(ARBCOM_FIRST_EDIT,$date)).' benötigt.';
  if ($forBot)
  {
    echo('Stimmberechtigung Schiedsgericht: '.(sizeof($reasons) ? 'Nein' : 'Ja')."\r\n");
  } else {
    oneResult('Schiedsgerichtswahl', $reasons);
    echo("<!-- ".$ArbCom." edits -->\n");
  }

  $reasons = array();
  $startDate = strtotime(IMAGE_AGE,$date);
  if (($ArbCom < IMAGE_EDITS) && ($signup > date(TIMESTAMP_FORMAT,$startDate)))
    $reasons[] = $ArbCom.' von '.IMAGE_EDITS.' Bearbeitungen, und erst seit dem '.
                 $signup[6].$signup[7].'.'.$signup[4].$signup[5].'.'.$signup[0].$signup[1].$signup[2].$signup[3].
                 ' um '.$signup[8].$signup[9].':'.$signup[10].$signup[11].
                 ' dabei (benötigt: '.date(DATE_FORMAT,$startDate).' oder früher)';
  if ($forBot)
  {
    echo('Stimmberechtigung exzellente Bilder: '.(sizeof($reasons) ? 'Nein' : 'Ja')."\r\n");
  } else {
    oneResult('Kandidaten für exzellente Bilder', $reasons);
  }
}

$date = getTimestamp();
$user = ucfirst(trim(getVar('user')));

$forBot = getVar('mode') == 'bot';

if (!$forBot)
{
  print_header('de','Stimmberechtigung');
  echo("<h1>Überprüfung der Stimmberechtigung</h1>\r\n");

 if ($date <= 0) // Illegal date entered
 {
   echo('<h2 style="color:red;text-align:center;">Fehler: Ungültiges Datum angegeben!</h2>'."\r\n");
 }
 printForm();
} else {
  header('Content-Type: text/plain');
  echo("Datum Abstimmung: ".date(DATE_FORMAT,$date)."\r\n");
  echo("Benutzer: ".$user."\r\n");
}

if (!empty($user))
{
  $database = connectDB();
  $udata = getUserData($user);
  $uid = $udata['actor_id'];

  if (empty($uid))
  {
    if (!$forBot)
    {
      echo '<p style="font-weigt:bold;">Benutzer "'.$user.'" existiert nicht.</p>
</body>
</html>';
    } else {
      echo("Fehler: Benutzer unbekannt.\r\n");
    }
    exit();
  } else if (strpos($udata['groups'],'bot') !== false) {
    if (!$forBot)
    {
      echo('<p style="font-weigt:bold;">Benutzer "'.$user.'" ist ein Bot und damit nicht stimmberechtigt.</p>
</body>
</html>');
    } else {
      echo("Fehler: Benutzer ist ein Bot.\r\n");
    }
    exit();
  }

  if (!$forBot)
  { 
    echo '
  <hr/>
  <p>
   <div style="font-size:130%;font-weight:bold;">
    <a href="https://de.wikipedia.org/wiki/Benutzer:'.$user.'">[[Benutzer:'.$user.']]</a> 
    <a style="font-size:70%;display:none;" href="http://tools.wikimedia.de/~soxred93/count/index.php?name='.$user.'&lang=de&wiki=wikipedia">(detaillierter Editcount)</a>
   </div>
   <hr />
';
  }
  $firstEdit           = getFirstEdit($uid);
  $ArbComElectionEdits = verifyUserEditCount($uid,0, $date,true, ARBCOM_EDITS);
  $AdminElectionEdits  = verifyUserEditCount($uid,0, $date,false,ADMIN_EDITS);
  $AdminElectionRecent = verifyUserEditCount($uid,strtotime(ADMIN_RECENT_TIME, $date),$date,false,ADMIN_RECENT_EDITS);
  printResults($forBot,$date,$udata['user_registration'],$firstEdit,$ArbComElectionEdits,$AdminElectionEdits,$AdminElectionRecent);
}

if (!$forBot)
{
  echo('</p>');
  print_footer('Modifiziert von <a href="http://de.wikipedia.org/wiki/Benutzer:Guandalug">Guandalug</a> und <a href="http://de.wikipedia.org/wiki/Benutzer:Ireas">ireas</a> nach einem Skript von <a href="http://tools.wikimedia.de/~gunther/">Gunther</a>. Veröffentlicht unter der <a href="http://www.opensource.org/licenses/mit-license.php">MIT-Lizenz</a> auf <a href="https://github.com/tool-labs/stimmberechtigung">GitHub</a>.');
}

?>

