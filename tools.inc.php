<?php

function buildMysqlString($lang,$needUser)
{
  switch($lang)
  {
    default:           return 'mysql:host='.$lang.'wiki.labsdb'.
                              ';dbname='.$lang.'wiki_p';
  }
}

function connectDB($lang='de',$needUser = false)
{
  $settings = parse_ini_file('/data/project/stimmberechtigung/replica.my.cnf');

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
 echo('<!DOCTYPE html>
<html lang="'.$lang.'">
<head>
 <meta charset="UTF-8" />
 <title>'.$title.'</title></head><body>
 <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
');
}

function print_footer($text)
{
 echo('<hr />
  <div style="float:left;font-size:70%;">'.$text.'</div>
  <a href="https://tools.wmflabs.org"><img style="float:right;" id="poweredbyicon" src="powered-by-labs.png" title="Powered by Wikimedia Tool Labs" /></a>
  </div>
</body>
</html>
');
}
?>
