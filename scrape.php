<?php
include('scrape_action.php');
// $link = file_get_contents('https://sprzedajemy.pl/klinkier-cegla-na-kominki-wedzarnie-zgierz-2-a1d299-nr62480945');
// libxml_use_internal_errors(TRUE);
// $dom = new DOMDocument();
// $dom->loadHTML($link);
// $xml = simplexml_import_dom($dom);
// libxml_use_internal_errors(FALSE);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
            integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body>
<style>
    body {
        font-family: sans-serif;
        font-size: 17px;
        display: flex;
        flex-direction: column;
    }
    h1 {
        color: gray;
    }
    .input-txt {
        width: 500px;
        height: 25px;
        display: flex;
        margin-top: 2px;
    }
    .label-txt {
        font-size: 22px;
        font-weight: bold;
    }
    .button {
        padding: 5px 20px;
        font-size: 15px;
        font-weight: bold;
        margin-top: 10px;
    }
    .list {
        width: 250px;
        height: 28px;
        margin-top: 15px;
        font-size: 16px;
    }
</style>
<h1>Scraping (Sprzedajemy.pl)</h1>
<form action="scrape_action.php" method="post">
    <label class='label-txt'>Link do ogłoszenia: </label>
    <input type='text' class='input-txt' name='link'></input>
    <label>Select category:</label>
    <select id="cat" name='cat' class='list'>
        <option></option>
    </select>
    <input style='display:none' name='cat' type="text"/>
    </br>
    <label>Select subcategory:</label>
    <select id="subcat" class='list'></select>
    <input style='display:none' name='scat' type="text"/>
    </br>
    <button type='submit' class='button' name='mass-add'>Dodaj</button>
    <button type='submit' class='button' name='check-links'>Sprawdź linki</button>
</form>
</body>
<script>
    $(document).ready(function () {

        //Pobieranie nazw kategorii z bazy danych
        $.post("scrape_action.php", {action: 'select_cat'}, function (response) {
            $('#cat').append(response);
        });

        //Pobieranie subcategorii po wybraniu kategorii
        //Przypisywanie inputowi id kategorii
        $('#cat').click(function () {
            let catDataId = $("#cat").val();
            $('input[name=cat]').val(catDataId);
            if (!catDataId == '') {
                $.post("scrape_action.php", {action: 'select_scat', category: catDataId}, function (response) {
                    $('#subcat').html(response);
                });
            }
        });
        
        //Przypisywanie inputowi id subkategorii
        $('#subcat').click(function () {
            let scat_data_id = $("#subcat").val();
            $('input[name=scat]').val(scat_data_id);
        });
    });
</script>
</html>