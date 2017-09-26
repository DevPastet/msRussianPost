<?php 
// Загружаем библиотеку
require_once 'postcalc_light_lib.php';
extract($arrPostcalcConfig,EXTR_PREFIX_ALL,'postcalc_config');
// Инициализируем значения полей формы
$postcalc_from = ( isset($_GET['postcalc_from']) ) ? $_GET['postcalc_from'] : $postcalc_config_default_from;
$postcalc_to = ( isset($_GET['postcalc_to']) ) ? $_GET['postcalc_to'] : '190000';
$postcalc_weight = ( isset($_GET['postcalc_weight'])) ? $_GET['postcalc_weight'] : 1000;
$postcalc_valuation = ( isset($_GET['postcalc_valuation']) ) ?$_GET['postcalc_valuation']:1000;
$postcalc_country=(isset($_GET['postcalc_country'])) ? $_GET['postcalc_country'] : 'Ru';
// Выдаем заголовок с указанием на кодировку
header("Content-Type: text/html; charset=$postcalc_config_cs");
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Клиент PostcalcLight</title>
        <meta charset='<?=$postcalc_config_cs?>'> 
        <style>
            #postcalc_form { width: 34em; margin-left: 2em }
            #postcalc_form label { display: inline-block; width: 8em; }
            #postcalc_form legend { width:20em;padding:0.2em;padding-left:1em;padding-right:1em; } 
            #postcalc_loader {left:17em;top:5em}
            #postcalc_country  { width:20em }
            #postcalc_from  { width:20em }
            #postcalc_to { width:20em }
            #postcalc_from_to { width: 32em; margin-left: 5em; }
            #postcalc_from_to TD { vertical-align: top }
            #postcalc_table { margin-left: 5em }
            #postcalc_table TD { padding:0.2em;padding-left:1em }
            #postcalc_table TH  { padding:0.5em; }
            #postcalc_wv { display: none }
            .center { text-align: center;} 
            @media print {
                    body { font-family: sans-serif, Arial; font-size: 10pt }
                    #postcalc_table {  background-color: grey }
                    #postcalc_table TR {  background-color: white }
                    #postcalc_form { display:none }
                    #postcalc_wv { display: block }
            }
       </style>
       <link rel='stylesheet' href='//yandex.st/jquery-ui/1.10.4/themes/start/jquery-ui.min.css' type='text/css' media='screen' />
       <script src='//yandex.st/jquery/1.10.2/jquery.min.js'></script>
       <script src='//yandex.st/jquery-ui/1.10.4/jquery-ui.min.js'></script>
       <!--[if lt IE 8]><script>var OldIE = true;</script><![endif]-->
       <script src='postcalc_light_form_plugin.js'></script>
        <script>
        // Если возникают конфликты с другими библиотеками Javascript, использующими знак $, раскомментируйте следующую строчку
        //jQuery.noConflict();
        jQuery(document).ready(function( $ ){
            //Инициализируем форму
            $.fn.postcalc_light_form('postcalc_form');
        });
       </script> 
    </head>
    <body>
        
        <?php // Раскомментируйте, чтобы доступ был только по паролю $arrPostcalcConfig['pass'].
        //require 'postcalc_light_auth.php'; ?>
        <div id="postcalc" class="ui-widget">
        <form id="postcalc_form">
             <fieldset  class="ui-widget-content ui-corner-all">
        
               <legend  class="ui-widget-header ui-corner-all" >Доставка Почтой России и EMS</legend>
               <span style="display:<?= ($postcalc_config_hide_from) ? 'none' : 'block' ?>">
               <label for="postcalc_from" title="Отделение связи отправителя - шестизначный индекс или местоположение EMS (название региона или центр региона)"> Откуда </label>
               <input type="text" id="postcalc_from" name="postcalc_from" data-autocomplete-url="postcalc_light_autocomplete.php" data-validation-error="В базе данных Клиента данное отделение связи отправителя не найдено." size="20" value="<?=$postcalc_from?>"/>
               <br>
               </span>
               <label for="postcalc_to" title="Отделение связи получателя - шестизначный индекс  или местоположение EMS (название региона или центр региона)"> Куда </label>
               <input type="text"  id="postcalc_to" name="postcalc_to" data-autocomplete-url="postcalc_light_autocomplete.php" data-validation-error="В базе данных Клиента данное отделение связи получателя не найдено." size="20" value="<?=$postcalc_to?>"/>
               <br>
               <label for="postcalc_weight" title="Вес отправления в граммах - от 1 до 100000"> Вес, г </label>
               <input type="text" data-range="1,100000" data-validation-error="Вес отправления в граммах - от 1 до 100000" id="postcalc_weight" name="postcalc_weight" size="6" value="<?=$postcalc_weight?>"/>
               <br>
                <label for="postcalc_valuation" title="Оценка товарного вложения в рублях - от 0 до 100000">Оценка, руб. </label>
                <input type="text" data-range="0,100000" data-validation-error="Оценка товарного вложения в рублях - от 0 до 100000" id="postcalc_valuation" name="postcalc_valuation" size="6" value="<?=$postcalc_valuation?>"/>
                <br>
                <span style="display:block">
                <label for="postcalc_country" title="Страна назначения">Страна</label>
                <select id="postcalc_country" name="postcalc_country" size="1">
                    <?php  $arrPostcalcCountries=postcalc_arr_from_txt('postcalc_light_countries.txt'); 
                           echo postcalc_make_select($arrPostcalcCountries,$postcalc_country);
                    ?>
                </select><br>
                </span>
            </fieldset>
            <input type="submit" value="Рассчитать!" class="ui-button" onclick="javascript:ldr=document.getElementById('postcalc_loader');ldr.style.display='block';" id='postcalc_form_submit' style="margin-left:11em">
        </form>
       <div id='postcalc_loader' style='position: absolute;display:none'><img src='ajax-loader.gif' alt='Индикатор загрузки'></div>  
<?php
if ( isset($_GET['postcalc_from']) ) {
    
// Обращаемся к функции getPostcalc
$arrResponse=postcalc_request($_GET['postcalc_from'],$_GET['postcalc_to'],$_GET['postcalc_weight'],$_GET['postcalc_valuation'],$_GET['postcalc_country']);

// Если вернулась строка - это сообщение об ошибке.
if ( !is_array($arrResponse) ) {
    echo "<span class='ui-state-error'>Произошла ошибка:</span><br> $arrResponse";
    if ( count(error_get_last()) ){
        $arrError=error_get_last();
        echo "<br><span class='ui-state-error'>Ошибка PHP, строка $arrError[line] в файле $arrError[file]:</span><br> $arrError[message]";
    };
} else {
// Вернулся массив, Status=='OK'. 
// Откуда и Куда
echo "
    <div id='postcalc_from_to' class='ui-widget'>
 
        <b>Откуда:</b><br>
    {$arrResponse['Откуда']['Индекс']}, {$arrResponse['Откуда']['Название']}
            <br> {$arrResponse['Откуда']['Адрес']} 
            <br> {$arrResponse['Откуда']['Телефон']}
";
if ($_GET['postcalc_country']=='Ru') {
echo " <br> 
    <b>Куда:</b><br>
    {$arrResponse['Куда']['Индекс']}, {$arrResponse['Куда']['Название']}
            <br>{$arrResponse['Куда']['Адрес']} 
            <br>{$arrResponse['Куда']['Телефон']}

";
} else {
echo "<br>
       <b>Куда:</b><br>
       Международная доставка: {$arrPostcalcCountries[$postcalc_country]}
 
";
}
echo "<span id='postcalc_wv'><b>Вес:</b> $postcalc_weight г.<br>\n<b>Оценка:</b> $postcalc_valuation руб.</span>
</div>";
// Выводим таблицу отправлений
echo "
<br>
<br>
<table id='postcalc_table' class='ui-widget-content ui-corner-all'>
<tr class='ui-widget-header ui-widget-content'><th>Название</th><th>Доставка</th><th>Сроки</th></tr>
";

// Выводим список тарифов
$counter=0;
foreach ( $arrResponse['Отправления'] as $parcel ) {
    if ($parcel['Доставка']) {
	echo "<tr";
        // Расцветка четных полос. 
        if ( $counter % 2 ) echo " class='ui-state-highlight'";
        echo "><td>$parcel[Название] </td><td>".number_format($parcel['Доставка'],2,',',' ')." руб.</td><td class='center'>$parcel[СрокДоставки]</td></tr>\n";
    }
    $counter++;
}
echo '</table>';

}

}
?>
    
            </div>
        </body>
</html>