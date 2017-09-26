<?php
require_once 'postcalc_light_config.php';
/**
 * Основная функция опроса сервера Postcalc.RU
 * 
 * Настройки хранятся в конфигурационном файле файле postcalc_light_config.php.<br>
 * 
 * Принимает следующие данные: отправитель, получатель, вес, оценка, страна. <br>
 *  
 * 1). Проверяет эти данные, при ошибке возвращает строку с сообщением об ошибке.<br>
 * 
 * 2). В цикле опрашивает сервера проекта Postcalc.RU (переменная servers
 * конфигурационного файла).<br>
 * 
 * 3). В случае успеха возвращает массив с полученными от сервера данными, 
 * при ошибке - строку с сообщением об ошибке.<br>
 * 
 * 4). Использует кэширование: в случае успеха записывает ответ в каталог cache_dir, 
 * хранит ответ в течение cache_valid секунд. <br>
 * 
 * <code>
 * $Response=postcalc_request('101000', 'Ленинградская область', 505.1, 1000, 'RU');
 * 
 * if (is_array($Response)) {
 *      echo $Response['Отправления']['ПростаяБандероль']['Тариф'];
 *      } else {
 *      echo "Ошибка: $Response";
 * }
 * </code>
 * 
 * @uses postcalc_get_default_ops() Используется при валидации отправителя и получателя.
 * @uses postcalc_arr_from_txt() Используется при валидации страны.
 * 
 * @param string $From Отправитель. Либо 6-значный индекс ОПС, который проверяется по базе postcalc_light_post_indexes.txt,
 * либо наименование региона/центра региона, которое проверяется по базе postcalc_light_locations.txt.
 * 
 * @param string $To Получатель. Либо 6-значный индекс ОПС, который проверяется по базе postcalc_light_post_indexes.txt,
 * либо наименование региона/центра региона, которое проверяется по базе postcalc_light_locations.txt.
 * 
 * @param float $Weight Вес в граммах, от 1 до 100000.
 * 
 * @param float $Valuation Оценка почтового отправления в рублях, от 0 до 100000.
 * 
 * @param string $Country Двухбуквенный код страны, проверяется по базе postcalc_light_countries.txt.
 *    Если отличается от Ru, поле $To игнорируется.
 * 
 * @return array|string В случае успеха возвращает массив с данными, полученными от сервера Postcalc.RU.
 *  При ошибке возвращает строку с сообщением об ошибке.
 * 
 * @since 10.05.2014
 * 
 * @author Postcalc.RU <postcalc@mail.ru>
 * 
 * @version 1.05
 * 
 *
 * 
 */
function postcalc_request($From,$To,$Weight,$Valuation=0,$Country='RU')
{
    global $arrPostcalcConfig;
    extract($arrPostcalcConfig,EXTR_PREFIX_ALL,'config');
    // Обязательно! Проверяем данные - больше всего ошибочных запросов из-за неверных значений веса и оценки,
    // из-за пропущенного поля "Куда".
    if ( !is_numeric($Weight) || !($Weight>0 && $Weight<=100000) ) 
                return "Bec в граммах - число от 1 до 100000, десятичный знак - точка!";
    if ( !is_numeric($Valuation) || !($Valuation>=0 && $Valuation<=100000) ) 
                return "Оценка в рублях - число от 0 до 100000, десятичный знак - точка!";
    
    // Отдельная функция проверяет правильность полей Откуда и Куда
    $From=mb_convert_case($From, MB_CASE_TITLE, $config_cs);
    if ( !postcalc_get_default_ops($From) ) 
            return "Поле 'Откуда': '$From' - не является допустимым индексом, названием региона или центра региона!";
    
    $To=mb_convert_case($To, MB_CASE_TITLE, $config_cs);
    if ( !postcalc_get_default_ops($To) ) 
            return "Поле 'Куда': '$To' - не является допустимым индексом, названием региона или центра региона!";
    // Переводим в "процентную" кодировку
    $From=rawurlencode($From);  $To=rawurlencode($To);
    
    $Country=mb_convert_case($Country, MB_CASE_TITLE, $config_cs);
    if ( !postcalc_arr_from_txt('postcalc_light_countries.txt', $Country, 1) ) return "Код страны '$Country' не найден в базе стран!";

    // Формируем запрос со всеми необходимыми переменными. 
    $QueryString  = "st=$config_st&ml=$config_ml";
    $QueryString .= "&f=$From&t=$To&w=$Weight&v=$Valuation&c=$Country";
    $QueryString .= "&o=php&sw=PostcalcLight_1.05&cs=$config_cs";
    if ( $config_d != 'now' ) $QueryString .= "&d=$config_d";
    if ( $config_ib != 'f' ) $QueryString .= "&ib=$config_ib";
    if ( $config_r != 0.01 ) $QueryString .= "&r=$config_r";
    if ( $config_pr > 0 ) $QueryString .= "&pr=$config_pr";    
    if ( $config_pk > 0 ) $QueryString .= "&pk=$config_pk";  
     
    // Название файла - префикс postcalc_ плюс хэш строки запроса
    $CacheFile="$config_cache_dir/postcalc_".md5($QueryString).'.txt';
    // Сборка мусора. Удаляем все файлы, которые подходят под маску, старше POSTCALC_CACHE_VALID секунд 
    $arrCacheFiles=glob("$config_cache_dir/postcalc_*.txt");
    $Now=time();
    foreach ($arrCacheFiles as $fileObj) 
        if ( $Now-filemtime($fileObj) > $config_cache_valid ) unlink($fileObj);
    
    // Если существует файл кэша для данной строки запроса, просто зачитываем его
    if ( file_exists($CacheFile) ) {
        return  unserialize(file_get_contents($CacheFile)); 
    } else {
         // Формируем опции запроса. Это _необязательно_, однако упрощает контроль и отладку
        $arrOptions = array('http' =>
          array( 'header'  => 'Accept-Encoding: gzip',
                 'timeout' => $config_timeout, 
                 'user_agent' => 'PostcalcLight_1.05 '.phpversion() 
               )
        );
        $TS=microtime(1);
        // Опрашиваем в цикле сервера Postcalc.RU, пока не получаем ответ
        $ConnectOK=0;
        foreach ( $config_servers as $Server ) {
            // Запрос к серверу. Сохраняем ответ в переменной $Response. 
            // При ошибке соединения опрашиваем следующий сервер в цепочке.
            if ( !$Response=file_get_contents("http://$Server/?$QueryString", false , stream_context_create($arrOptions)) ) {
                  // === ОБРАБОТКА ОШИБОК СОЕДИНЕНИЯ                  
                  // Журнал ошибок соединения, поля разделены табуляцией: 
                  // метка времени, сервер, истекшее время с начала сессии (т.е. всех запросов), краткое сообщение об ошибке, полное сообщение об ошибке
                  if ( $config_error_log && count(error_get_last()) ) {
                        $ErrorLog = "$config_cache_dir/postcalc_error_".date('Y-m').'.log';
                        $arrError = error_get_last(); 
                        $PHPErrorMessage = $arrError['message'];
                        // Отрезаем конец сообщения PHP, где сообщается причина проблемы
                        $ErrMessage = substr( $PHPErrorMessage, strrpos( $PHPErrorMessage,':')+2 );
                        $fp_log=fopen($ErrorLog,'a');
                        fwrite($fp_log,date('Y-m-d H:i:s')."\t$Server\t".  number_format((microtime(1)-$TS),3)."\t$ErrMessage\t$PHPErrorMessage\n");
                        fclose($fp_log);
                        if ( $config_error_log_send > 0 ) {
                            $fp_log=fopen($ErrorLog,'r');
                            // Последовательно идем по логу и сохраняем в переменной $MailMessage фрагмент не более $config_error_log_send строк
                            $MailMessage = '';  $send_log=false;  $line_counter = 0;
                            while ( ($line = fgets($fp_log)) !== false) {
                                $line_counter++;
                                if ( $send_log ) {
                                    $MailMessage = '';
                                    $send_log=false;
                                }
                                $MailMessage .= $line;
                                // Если в $MailMessage оказалось ровно $config_error_log_send строк, сбрасываем счетчик строк и устанавливаем флаг $send_log.
                                // Если следующее чтение вернуло конец файла, цикл будет прерван и фрагмент лога отослан по почте.  
                                // Иначе фрагмент лога будет сброшен, как и флаг $send_log 
                                if ( $line_counter % $config_error_log_send === 0 ) {
                                    $line_counter = 0;
                                    $send_log=true;
                                }
                            }
                            fclose($fp_log);
                            if ( $send_log ) {
                                    $MailMessage="$_SERVER[SERVER_ADDR] [$_SERVER[SERVER_ADDR]]: ошибки соединения в скрипте $_SERVER[SCRIPT_FILENAME].\n"
                                            . "Подробности см. в http://$_SERVER[HTTP_HOST]".dirname($_SERVER['REQUEST_URI'])."/postcalc_light_stat.php\n"
                                            . "Последние строки ($config_error_log_send) из журнала ошибок:\n\n"
                                            . $MailMessage;
                                    mail($config_ml, 
                                         "$_SERVER[SERVER_ADDR] [$_SERVER[SERVER_ADDR]]: connection errors in postcalc_light_lib",
                                         $MailMessage,
                                         "Content-Transfer-Encoding: 8bit\nContent-Type: text/plain; charset=$config_cs\n");
                            }
                        }
                  }
                  // === КОНЕЦ ОБРАБОТКИ ОШИБОК СОЕДИНЕНИЯ
                  continue;
            }
                $ConnectOK=1;      
                break;
        }
        if ( !$ConnectOK )  return 'Не удалось соединиться ни с одним из следующих серверов postcalc.ru: '.implode(',',$config_servers).'. Проверьте соединение с Интернетом.';
        
        $ResponseSize = strlen($Response);
        // Если поток сжат, разархивируем его
        if ( substr($Response,0,3) == "\x1f\x8b\x08" ) $Response=gzinflate(substr($Response,10,-8));

           // Переводим ответ сервера в массив PHP
        if (!$arrResponse=unserialize($Response)) return "Получены странные данные. Ответ сервера:\n$Response";

        // Обработка возможной ошибки
        if ( $arrResponse['Status'] != 'OK' ) return "Сервер вернул ошибку: $arrResponse[Status]!";
               
        // Журнал успешных соединений, поля разделены табуляцией: 
        // метка времени, сервер, затраченное время, размер ответа, строка запроса
        if ( $config_log ) {
            $fp_log=fopen("$config_cache_dir/postcalc_light_".date('Y-m').'.log','a');
            fwrite($fp_log,date('Y-m-d H:i:s')."\t$Server\t".  number_format((microtime(1)-$TS),3)."\t$ResponseSize\t$QueryString\n");
            fclose($fp_log);
        }
        // Успешный ответ пишем в кэш
        file_put_contents($CacheFile,$Response);
        
    return $arrResponse;
    }
 
}

/**
 * Функция проверки правильности отправителя или получателя. Принимает либо 6-значный индекс,
 * либо местоположения EMS ('Москва', 'Сочи', 'Ленинградская область').
 * Возвращает 6-значный индекс ОПС, если не найдено - false. 
 *  
 * Если передан 6-значный индекс, проверка идет по текстовому файлу postcalc_light_post_indexes.txt, 
 * который содержит все почтовые индексы России в формате один индекс - одна строка.
 * Если передан текст, он проверяется по текстовому файлу postcalc_light_locations.txt.
 * 
 * <code>
 * $From='Ленинградская область';
 * 
 * $postIndex = postcalc_get_default_ops($From);
 * 
 * if ( !$postIndex ) echo "'$From' не является допустимым индексом, названием региона или центра региона!";
 * </code>
 * 
 * @param string $FromTo Проверяемое значение 
 * @return string  При ошибке возвращает false, иначе - шестизначный индекс ОПС.
 * 
 * @uses postcalc_arr_from_txt() Запрашивает массив, созданный из текстового файла.
 */
function postcalc_get_default_ops($FromTo)
{
    if (!$FromTo) return false;
    if ( preg_match('/^[1-9][0-9]{5}$/',$FromTo) ) {
        // Это 6-значный индекс. 
         $arr=postcalc_arr_from_txt('postcalc_light_post_indexes.txt', $FromTo, 1);
         return ( count($arr) ) ? $FromTo : false;
    } else {
        // Проверяем, не является ли это названием региона или его центра
         $arr=postcalc_arr_from_txt('postcalc_light_locations.txt', $FromTo, 1);
         return ( count($arr) ) ? current($arr) : false;
    }
}
/**
 * Функция генерирует массив PHP из текстового файла с данными.
 * 
 * Открывает файл $src_txt, в котором находятся данные в одном из двух форматов:
 * \n[единый ключ-значение] или \n[ключ]\t[значение]. Текстовые ключи дополнительно
 * обработаны функцией mb_convert_case с параметром MB_CASE_TITLE
 * Возвращает массив. Параметр search - совпадение с началом ключа, если пустая 
 * строка (по умолчанию) возвращает все строки. 
 * 
 * @param string $src_txt Файл с данными
 * @param string $search Совпадение с началом ключа. Если пустая строка, возвращает полную базу данных.
 * @param integer $limit Возвращать не более $limit элементов (для Autocomplete)
 * @return array Массив, если совпадений нет - пустой массив
 * 
 */
function postcalc_arr_from_txt($src_txt, $search='', $limit=0){
     global $arrPostcalcConfig;
     $arr=array();
     $StringDB = file_get_contents($src_txt);
     $StringDBLen = strlen($StringDB);
     $search=  mb_convert_case($search, MB_CASE_TITLE, $arrPostcalcConfig['cs']);
        $pos = strpos($StringDB,"\n$search");
         if ( $pos !== false ) {
            $counter = 1;
            $found = substr($StringDB, $pos+1, strpos($StringDB, "\n", $pos+2)-$pos-1);
            // Если внутри есть табуляция, то формат строки ключ\tзначение, 
            // иначе это единый ключ-значение
            $HasTab = ( strpos($found,"\t") === false ) ? false : true; 
            if ( $HasTab ) {
                list($key,$value)=explode("\t",$found);
                $arr[$key]=$value;
            } else {
                $arr[$found]=$found;
            }
            while ( $pos = strpos($StringDB,"\n$search",$pos+1)  ) {
                if ( $limit && $counter++>=$limit ) break;
                // Контролируем выход за пределы строки
                if ( $pos+2>$StringDBLen) break;
                $found=substr($StringDB, $pos+1,strpos($StringDB, "\n", $pos+2)-$pos-1);
                if ( $HasTab ) {
                    list($key,$value)=explode("\t",$found);
                    $arr[$key]=$value;
                } else {
                    $arr[$found]=$found;
                }
            }
        }
    return $arr;
}


/**
 * Вспомогательная функция, генерирует из массива содержимое списка <select> для веб-страницы.
 * 
 * Создает список стран, Россия в списке выделена:
 * <code>
 * postcalc_make_select(postcalc_arr_from_txt('postcalc_light_countries.txt'),'Ru');
 * </code>
 * 
 * @ignore
 * @param array $arrList Ключи массива становятся value в тэге <option>, значения массива становятся видимыми элементами списка.
 * @param string $defaultValue Это значение будет выделено (атрибут selected).
 * @return string Готовый список для вставки на веб-странице между тэгами <select> и </select>.
 */
function postcalc_make_select($arrList,$defaultValue)
{
$Out='';
   foreach ($arrList as $value=>$label) {
        $Out.= "<option value='$value'";
        $Out.= ($value==$defaultValue) ? ' selected' : ''; 
        $Out.= ">$label</option>\n";
    }
return $Out;
}
/**
 * Автодополнение для полей "Откуда" и "Куда" на веб-странице. Работает с виджетом jQuery Autocomplete.
 * 
 * Внимание! Входные данные ожидаются всегда в кодировке UTF-8. 
 * jQuery Autocomplete эту кодировку обеспечивает автоматически, в остальных случаях можно применять
 * функцию javascript encodeURIComponent().
 * 
 * Возвращает массив JSON для непосредственного использования в виджете jQuery Autocomplete в кодировке UTF-8.
 * 
 *
 * @param string $post_index Начало почтового индекса или местоположения
 * @param integer $limit Максимальное число элементов в списке
 * @return mixed Объект JSON для непосредственного использования в виджете jQuery Autocomplete
 * 
 * @uses postcalc_arr_from_txt() Запрашивает функцию postcalc_arr_from_txt() для получения массива, сгенерированного из текстового файла.
 */
function postcalc_autocomplete($post_index,$limit=10)
{
    global $arrPostcalcConfig;
    $Charset=$arrPostcalcConfig['cs'];
    $arr=array();
    if ( preg_match("/\d+/",$post_index) ) {
        $arr_indexes=postcalc_arr_from_txt('postcalc_light_post_indexes.txt', $post_index, $limit);
        foreach ($arr_indexes as $ops) $arr[]=array('label'=>$ops,'value'=>$ops);
      } else {
        // Все данные с веб-страницы поступают в UTF-8
        $post_index=mb_convert_case($post_index, MB_CASE_TITLE, 'UTF-8');
        // Преобразуем в текущую кодировку библиотеки
        $post_index=mb_convert_encoding($post_index,$Charset, 'UTF-8' );
        $arr_locations=postcalc_arr_from_txt('postcalc_light_locations.txt', $post_index, $limit);
        foreach ($arr_locations as $location=>$default_ops) {
             $location=  str_replace(
                    array('Республика','Область','Автономный Округ','Край','-На-'), 
                    array('республика','область','автономный округ','край','-на-'),
                    $location
                    );
             $arr[]=array(
                    'label' =>  mb_convert_encoding($location,'UTF-8',$Charset),
                    'value' =>  mb_convert_encoding($location,'UTF-8',$Charset)
             );
        }
    }
return json_encode($arr);
}

/**
 * Функция генерирует из журналов соединений массив PHP. Используется в postcalc_light_stat.php. 
 * Возвращаемый массив может быть использован и для самостоятельного анализа.
 * 
 * Открывает в цикле все файлы, которые расположены в cache_dir и имеют название вида postcalc_light_YYYY-MM.log,
 * возвращает массив, где данные сгруппированы по дням: ключ массива - дата в формате YYYY-MM-DD, 
 * значения: число обращений за сутки num_requests, среднее время запроса time_elapsed, средний размер ответа size.
 *  
 * @global array $arrPostcalcConfig
 * @return array
 */
function postcalc_get_stat_arr() {
   global $arrPostcalcConfig;
   $postcalc_config_cache_dir =  $arrPostcalcConfig['cache_dir'];
   $arrStat=array();
foreach (glob("$postcalc_config_cache_dir/postcalc_light_*.log") as $logfile ) {
    $fp_log=fopen($logfile,'r');
    while ( $logline = fgets($fp_log)) {
        list($date_time,$server,$time_elapsed,$size,$query_string)=explode("\t",$logline);
        $date = substr($date_time, 0, 10);
        if ( isset($arrStat[$date]) ) {
            $arrStat[$date]['time_elapsed'] += $time_elapsed;
            $arrStat[$date]['size'] += $size;
            $arrStat[$date]['num_requests']++;
        } else {
            $arrStat[$date]['time_elapsed'] = $time_elapsed;
            $arrStat[$date]['size'] = $size;
            $arrStat[$date]['num_requests'] = 1;
            $arrStat[$date]['errors'] = 0;
        }
    }
    fclose($fp_log);
}
// Дополняем статистикой по ошибкам
foreach (glob("$postcalc_config_cache_dir/postcalc_error_*.log") as $logfile ) {
    $fp_log=fopen($logfile,'r');
    while ( $logline = fgets($fp_log)) {
        list($date_time,$server,$time_elapsed,$error_short,$error_full)=explode("\t",$logline);
        $date = substr($date_time, 0, 10);
        if ( isset($arrStat[$date]['errors']) ) {
            $arrStat[$date]['errors'] += 1;
        } else {
            $arrStat[$date]['errors'] = 1;
        }
    }
    fclose($fp_log);
}

// Теперь проходимся по всему массиву и вычисляем среднее арифметическое 
// для size (округляем до целого) и time_elapsed (оставляем 3 знака после запятой).
foreach ( $arrStat as $date => $arr_values ) {
    if ( isset($arrStat[$date]['time_elapsed']) )
        $arrStat[$date]['time_elapsed'] = number_format(($arrStat[$date]['time_elapsed']/$arrStat[$date]['num_requests']),3,'.','');
    if ( isset($arrStat[$date]['size']) )
        $arrStat[$date]['size'] = round($arrStat[$date]['size']/$arrStat[$date]['num_requests'], 0);
}

return $arrStat;
}
