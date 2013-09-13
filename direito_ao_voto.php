<?php
require_once("tools.inc.php"); 

define('ADMIN_EDITS',              300);
define('ADMIN_FIRST_EDIT',  '-90 days');

define('DATE_FORMAT', 'H\hi\m\i\n \d\e d.m.Y');
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
  '<form method="get" action="'.basename(__FILE__).'"> 
   <p><label>Usuário:&nbsp;<input type="text" name="user" value="' . $_GET['user'].'"></label></p>
   <p><label>Dia:&nbsp;<input type="text" name="day" value="'.getVar('day', date('d')).'" size="2" maxlength="2"></label>&nbsp;
      <label>Mês:&nbsp;<input type="text" name="mon" value="'.getVar('mon', date('m')).'" size="2" maxlength="2" /></label>&nbsp;
      <label>Ano:&nbsp;<input type="text" name="year" value="'.getVar('year', date('Y')).'" size="4" maxlength="4" /></label>&nbsp;
      <label>Hora:&nbsp;<input type="text" name="hour" value="'.getVar('hour', date('H')).'" size="2" maxlegth="2" />&nbsp;:&nbsp;
                           <input type="text" name="min" value="'.getVar('min', date('i')).'" size="2" maxlength="2" /></label></p>
   <p style="font-size:smaller;">Por favor insira a data e hora do começo da votação na qual quer votar como <a href="http://pt.wikipedia.org/wiki/Tempo Universal Coordenado">Tempo Universal Coordenado</a> (UTC).</p>
   <p><input type="submit" value="verificar" /></p>
  </form>';
}  

function getUserData($name)
{ 
  global $database;
  $query = $database->prepare('select user_id,user_registration,group_concat(ug_group) as groups '.
                              'from user join user_groups on (ug_user = user_id) '.
                              'where user_name = ?');
  $query->execute(array($name));
  return $query->fetch();
}

function verifyUserEditCount($user,$start,$end,$allNS,$required)
{
  global $database;
  $query = $database->prepare('select count(1) as num from ('.
                              'select rev_id from revision join page on (page_id = rev_page) '.
                              'where '.
                              ($allNS ? '' : 'page_namespace=0 and ').
                              'rev_user=? and rev_timestamp between ? and ? '.
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
  $query = $database->prepare('select rev_timestamp from revision '.
                              'where rev_user=? '.
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
    echo '<div style="color:green; font-size:130%;margin-top:1em;">'.$type.': possui direito ao voto</div>'."\n";
  else
    echo '<div style="color:red;   font-size:130%;margin-top:1em;">'.$type.": não possui direito ao voto</div><ul><li>\n".
         implode($failReasons,"</li>\n<li>")."</li></ul>\n";
}

function printResults($forBot,$date,$signup,$firstEdit,$Admin)
{
  $newSB = $date >= strtotime('2010-01-27');

  $reasons = array();
  if ($Admin < ADMIN_EDITS)
    $reasons[] = 'Número de edições '.$Admin.' / '.ADMIN_EDITS;

  if ($firstEdit > date(TIMESTAMP_FORMAT,strtotime(ADMIN_FIRST_EDIT,$date)))
    $reasons[] = 'Primeira edição válida ('.$firstEdit[8].$firstEdit[9].'h'.$firstEdit[10].$firstEdit[11].'min de '.
                 $firstEdit[6].$firstEdit[7].'.'.$firstEdit[4].$firstEdit[5].'.'.$firstEdit[0].$firstEdit[1].$firstEdit[2].$firstEdit[3].' UTC) há mais de 90 dias '.
                 '('.date(DATE_FORMAT,strtotime(ADMIN_FIRST_EDIT,$date)).')';

  $AdminOK = empty($reasons);
  if ($forBot)
  {
    echo('Voting right: '.(sizeof($reasons) ? 'No' : 'Yes')."\r\n");
  } else {
    oneResult('<a href="http://pt.wikipedia.org/w/index.php?title=Wikipedia:Direito_ao_voto&oldid=23081972#Regras">Direito ao voto</a>', $reasons);
  }
}

$date = getTimestamp();
$user = ucfirst(trim(getVar('user')));

$forBot = getVar('mode') == 'bot';

if (!$forBot)
{
  print_header('pt','Direito ao voto');
  echo("<h1>Direito ao voto</h1>\r\n");

 if ($date <= 0) // Illegal date entered
 {
   echo('<h2 style="color:red;text-align:center;">Usou data inválida.</h2>'."\r\n");
 }
 printForm();
} else {
  header('Content-Type: text/plain');
  echo("Checked date: ".date('d.m.Y \u\m H:i:s',$date)."\r\n");
  echo("Username: ".$user."\r\n");
}

if (!empty($user))
{
  $database = connectDB('pt');
  $udata = getUserData($user);
  $uid = $udata['user_id'];

  if (empty($uid))
  {
    if (!$forBot)
    {
      echo '<p style="font-weigt:bold;">Usuário "'.htmlspecialchars($user).'" não existe.</p>
</body>
</html>';
    } else {
      echo("Error: User unknown.\r\n");
    }
    exit();
  } else if (strpos($udata['groups'],'bot') !== false) {
    if (!$forBot)
    {
      echo('<p style="font-weigt:bold;">Usuário "'.htmlspecialchars($user).'" é um bot e não possui direito ao voto.</p>
</body>
</html>');
    } else {
      echo("Error: User is a Bot.\r\n");
    }
    exit();
  }

  if (!$forBot)
  { 
    echo '
  <hr/>
  <p>
   <div style="font-size:130%;font-weight:bold;">
    <a href="http://pt.wikipedia.org/wiki/Usuário:'.htmlspecialchars($user).'">[[Usuário:'.htmlspecialchars($user).']]</a> 
    <a style="font-size:70%;" href="http://toolserver.org/%7Evvv/yaec.php?user='.htmlspecialchars($user).'&wiki=ptwiki_p">(Número de edições)</a>
   </div>
   <hr />
';
  }
  $firstEdit           = getFirstEdit($uid);
  $AdminElectionEdits  = verifyUserEditCount($uid,0, $date,false,ADMIN_EDITS);
  printResults($forBot,$date,$udata['user_registration'],$firstEdit,$AdminElectionEdits);
}

if (!$forBot)
{
  echo('</p>');
  print_footer('Written by <a href="http://de.wikipedia.org/wiki/Benutzer:Guandalug">Guandalug</a>, modified by <a href="http://de.wikipedia.org/wiki/Benutzer:Ireas">ireas</a>, based on a script of <a href="http://tools.wikimedia.de/~gunther/">Gunther</a>. Published under the terms of the <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>.');
}
?>

