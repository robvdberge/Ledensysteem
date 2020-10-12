<?php 
include_once 'includes/header.php';
// Wanneer nog niet is ingelogd 
if ( !$ingelogd ){
    header('location: login.php');
    exit();
}

$leden_overzicht = $extra_team = $bewerk_team = FALSE;
if (isset($_POST['verwijder_lid'])){
    if ($db->verwijder_lid_uit_team($_POST)){
        echo "Het lid is verwijdert uit het team";
    }

} elseif (isset($_POST['lid_toevoegen'])){
    $leden_overzicht = TRUE;

} elseif ( isset($_POST['nieuw_teamlid'])){
    if ($db->voeg_teamlid_toe($_POST)){
        echo "Het lid is toegevoegd";
    } else echo "Er is wat foutgegaan";

} elseif (isset($_POST['extra_team'])){
    $extra_team = TRUE;

} elseif (isset($_POST['nieuw_team'])){
    // wanneer een van beide of beide leeg zijn
    if ($_POST['teamnaam'] == '' || $_POST['omschrijving'] == '' || ($_POST['omschrijving'] == '' && $_POST['teamnaam'] == '') ){
        echo "Vul alle velden.";
    } elseif ($db->maak_nieuw_team($_POST)){ 
        echo "Er is een nieuw team gemaakt";
    } else {
        echo "Er is iets fout gegaan bij het aanmaken van een nieuw team";
    }
} elseif (isset($_POST['cancel'])){
    echo "<script>window.location.href = window.location.href</script>";

} elseif (isset($_POST['bewerk_team'])){
    // maak inputs van de teamnaam en omschrijving
    $bewerk_team = TRUE;

} elseif (isset($_POST['verwijder_team'])){
    // verwijder het team uit Teamstabel
    if ($db->verwijder_team($_POST)){ 
        echo 'Team is verwijdert.';
    } else { 
        echo "Er is iets foutgegaan bij het verwijderen";
    }

} elseif (isset($_POST['sla_team_op'])){
    // Update het team in Teamtabel
    if ($db->wijzig_team($_POST)){
        echo "Het team is opgeslagen";
    } else {
        echo "Er is iets foutgegaan bij het opslaan van het team";
    }
}
?>
<h1 class="display-4">Teams</h1>

<?php 
    $teams = $db->haal_alle_teams();
    $num_teams = $teams->num_rows;
    if ( $teams ){
        for ($i = 0; $i < $num_teams; $i++){
            $team = $teams->fetch_array(MYSQLI_ASSOC);
            if (!$bewerk_team || $team['teamnaam'] != $_POST['teamnaam']){
            ?>

            <form method="POST" action="teams.php" id="team_form">
            <div class="team">
                <div class="rij team-controls">
                    <h4 class="mt-3 tabelnaam"><?php echo htmlentities($team['teamnaam']);?></h4>
                    <input type="submit" name="bewerk_team" class="knop saveknop" value="Bewerk">
                    <input type="submit" name="verwijder_team" class="knop minknop" value="Verwijder">
                </div>    
                <input type="hidden" name="teamnaam" value="<?php echo htmlentities($team['teamnaam']);?>">
                <p class="teamomschrijving"><?php echo htmlentities($team['omschrijving']);?></p>
            </form>
                <div class="row">
                    <table name="teamtabel"><thead><tr><th>Lidnaam</th><th>Actie</th></tr></thead>
<?php       } else { ?>
            <form method="POST" action="teams.php" id="team_form">
            <div class="team">
                <div class="rij team-controls">
                    <input type="hidden" name="oude_naam" value="<?php echo $team['teamnaam'];?>">
                    <input type="text" name="teamnaam" class="mt-3 tabelnaam" value="<?php echo htmlentities($team['teamnaam']);?>">
                    <input type="submit" name="sla_team_op" class="knop saveknop" value="Opslaan">
                    <input type="submit" value="cancel" name="cancel" class="knop cancelKnop">
                </div>    
                <textarea name="omschrijving" class="teamomschrijving omschrijving_invoer"><?php echo htmlentities($team['omschrijving']);?></textarea>
            </form>
                <div class="row">
                    <table name="teamtabel"><thead><tr><th>Lidnaam</th><th>Actie</th></tr></thead>
<?php       }
            $teamleden = $db->haal_teamleden_per_teamnaam($team['teamnaam']);
            if ($teamleden){
                echo "<tbody>";
                $num_leden = $teamleden->num_rows;
                for ($j = 0; $j < $num_leden; $j++){
                    $lid = $teamleden->fetch_array(MYSQLI_ASSOC);?>
                    <tr><form method="POST" action="teams.php">
                        <td><?php echo htmlentities($lid["voornaam"]) . ' ' . htmlentities($lid["naam"]);?></td>
                            <input type="hidden" name="teamnaam" value="<?php echo htmlentities($team['teamnaam']);?>">
                            <input type="hidden" name="lidnummer" value="<?php echo htmlentities($lid['lidnummer']);?>">
                        <td><input type="submit" name="verwijder_lid" value="Verwijder" class="ml-1 knop minknop"></td></form>
                    </tr>
<?php           } 
                if ($num_leden == 0) {
                    echo "<tr><td colspan='2'>Er zijn geen leden gevonden</td></tr>";    
                } 
                // wanneer op lid_toevoegen is geklikt
                if ( $leden_overzicht && $team['teamnaam'] == $_POST['teamnaam'] ){
                    $leden_in_team = $db->array_alle_teamleden($_POST);
                    echo "<table name='ledentabel'>";
                    echo "<thead><tr><th>Naam</th><th>Actie</th></tr></thead>";
                    echo "<tbody>"; 
                    $alle_leden = $db->haal_alle_leden();
                    $aantal_leden = $alle_leden->num_rows;
                    $vrij_lid_gevonden = FALSE;
                    for ($teller = 0; $teller < $aantal_leden; $teller++){
                        $nw_lid = $alle_leden->fetch_array(MYSQLI_ASSOC);
                        
                        // druk leden af wanneer ze niet al in het team zitten
                        if (!in_array($nw_lid['lidnummer'], $leden_in_team ) ){
                            $vrij_lid_gevonden = TRUE;   ?>
                            <form method="POST" action="teams.php">
                                <input type="hidden" name="teamnaam" value="<?php echo htmlentities($team['teamnaam']);?>">
                                <input type="hidden" name="lidnummer" value="<?php echo htmlentities($nw_lid['lidnummer']);?>">
                                <tr>
                                    <td><?php echo htmlentities($nw_lid['voornaam']) . " " . htmlentities($nw_lid['naam']);?></td>
                                    <td><input type='submit' class='knop plusknop' value='+' name='nieuw_teamlid'></td>
                                </tr>
                            </form>
<?php                   }
                    }
                    if (!$vrij_lid_gevonden){
                        echo "<tr><td>Er zijn geen vrije leden gevonden</td></tr>";
                    }
                    
                    echo "<tr><td><input type='submit' class='knop cancelKnop px-5' value='Cancel' name=''cancel></td></tr>";
                    echo "</tbody></table>";
                } else { ?>
                    <tr>
                       <td></td>
                       <td>
                         <form method="POST" action="teams.php">
                            <input type="hidden" name="teamnaam" value="<?php echo htmlentities($team['teamnaam']);?>">
                            <input type="submit" name="lid_toevoegen" value="Lid toevoegen" class="knop plusknop">
                         </form>
                       </td>
                    </tr>
<?php           }
            }
            echo "</tbody></table></div></div>";
        }

    } else echo "Er zijn geen teams gevonden";
    if ($extra_team){
?>
<div class="team nieuw_team">
    <div class="column">
        <form action="teams.php" method="post" class="column">
        <input type="text" name="teamnaam" placeholder="Teamnaam" >
        <textarea name="omschrijving" placeholder="Omschrijving" ></textarea>
        <div class="rij">
            <button type="submit" name="nieuw_team" class="knop plusknop">Voeg nieuw team toe</button>
            <button  class="knop cancelKnop">Cancel</button>
        </div>    
        </form>
    </div>    
</div>
    <?php } else {?>
        <div class="team nieuw_team">
            <form action="teams.php" method="post">
            <input type="submit" name="extra_team" class="knop plusknop" value="Maak een nieuw team">
            </form>
        </div>    
    <?php } ?>
<?php 
include_once 'includes/footer.php';
?>