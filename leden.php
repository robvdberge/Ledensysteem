<?php
include_once 'includes/header.php';

// Wanneer nog niet is ingelogd, verwijs dan naar login.php
if (!$ingelogd ){
    header('location: login.php');
    exit();
}

$email_nieuw    = $telefoon_nieuw = FALSE;
$email_wijzigen = $telnr_wijzigen = FALSE;

//************ LID crud *****************
if (isset($_POST['verander'])){
    // Update lid in db
    $response = $db->wijzig_lid($_POST);
    if ($response == TRUE){
        echo "Het lid is opgeslagen";
    } else { echo $response; };

} elseif ( isset($_POST['verwijder'])){
    // Verwijder lid uit db
    $response = $db->verwijder_lid($_POST);
    if ($response == TRUE){
        echo htmlentities($_POST['voornaam']) . ' ' . htmlentities($_POST['naam']) . ' is verwijderd';
    } else { echo $response;}

} elseif ( isset($_POST['insertNieuwLid'])){
    // Maak nieuw lid in db
    // check of velden leeg zijn
    $checkvelden = array("naam", "voornaam", "adres", "telefoonnummers", "woonplaats", "emailadressen", "huisnummer");
    if ( $db->check_of_leeg_is($_POST, $checkvelden)){
        // check of emailadres en emailadres al bestaan
        if ( !$db->telnr_gevonden($_POST) && !$db->email_gevonden($_POST) ){
            $response = $db->nieuw_lid($_POST);
            if ($response == TRUE){
                echo "Het nieuwe lid is aangemaakt.";
            } else { echo $response;}
        } else {
            echo "Telefoonnummer en/of emailadres bestaan al, kies een andere.";
        } 
    } else {
        echo "Er zijn lege velden gevonden, vul aub alles in";
    }

} elseif ( isset($_POST['cancel'])){
    // Wanneer op een cancel knop is geklikt
    echo "<script>window.location.href = window.location.href</script>";

//*************** TELEFOONNUMMER crud ********************/
} elseif ( isset($_POST['nieuw_telnr'])){
    // Wanneer een nieuw telnr toegevoegd moet worden
    $telefoon_nieuw = TRUE;

} elseif ( isset($_POST['voeg_telnr_toe'])){
    // Wanneer een nieuw telnr toegevoegd moet worden in db
    // DB query INSERT telnr
    if ( !$db->telnr_gevonden($_POST) ){
        if ( $db->nieuw_telefoonnummer($_POST)){
           echo htmlentities($_POST['telefoonnummer']) . " is toegevoegd."; 
        }
    } else {
        echo "Dit nummer bestaat al, kies een ander nummer";
    }

} elseif ( isset($_POST['wijzig_telnr'])){
    // wanneer een telefoonnummer gewijzigd moet worden
    // kijk of er wel een telnr geselecteerd is
    if ( $_POST['telefoonnummer'] != ''){
        echo htmlentities($_POST['telefoonnummer']) . " kan nu worden gewijzigd";
        $telnr_wijzigen = TRUE;
    } else {
        echo "Er moet een telefoonnummer worden geselecteerd om deze te kunnen wijzigen.";
    }

} elseif ( isset($_POST['sla_telnr_op'])){
    // wanneer een gewijzigd telefoonnummer moet worden opgeslagen
    if ( !$db->telnr_gevonden($_POST) ){
        if ( $db->wijzig_telnr($_POST)){
           echo htmlentities($_POST['telefoonnummer']) . " is opgeslagen."; 
        }
    } else {
        echo "Dit nummer bestaat al, kies een ander nummer";
    }
    
} elseif ( isset($_POST['verwijder_telnr'])){
    // Wanneer een telefoonnummer moet worden verwijdert
    // check of er wel een telnr is geselecteerd
    if ( $_POST['telefoonnummer'] != ''){
        // DB query DELETE telnr
        if ( $db->verwijder_telefoonnummer($_POST) ){
            echo htmlentities($_POST['telefoonnummer']) . " is verwijdert.";
        }
    } else {
        echo "Er is geen telefoonnummer geselecteerd om te verwijderen.";
    }

//******************** EMAILADRES crud ***************************/
} elseif ( isset($_POST['nieuwe_email'])){
    // Wanneer een nieuw emailadres aangemaakt moet worden
    $email_nieuw = TRUE;

} elseif ( isset($_POST['voeg_email_toe'])){
    // Wanneer een nieuw emailadres toegevoegd moet worden in db
    // DB query INSERT email
    if ( !$db->email_gevonden($_POST) ){
        if ( $db->nieuw_emailadres($_POST)){
            echo htmlentities($_POST['emailadres']) . " is toegevoegd.";
        }
    } else {
        echo "Dit emailadres bestaat al, kies een andere.";
    }
    

} elseif ( isset($_POST['wijzig_email'])){
    // wanneer een emailadres gewijzigd moet worden
    // kijk of er wel een emailadres geselecteerd is
    if ( $_POST['emailadres'] != ''){
        echo htmlentities($_POST['emailadres']) . " kan nu worden gewijzigd";
        $email_wijzigen = TRUE;
    } else {
        echo "Er moet een emailadres worden geselecteerd om deze te kunnen wijzigen.";
    }

} elseif ( isset($_POST['sla_email_op'])){
    // wanneer een gewijzigd emailadres moet worden opgeslagen
    if ( !$db->email_gevonden($_POST) ){
            // DB query UPDATE email
        if ( $db->wijzig_emailadres($_POST)){
            echo htmlentities($_POST['emailadres']) . " is opgeslagen";
        }
    } else {
        echo "Het emailadres bestaat al, kies een andere.";
    }

} elseif ( isset($_POST['verwijder_email'])){
    // Wanneer een emailadres verwijdert moet worden
    if ( $_POST['emailadres'] != '' ){
        // verwijder telefoonnummers uit telefoonnummerstabel
        if ( $db->verwijder_email($_POST)){
            echo htmlentities($_POST['emailadres']) . " is verwijdert.";
        }
    } else {
        echo "Er is geen emailadres geselecteerd om te verwijderen.";
    }
} 

?>
<h1 class="display-4">Leden</h1>
<?php
    $result = $db->haal_alle_leden();
    if (!$result){
        echo '<p>Er zijn geen leden gevonden.</p>';
    } else {
        // Zoek het aantal gevonden records
        $rows = $result->num_rows;?>
        <table id="ledentabel">
            <tr>
                <th>Naam</th><th>Voornaam</th><th>Postcode</th><th>Telefoonnummer</th>
                <th>Emailadres</th><th>Adres</th><th>Huisnr</th><th>Woonplaats</th><th>Actie</th>
            </tr>
        
<?php   // Geef per record een overzicht
        for ($i = 0; $i < $rows; $i++){
            $row = $result->fetch_array(MYSQLI_ASSOC);?>
            <tr>
                <form class="mainForm" method="POST" action="leden.php">
                    <td><input type="text" class="long" name="naam" value="<?php echo htmlentities($db->saniteer($row['naam']));?>"></td>
                    <td><input type="text" class="short" name="voornaam" value="<?php echo htmlentities($db->saniteer($row['voornaam']));?>"></td>
                    <td><input type="text" class="short" name="postcode" value="<?php echo htmlentities($db->saniteer($row['postcode']));?>"></td>
                    <td>
                        <div class="column">
                        <?php // Geef de select weer als: geen wijziging, geen nieuwtelnr en post[lidnr] != als row[lidnr]
                            if ( (!$telnr_wijzigen && !$telefoon_nieuw) || $_POST['lidnummer'] != $row['lidnummer']){ ?>
                            <select name="telefoonnummer" size="2">
                            <?php 
                                // Haal alle telefoonnummers op die met lidnummer zijn gekoppeld
                                $telnummers = $db->zoek_telnrs($row['lidnummer']);
                                // Als er resultaat is dan
                                if ($telnummers){
                                    // Geef voor elk gevonden nummer een row in de tabel
                                    $aantal_telnrs = $telnummers->num_rows;
                                    for ($j = 0; $j < $aantal_telnrs; $j++){
                                        $tel_row = $telnummers->fetch_array(MYSQLI_ASSOC);?>
                                        <div class="rij">
                                            <option value="<?php echo htmlentities($tel_row['telefoonnummer']);?>"><?php echo $tel_row['telefoonnummer']; ?></option>>
                                        </div>
                            <?php   }?>
                            </select>
                        <?php   } else { echo '<input type="text" disabled value="niets gevonden">'; } ?>
                            <div class="rij">
                                <input type="submit" value="nieuw" name="nieuw_telnr" class="knop plusknop">
                                <input type="submit" value="verwijder" name="verwijder_telnr" class="knop minknop">
                                <input type="submit" value="wijzig" name="wijzig_telnr" class="knop saveknop">
                            </div> 
                        <?php }   // Nieuw telefoonnummer inputs
                                if ( $telefoon_nieuw && $_POST['lidnummer'] === $row['lidnummer']){?>
                                    <div class="rij">
                                        <input type="text" name="telefoonnummer" placeholder="nieuw nummer">
                                        <input type="submit" name="voeg_telnr_toe" value="V" class="knop okKnop">
                                        <input type="submit" name="cancel" value="X" class="knop cancelKnop">
                                    </div>    
                            <?php } // Wijzig telefoonnummer inputs
                            if ( $telnr_wijzigen && !$telefoon_nieuw && ($_POST['lidnummer'] === $row['lidnummer']) ){?>
                                <div class="rij">
                                    <input type="hidden" name="telnr_oud" value="<?php echo htmlentities($_POST['telefoonnummer']);?>">
                                    <input type="text" name="telefoonnummer" value="<?php echo htmlentities($_POST['telefoonnummer']);?>">
                                    <input type="submit" name="sla_telnr_op" value="V" class="knop okKnop">
                                    <input type="submit" name="cancel" value="X" class="knop cancelKnop">
                                </div>
                        <?php } ?>
                        </div>
                    </td>
                    <td>
                        <div class="column">
                        <?php if ( (!$email_wijzigen && !$email_nieuw) || $_POST['lidnummer'] != $row['lidnummer']){ ?>
                            <select name="emailadres" size="2">
                                <?php 
                                // Haal alle emailadressen op die met lidnummer zijn gekoppeld
                                $emailnummers = $db->zoek_email($row['lidnummer']);
                                if ($emailnummers){
                                    // Vraag het aantal gevonden nummers op
                                    $aantal_email = $emailnummers->num_rows;
                                    for ($j = 0; $j < $aantal_email; $j++){
                                        $email_row = $emailnummers->fetch_array(MYSQLI_ASSOC);?>
                                        echo '<option value="<?php echo htmlentities($email_row['emailadres']);?>"><?php echo htmlentities($email_row['emailadres']);?></option>
                                <?php }
                                } else {
                                    echo '<option>geen gevonden</option>';
                                } ?>
                            </select> 
                            <div class="rij">
                                <input type="submit" value="nieuw" name="nieuwe_email" class="knop plusknop">
                                <input type="submit" value="verwijder" name="verwijder_email" class="knop minknop">
                                <input type="submit" value="wijzig" name="wijzig_email" class="knop saveknop">
                            </div> 
                        <?php }      // Nieuwe email inputs
                                if ( isset($_POST['nieuwe_email']) && $_POST['lidnummer'] === $row['lidnummer']){?>
                                    <div class="rij">
                                        <input type="text" name="emailadres" placeholder="nieuw emailadres">
                                        <input type="submit" name="voeg_email_toe" value="V" class="knop okKnop">
                                        <input type="submit" value="X" class="knop cancelKnop">
                                    </div>
                            <?php }  // Email wijzigen inputs
                                if ( $email_wijzigen && !$email_nieuw && ($_POST['lidnummer'] === $row['lidnummer']) ){?>
                                <div class="rij">
                                    <input type="hidden" name="email_oud" value="<?php echo htmlentities($_POST['emailadres']);?>">
                                    <input type="text" name="emailadres" value="<?php echo htmlentities($_POST['emailadres']);?>">
                                    <input type="submit" value="V" class="knop okKnop" name="sla_email_op">
                                    <input type="submit" value="X" class="knop cancelKnop" name="cancel">
                                </div>
                        <?php } ?>
                        </div>    
                    </td>
                    <td><input type="text" class="long" name="adres" value="<?php echo htmlentities($db->saniteer($row['adres']));?>"></td>
                    <td><input type="text" class="short" name="huisnummer" value="<?php echo htmlentities($db->saniteer($row['huisnummer']));?>"></td>
                    <td><input type="text" class="long" name="woonplaats" value="<?php echo htmlentities($db->saniteer($row['woonplaats']));?>"></td>
                    <td><div class="column">
                        <input type="hidden" name="lidnummer" value="<?php echo htmlentities($db->saniteer($row['lidnummer']));?>">
                        <input type="submit" value="Sla lid op" name="verander" id="chn_btn">
                        <input type="submit" value="Verwijder lid" name="verwijder" class="del_btn"></div>
                    </td>
                </form>
            </tr>
        <?php
        } // wanneer op de Lid Toevoegen knop is gedrukt
        if (isset($_POST['nieuwLid'])){?>
            <form class="mainForm" method="POST" action="leden.php">
                <td><input type="text" class="long" name="naam" placeholder="Naam" required></td>
                <td><input type="text" class="short" name="voornaam" placeholder="Voornaam" required></td>
                <td><input type="text" class="short" name="postcode" placeholder="Postcode" required></td>
                <td>
                    <div class="column">
                        <select name="telefoonnummers[]" id="telefoonnummers" multiple="multiple" required>
                            
                        </select>
                        <div class="rij">
                            <input id="tempTelefoon" type="text" name="telefoonnummer" placeholder="Telefoonnummer">
                            <button id="addTelnr" class="btn plusknop">+</button>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="column">
                        <select name="emailadressen[]" id="emailadressen" multiple="multiple" required>

                        </select>
                        <div class="rij">
                            <input id="tempEmail" type="text" name="emailadres" placeholder="Emailadres"> 
                            <button id="addEmail" class="btn plusknop">+</button>
                        </div>
                    </div>
                </td>
                <td><input type="text" class="long" name="adres" placeholder="Straatnaam" required></td>
                <td><input type="text" class="short" name="huisnummer" placeholder="Huisnr" required></td>
                <td><input type="text" class="long" name="woonplaats" placeholder="Woonplaats" required></td>
                <td><div class="actieKnoppen">
                    <input type="submit" value="Maak lid aan" name="insertNieuwLid" id="chn_btn">
                </td>
            </form>
            <?php
        }
        echo "</table>";
        $result->close();
        $db->sluit();
    }
?>
<form method="POST" action="leden.php">
    <div class="controls">
        <input type="submit" name="nieuwLid" id="nieuweKnop" value="Nieuw lid toevoegen">
        <input type="submit" name="cancel" class="cancelKnop knop-groot" value="Cancel">
    </div>
</form>


<?php 
include_once 'includes/footer.php';
?>