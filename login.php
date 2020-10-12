<?php
include_once 'includes/header.php';

// wanneer is ingelogd
if ( $ingelogd ){
    header('location: index.php');
    exit();
} elseif (isset($_POST['authenticeer'])){
    $gebruikersnaam = $db->saniteer($_POST['gebruikersnaam']);
    $wachtwoord     = $db->saniteer($_POST['wachtwoord']);
    if ( ($gebruikersnaam === GEBRUIKER) && ( password_verify($wachtwoord, WACHTWOORD) ) ){
        // begin de sessie
        begin_sessie($gebruikersnaam);
        echo "U bent nu ingelogd";
        // verwijs naar index.php
        header('location: index.php');
        exit(); 
    } else {
        echo "De gebruikersnaam/wachtwoord combinatie is niet bekend.";
    }
} 
?>
<h1 class="display-4">Login</h1>

<div class="login-scherm center">
    <form action="login.php" method="post">
        <div class="column">
            <label for="gebruikersnaam">Gebruikersnaam</label>
            <input type="text" name="gebruikersnaam" id="gebruikersnaam">
        </div>
        <div class="column pt-3">
            <label for="wachtwoord">Wachtwoord</label>
            <input type="password" name="wachtwoord" id="wachtwoord">
        </div>
        <div class="column pt-3">
            <input type="submit" id="loginknop" value="OK" name="authenticeer">
        </div>
    </form>
</div>

<?php 
include_once 'includes/footer.php';
?>