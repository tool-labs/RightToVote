<?php

function buildMysqlString($lang,$needUser)
{
  switch($lang)
  {
    case "toolserver":
    case "TS":         return 'mysql:host=sql-toolserver;dbname=toolserver';
    default:           return 'mysql:host='.$lang.'wiki-p.'.
                              ($needUser ? 'userdb' : 'rrdb').
                              '.toolserver.org;dbname='.$lang.'wiki_p';
  }
}

function connectDB($lang='de',$needUser = false)
{
  $settings = parse_ini_file('/home/project/s/t/i/stimmberechtigung/.my.cnf');

  try
  {
    $database = new PDO(buildMysqlString($lang,$needUser),
                        $settings['user'],
                        $settings['password']);
  } catch (PDOException $e) {
    echo("Can't connect to the database: ".$e->getMessage()."\n");
    die();
  }
  unset($settings);
  return $database;
}

function getVar($name,$default=null)
{
  if (isset($_GET[$name]))
    return htmlspecialchars($_GET[$name]);
  else
    return $default;
}

function print_header($lang,$title)
{
 echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="'.$lang.'" xml:lang="'.$lang.'">
<head>
 <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <title>'.$title.'</title></head><body>
 <link rel="stylesheet" type="text/css" href="/~stimmberechtigung/style.css" />
</head>
<body>
');
}

function print_footer($text)
{
 echo('<hr />
  <div style="float:left;font-size:70%;">'.$text.'</div>
  <a href="/"><img style="float:right;" id="poweredbyicon" src="/~stimmberechtigung/ts_button.png" alt="Powered by Wikimedia-Toolserver" /></a>
</body>
</html>
');
}
?>
