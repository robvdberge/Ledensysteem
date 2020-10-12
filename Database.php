<?php
require_once 'setup/setup.php';

/*=============================================================
=   Database Class
=   1.      Constructor & connectiesluiten
=   2.      Lidbewerking
=   2.1.    Postcode
=   2.2.    Telefoonnummers
=   2.3.    Emailadressen
=   3.      Team
=   3.1     Teamlid
=   4.      Hulpmethodes
=================================================================*/

class Database
{
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli(SERVER_NAME, USER_NAME, PASSWORD, DB_NAME);
        if ($this->conn->connect_error) die ("Fatale fout: " . $this->conn->connect_error);
        return TRUE;
    }

    public function sluit()
    {
        $this->conn->close();
    }
    /*********************** Lid ****************************/
    // CREATE
    public function nieuw_lid($post)
    {
        // Saniteer
        $naam               = $this->saniteer_dbquery($post['naam']);
        $voornaam           = $this->saniteer_dbquery($post['voornaam']);
        $postcode           = $this->saniteer_dbquery($post['postcode']);
        $huisnummer         = $this->saniteer_dbquery($post['huisnummer']);
        // Prepare
        $stmt = $this->conn->prepare("INSERT INTO Lid(naam, voornaam, postcode, huisnummer) VALUES(?,?,?,?)");
        // Bind
        $stmt->bind_param('ssss', $naam, $voornaam, $postcode, $huisnummer);
        // Execute
        $stmt->execute();
        if ($stmt->error){
            return "Er is iets fout gegaan: " . $stmt->error;
        } else {
            // verkrijg zojuist gemaakte lidnummer
            $post['lidnummer'] = $this->conn->insert_id;
            if (!$this->nieuw_telefoonnummer($post)){ return FALSE; }
            if (!$this->nieuw_emailadres($post)){ return FALSE; }
            if (!$this->nieuwe_postcode($post)){ return FALSE; }
            return TRUE;
        }
    }

    // READ
    public function haal_alle_leden()
    {
        $query = "SELECT * FROM Lid 
                    LEFT JOIN Postcode ON Postcode.postcode = Lid.postcode
                    ";
        $result = $this->conn->query($query);
        if (!$result) die ("Fatale fout:" . $this->conn->error);
        return $result;
    }

    // UPDATE
    public function wijzig_lid($post)
    {
        // Saniteer alles in $post
        $lidnummer          = $this->saniteer_dbquery($post['lidnummer']);
        $naam               = $this->saniteer_dbquery($post['naam']);
        $voornaam           = $this->saniteer_dbquery($post['voornaam']);
        $postcode           = $this->saniteer_dbquery($post['postcode']);
        $huisnummer         = $this->saniteer_dbquery($post['huisnummer']);
        
        // check of niets leeg is
        if ($lidnummer != '' && $naam != '' && $voornaam != '' && $postcode != '' && $huisnummer != ''){
            // prepare de statements
            $queryLid = "UPDATE Lid SET naam=?, voornaam=?, postcode=?, huisnummer=? WHERE lidnummer=?";
            $stmt = $this->conn->prepare($queryLid);
            // Bind de statements
            $stmt->bind_param('ssssi', $naam, $voornaam, $postcode, $huisnummer, $lidnummer);
            // Execute de statements
            $stmt->execute();
            if ($stmt->error){
                echo "Er is iets fout gegaan: " . $stmt->error;
                return FALSE;
            } else {
                // check of postcode al bestaat-> zo niet maak nieuwe postcode, anders pas bestaande aan
                $gevonden = $this->haal_postcode($post);
                $gevonden = $gevonden->num_rows;
                if ( $gevonden <= 0 ){
                    // maak nieuwe postcode
                    $response = $this->nieuwe_postcode($post);
                    if ($response != TRUE){ 
                        return "Er is iets fout gegaan: ". $response; 
                    }
                } else {
                    // update bestaande postcode
                    $this->wijzig_postcode($post);
                    return TRUE;
                }
                return TRUE;
            }  
        } 
        return "Er mogen geen lege velden worden ingevoerd.";
    }

    // DELETE
    public function verwijder_lid($post)
    {
        // verwijder bijbehorende telefoonnummer(s) en emailadres(sen)
        $this->verwijder_telefoonnummer_per_lidnr($_POST);
        $this->verwijder_email_per_lidnr($_POST);
        
        $lidnummer = $this->saniteer_dbquery($post['lidnummer']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Lid WHERE lidnummer=?");
        // bind
        $stmt->bind_param('i', $lidnummer);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return "Er is iets fout gegaan: " . $stmt->error;
        }
    }

    /*********************** Postcode ****************************/
    // CREATE
    public function nieuwe_postcode($post)
    {
        // Sanitize wat verstuurd is
        $postcode    = $this->saniteer_dbquery($post['postcode']);
        $adres       = $this->saniteer_dbquery($post['adres']);
        $woonplaats  = $this->saniteer_dbquery($post['woonplaats']);

        // Check of input niet leeg is
        if ( $postcode != '' && $adres != '' && $woonplaats != '' ){
            // Bereidt de query voor
            $stmt = $this->conn->prepare("INSERT INTO Postcode VALUES(?,?,?)");
            // bind de parameters aan de query
            $stmt->bind_param('sss', $postcode, $adres, $woonplaats);
            $stmt->execute();
            if ($stmt->error){ return $stmt->error; }
            return TRUE;
        } else {
            return "Er mogen geen lege velden worden ingevoerd.";
        }
    }
    // READ
    public function haal_postcode($post)
    {
        $postcode = $this->saniteer_dbquery($post['postcode']);
        if ($postcode != ''){
            // Prepare de query
            $stmt = $this->conn->prepare("SELECT postcode FROM Postcode WHERE postcode=?");
            // bind de query
            $stmt->bind_param('s', $postcode);
            // voer uit
            $stmt->execute();
            if ($stmt->error) die( "Fatale fout: " . $stmt->error ); 
            // haal het resultaat op
            $result = $stmt->get_result();
            if ($result){
                return $result;
            } else {
                return FALSE;
            }
        } else { return "Er mag geen leeg veld worden ingevoerd."; }
    }

    // UPDATE
    public function wijzig_postcode($post)
    {
        // Saniteer alles in $post
        $postcode        = $this->saniteer_dbquery($post['postcode']);
        $adres           = $this->saniteer_dbquery($post['adres']);
        $woonplaats      = $this->saniteer_dbquery($post['woonplaats']);
        
        // check of niets leeg is
        if ($adres != '' && $postcode != '' && $woonplaats != ''){

            // prepare de statements
            $queryLid = "UPDATE Postcode SET adres=?, woonplaats=? WHERE postcode=?";
            $stmt = $this->conn->prepare($queryLid);
            // Bind de statements
            $stmt->bind_param('sss', $adres, $woonplaats, $postcode);
            // Execute de statements
            $stmt->execute();
            if ($stmt->error){
                return "Er is iets fout gegaan: " . $stmt->error;
            } else {
                return TRUE;
            }  
        }
    }

    // DELETE

    /********************* Telefoonnummers *******************/
    // CREATE
    public function nieuw_telefoonnummer($post)
    {
        $result = '';
        // Kijk of telefoonnummers(array) bestaat
        if (isset($post['telefoonnummers']) && $post['telefoonnummers'][0] != ''){
            foreach ($post['telefoonnummers'] as $key => $value) {
                // Saniteer
                $telefoonnummer     = $this->saniteer_dbquery($value);
                $lidnummer          = $this->saniteer_dbquery($post['lidnummer']);
                if ($lidnummer != '' && $telefoonnummer != ''){
                    // Controleer of het telefoonnummer al is gebruikt
                    if ( $this->telnr_gevonden($post) ){
                        return "Dit telefoonnummer bestaat al, kies een andere";
                    } else {
                        // Prepare
                        $stmt = $this->conn->prepare("INSERT INTO Telefoonnummers(telefoonnummer, lidnummer) VALUES(?,?)");
                        // Bind
                        $stmt->bind_param('si', $telefoonnummer, $lidnummer);
                        // Execute
                        $stmt->execute();
                        if ($stmt->error){
                            $result = "Er is iets fout gegaan: " . $stmt->error;
                        } else {
                            $result = TRUE;
                        }
                    }
                } else { $result = "Er mogen geen lege velden worden ingevoerd.";}
            }
        }   // Wanneer telefoonnummers niet bestaat(geen array)
        if ($post['telefoonnummer'] != ''){
            // Saniteer
            $lidnummer          = $this->saniteer_dbquery($post['lidnummer']);
            $telefoonnummer     = $this->saniteer_dbquery($post['telefoonnummer']);
            if ($lidnummer != '' && $telefoonnummer != ''){
                // Controleer of het telefoonnummer al is gebruikt
                if ( $this->telnr_gevonden($post) ){
                    $result = "Dit telefoonnummer bestaat al, kies een andere";
                } else {
                    // Prepare
                    $stmt = $this->conn->prepare("INSERT INTO Telefoonnummers(telefoonnummer, lidnummer) VALUES(?,?)");
                    // Bind
                    $stmt->bind_param('si', $telefoonnummer, $lidnummer);
                    // Execute
                    $stmt->execute();
                    if ($stmt->error){
                        $result = "Er is iets fout gegaan: " . $stmt->error;
                    } else {
                        $result = TRUE;
                    }
                }
            } else { $result = "Er mogen geen lege velden worden ingevoerd.";}
        }
        return $result;
    }

    // READ
    public function zoek_telnrs($id)
    {
        // Saniteer input
        $id = $this->saniteer_dbquery($id);
        // prepare query
        $stmt = $this->conn->prepare("SELECT * FROM Telefoonnummers WHERE lidnummer=?");
        // bind query
        $stmt->bind_param('i', $id);
        // Execute
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) die ("fatale fout: " . $stmt->error);
        return $result;
    }

    public function telnr_gevonden($post)
    {
        // Saniteer input
        $telefoonnummer = $this->saniteer_dbquery($post['telefoonnummer']);
        // prepare query
        $stmt = $this->conn->prepare("SELECT * FROM Telefoonnummers WHERE telefoonnummer=?");
        // bind query
        $stmt->bind_param('s', $telefoonnummer);
        // Execute
        $stmt->execute();
        $result = $stmt->get_result();
        $num_gevonden = $result->num_rows;
        if ($num_gevonden > 0){
            return TRUE;
        } else {
            return FALSE; 
        }
        
    }

    // UPDATE
    public function wijzig_telnr($post)
    {
         // Saniteer alles in $post
         $telefoonnummer    = $this->saniteer_dbquery($post['telefoonnummer']);
         $lidnummer         = $this->saniteer_dbquery($post['lidnummer']);
         $telnr_oud         = $this->saniteer_dbquery($post['telnr_oud']);
         // check of niets leeg is
         if ($telefoonnummer != '' && $telnr_oud != ''){
             // prepare de statements
             $query = "UPDATE Telefoonnummers SET telefoonnummer = ? WHERE telefoonnummer = ?";
             $stmt = $this->conn->prepare($query);
             // Bind de statements
             $stmt->bind_param('ss', $telefoonnummer, $telnr_oud);
             // Execute de statements
             $stmt->execute();
             if ($stmt->error){
                 echo $stmt->error;
                 return FALSE;
             } else {
                 return TRUE;
             }  
         }
    }

    // DELETE
    public function verwijder_telefoonnummer($post)
    {
        $telefoonnummer = $this->saniteer_dbquery($post['telefoonnummer']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Telefoonnummers WHERE telefoonnummer=?");
        // bind
        $stmt->bind_param('s', $telefoonnummer);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function verwijder_telefoonnummer_per_lidnr($post)
    {
        $lidnummer = $this->saniteer_dbquery($post['lidnummer']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Telefoonnummers WHERE lidnummer=?");
        // bind
        $stmt->bind_param('i', $lidnummer);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /********************* Emailadressen ***********************/
    // CREATE
    public function nieuw_emailadres($post)
    {
        $result = '';
        // Check of $post[emailadressen] bestaat en of eerste niet leeg is
        if ( isset($post['emailadressen']) && $post['emailadressen'][0] != ''){
            foreach ($post['emailadressen'] as $key => $value) {
                // Saniteer
                $lidnummer      = $this->saniteer_dbquery($post['lidnummer']);
                $emailadres     = $this->saniteer_dbquery($value);
                
                // Prepare
                $stmt = $this->conn->prepare("INSERT INTO Email(emailadres, lidnummer) VALUES(?,?)");
                // Bind
                $stmt->bind_param('si', $emailadres, $lidnummer);
                // Execute
                $stmt->execute();
                if ($stmt->error){
                    $result = "Er is iets fout gegaan: " . $stmt->error;
                } else {
                    $result = TRUE;
                }
            }
        }
        if ($post['emailadres'] != '' ){
            // Saniteer
            $lidnummer      = $this->saniteer_dbquery($post['lidnummer']);
            $emailadres     = $this->saniteer_dbquery($post['emailadres']);
            
            // Prepare
            $stmt = $this->conn->prepare("INSERT INTO Email(emailadres, lidnummer) VALUES(?,?)");
            // Bind
            $stmt->bind_param('si', $emailadres, $lidnummer);
            // Execute
            $stmt->execute();
            if ($stmt->error){
                $result = "Er is iets fout gegaan: " . $stmt->error;
            } else {
                $result = TRUE;
            }
        }
        return $result; 
    }

    // READ
    public function zoek_email($id)
    {
        $id = $this->saniteer_dbquery($id);
        $query = "SELECT * FROM Email WHERE lidnummer=?";
        // Prepare
        $stmt = $this->conn->prepare($query);
        // Bind
        $stmt->bind_param('i', $id);
        // Execute
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) die ("Er is iets fout gegaan: ". $stmt->error);
        return $result;
    }
    
    public function email_gevonden($post)
    {
        // Saniteer input
        $email = $this->saniteer_dbquery($post['emailadres']);
        // prepare query
        $stmt = $this->conn->prepare("SELECT * FROM Email WHERE emailadres=?");
        // bind query
        $stmt->bind_param('s', $email);
        // Execute
        $stmt->execute();
        $result = $stmt->get_result();
        $num_gevonden = $result->num_rows;
        if ($num_gevonden > 0){
            return TRUE;
        } else {
            return FALSE; 
        }  
    }

    // UPDATE
    public function wijzig_emailadres($post)
    {
        // Saniteer alles in $post
        $emailadres        = $this->saniteer_dbquery($post['emailadres']);
        $lidnummer         = $this->saniteer_dbquery($post['lidnummer']);
        $email_oud         = $this->saniteer_dbquery($post['email_oud']);
        // check of niets leeg is
        if ($emailadres != '' && $email_oud!= ''){
            // prepare de statements
            $queryLid = "UPDATE Email SET emailadres = ? WHERE emailadres = ?";
            $stmt = $this->conn->prepare($queryLid);
            // Bind de statements
            $stmt->bind_param('ss', $emailadres, $email_oud);
            // Execute de statements
            $stmt->execute();
            if ($stmt->error){
                echo $stmt->error;
                return FALSE;
            } else {
                return TRUE;
            }  
        }
    }

    // DELETE
    public function verwijder_email($post)
    {
        $emailadres = $this->saniteer_dbquery($post['emailadres']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Email WHERE emailadres=?");
        // bind
        $stmt->bind_param('s', $emailadres);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function verwijder_email_per_lidnr($post)
    {
        $lidnummer = $this->saniteer_dbquery($post['lidnummer']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Email WHERE lidnummer=?");
        // bind
        $stmt->bind_param('i', $lidnummer);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }
    /************************ Team ***************************/
    // CREATE
    public function maak_nieuw_team($post)
    {
        $teamnaam     = $this->saniteer_dbquery($post['teamnaam']);
        $omschrijving = $this->saniteer_dbquery(rtrim($post['omschrijving']));
        $omschrijving = str_replace(array('\n','\r' ), ' ', $omschrijving);
        $stmt = $this->conn->prepare("INSERT INTO Teams(teamnaam, omschrijving) VALUES(?,?)");
        // Bind
        $stmt->bind_param('ss', $teamnaam, $omschrijving);
        // Execute
        $stmt->execute();
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }

    // READ
    Public function haal_alle_teams()
    {
        $query = "SELECT * FROM Teams";
        $result = $this->conn->query($query);
        if (!$result) die ("Fatale fout:" . $this->conn->error);
        return $result;
    }

    // UPDATE
    public function wijzig_team($post)
    {
        $teamnaam       = $this->saniteer_dbquery($post['teamnaam']);
        $omschrijving   = $this->saniteer_dbquery($post['omschrijving']);
        $oude_naam      = $this->saniteer_dbquery($post['oude_naam']);
        $this->wijzig_alle_teamleden($post);
        $stmt = $this->conn->prepare("UPDATE Teams SET teamnaam=?, omschrijving=? WHERE teamnaam=?");
        // Bind
        $stmt->bind_param('sss', $teamnaam, $omschrijving, $oude_naam);
        // Execute
        $stmt->execute();
        if ($stmt->error){
            echo $stmt->error;
            return FALSE;
        } else {
            return TRUE;
        }
    }
    

    // DELETE
    public function verwijder_team($post)
    {
        $teamnaam = $this->saniteer_dbquery($post['teamnaam']);
        $this->verwijder_alle_leden($post);
        $stmt = $this->conn->prepare("DELETE FROM Teams WHERE teamnaam=?");
        // Bind
        $stmt->bind_param('s', $teamnaam);
        // Execute
        $stmt->execute();
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }
    /********************** Teamlid **************************/
    // CREATE
    public function voeg_teamlid_toe($post)
    {
        $teamnaam  = $this->saniteer_dbquery($post['teamnaam']);
        $lidnummer = $this->saniteer_dbquery($post['lidnummer']);
        // prepare
        $stmt = $this->conn->prepare("INSERT INTO Teamlid(teamnaam, lidnummer) VALUES(?,?)");
        // bind
        $stmt->bind_param('si', $teamnaam, $lidnummer);
        $stmt->execute();
        if ($stmt->error){
            echo $stmt->error;
            return FALSE;
        } else {
            return TRUE;
        }
    }

    // READ
    public function haal_teamleden_per_teamnaam($teamnaam)
    {
        $teamnaam = $this->saniteer_dbquery($teamnaam);
        // prepare
        $stmt = $this->conn->prepare("SELECT * FROM Teamlid JOIN Lid on Lid.lidnummer = Teamlid.lidnummer WHERE teamnaam = ?");
        // bind
        $stmt->bind_param('s', $teamnaam);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result){
            return "Er is iets foutgegaan: " . $stmt->error;
        } else {
            return $result;
        }
    }
    // Geeft een array van lidnummers terug van leden in een bepaald team
    public function array_alle_teamleden($post)
    {
        $teamnaam = $this->saniteer_dbquery($post['teamnaam']);
        // prepare
        $stmt = $this->conn->prepare("SELECT * FROM Teamlid");
        // óf wanneer teamleden in meerdere teams mogen zitten:
        // $stmt = $this->conn->prepare("SELECT * FROM Teamlid WHERE teamnaam=?");
        // bind
        // $stmt->bind_param('s', $teamnaam);

        // bind
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result){
            return FALSE;
        } else {
            $aantal_teamleden = $result->num_rows;
            if ($aantal_teamleden == 0) return array();
            for ( $i = 0; $i < $aantal_teamleden; $i++){
                $teamlid = $result->fetch_array(MYSQLI_ASSOC);
                $lidnummers[$i] = $teamlid['lidnummer'];;
            }
            return $lidnummers;
        }

    }

    // UPDATE
    public function wijzig_alle_teamleden($post)
    {
        $teamnaam       = $this->saniteer_dbquery($post['teamnaam']);
        $oude_naam      = $this->saniteer_dbquery($post['oude_naam']);
        $stmt = $this->conn->prepare("UPDATE Teamlid SET teamnaam=? WHERE teamnaam=?");
        // Bind
        $stmt->bind_param('ss', $teamnaam, $oude_naam);
        // Execute
        $stmt->execute();
        if ($stmt->error){
            echo $stmt->error;
            return FALSE;
        } else {
            return TRUE;
        }
    }

    // DELETE
    public function verwijder_lid_uit_team($post)
    {
        $lidnummer = $this->saniteer_dbquery($post['lidnummer']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Teamlid WHERE lidnummer=?");
        // bind
        $stmt->bind_param('i', $lidnummer);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }
    public function verwijder_alle_leden($post)
    {
        $teamnaam = $this->saniteer_dbquery($post['teamnaam']);
        // prepare
        $stmt = $this->conn->prepare("DELETE FROM Teamlid WHERE teamnaam=?");
        // bind
        $stmt->bind_param('s', $teamnaam);
        // execute
        $stmt->execute();
        // return
        if ($stmt->error){
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /********************* Hulpmethodes **********************/
    
    public function saniteer($string)
    {
        if (get_magic_quotes_gpc()){
          $string = stripslashes($string);  
        }
        $string = strip_tags($string);
        $string = htmlentities($string);
        return $string;
    }

    public function saniteer_dbquery($string)
    {
        $string = $this->conn->real_escape_string($string);
        $string = $this->saniteer($string);
        return $string;
    }

    public function check_of_leeg_is($post, $string_array)
    {   
        $legevelden = FALSE;
        foreach ($string_array as $key => $value) {
            if ( $post[ $value ] == '' ){
                $legevelden = TRUE;
            }
        }
        if ($legevelden){
            return FALSE;
        } else {
            return TRUE;
        }
    }
}