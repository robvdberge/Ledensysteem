$(document).ready(function(){
    
    // Wanneer op de deleteknop geklikt wordt; geef een bevestiging
    $('.del_btn').on('click', function(e){
        result = confirm("Weet je zeker dat je dit lid wilt verwijderen?");
        if (!result){
            e.preventDefault();
            window.location.href = window.location.href;
        }
    })

    // Voeg meerdere emailadressen toe voor er een nieuw lid wordt aangemaakt
    $('#addEmail').on('click', function(e){
        e.preventDefault();
        // Lees #emailadressen uit
        var email = $('#tempEmail').val();
        // Maak een nieuwe option in #emailadressen select
        $("#emailadressen").append('<option value=' + email + ' selected>' + email + '</option>');
        // verwijder getypte text in tempEmail
        $('#tempEmail').val('');
    });

    // Voeg meerdere telefoonnummers toe voor er een nieuw lid wordt aangemaakt
    $('#addTelnr').on('click', function(e){
        e.preventDefault();
        // Lees #telefoonnummer uit
        var telnr = $('#tempTelefoon').val();
        // Maak een nieuwe option in #telefoonnummers select
        $("#telefoonnummers").append("<option value=" + telnr + " selected>" + telnr + "</option>");
        // verwijder getypte text in tempTelefoon
        $('#tempTelefoon').val('');

    });


})