<?php
require_once 'Database.php';
$db = new Database;
// wanneer de inlogtijd is verstreken
$uitlogtijd = UITLOGTIJD;

global $ingelogd;
if ( isset($_COOKIE['gebruiker']) && isset($_COOKIE['tijd']) && isset($_COOKIE['datum']) && ($_COOKIE['tijd'] + $uitlogtijd > time()) ){
  begin_sessie($_COOKIE['gebruiker']);
  $ingelogd = TRUE;
}
// Wanneer op uitloggen geklikt is, er niet ingelod is of de tijd van inlog is al verstreken
if ( isset($_POST['uitloggen']) ){
  beeindig_sessie('index.php');
  $ingelogd = FALSE;
} elseif (isset($_COOKIE['tijd']) && $_COOKIE['tijd'] + $uitlogtijd < time()){
  beeindig_sessie('login.php');
  $ingelogd = FALSE;
}

function beeindig_sessie($pagina){
  setcookie('datum', date("Y-m-d", time()), time()-292000 );
  setcookie('tijd', time(), time()-292000 );
  setcookie('gebruiker', GEBRUIKER , time()-292000);
  header('location: ' . $pagina);
  exit();
}
function begin_sessie($gebruiker){
  setcookie('datum', date( "Y-m-d", time()) );
  setcookie('tijd', time() );
  setcookie('gebruiker', GEBRUIKER );
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledenadministratie</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

</head>
<body>
<nav class="navbar navbar-expand-sm navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand">LedenAdmin<span> voor verenigingen</span></a>
    <div class="navbar-collapse ml-3" id="navbarsExample07">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="leden.php">Leden</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="teams.php">Teams</a>
        </li>
        <li class="nav-item">
          <?php if ( $ingelogd ){?>
            <form action="index.php" method="post">
            <button type="submit" class="nav-link linkknop" name="uitloggen">Uitloggen</button>
            </form>
          <?php } else { ?>
            <a class="nav-link" href="login.php">Inloggen</a>
          <?php } ?>
        </li>
      </ul>
      <?php if ( $ingelogd ){?>
              <p class="login-details">Ingelogd als <?php echo htmlentities($_COOKIE['gebruiker']);?></p>
            <?php } ?>
    </div>
  </div>
</nav>
<main>
<div class="main-container">
