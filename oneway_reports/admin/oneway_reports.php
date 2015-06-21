<?
// подключим все необходимые файлы:
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог

require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/oneway_reports/include.php"); // инициализация модуля
require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/oneway_reports/prolog.php"); // пролог модуля

// подключим языковой файл
IncludeModuleLangFile(__FILE__);

// получим права доступа текущего пользователя на модуль
$POST_RIGHT = $APPLICATION->GetGroupRight("oneway_reports");
// если нет прав - отправим к форме авторизации с сообщением об ошибке
if ($POST_RIGHT == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
?>
<?
// здесь будет вся серверная обработка и подготовка данных >>

$sTableID = "tbl_results"; // ID таблицы
//$oSort = new CAdminSorting($sTableID, "ID", "asc"); // объект сортировки
$lAdmin = new CAdminList($sTableID, $oSort); // основной объект списка



// проверку значений фильтра для удобства вынесем в отдельную функцию
function CheckFilter()
{
    global $FilterArr, $lAdmin;
    foreach ($FilterArr as $f) global $$f;
    
    /* 
       здесь проверяем значения переменных $find_имя и, в случае возникновения ошибки, 
       вызываем $lAdmin->AddFilterError("текст_ошибки"). 
    */
    
    return count($lAdmin->arFilterErrors) == 0; // если ошибки есть, вернем false;
}



// выберем список результатов
$cData = new CResults;

$fieldsResult = $cData->GetFieldsResults();
$fieldsParticipant = $cData->GetFieldsParticipant();
$fields = $cData->GetFields();


// опишем элементы фильтра
$FilterArr = Array(
    "find_ID",
    "find_SECTION_ID"/*,
    "date_from_DATE_CREATE",
    "date_from_TIMESTAMP_X",
    "date_to_DATE_CREATE",
    "date_to_TIMESTAMP_X"*/
    );

    foreach ($fields as $field){
        if($field['CODE'] === 'PARTICIPANT'){
             continue;
        }
        
        $FilterArr[] = 'find_' . $field['CODE'];
    }

// инициализируем фильтр
$lAdmin->InitFilter($FilterArr);

// если все значения фильтра корректны, обработаем его
if (CheckFilter())
{
    // создадим массив фильтрации для выборки
    $arFilter = Array(
        "ID"		=> $find_ID,
        'SECTION_ID' => $find_SECTION_ID/*,
        "DATE_CREATE" => $find_DATE_CREATE,
        "TIMESTAMP_X" => $find_TIMESTAMP_X*/
    );
    
    
    foreach ($fieldsResult as $field) {
        $arFilter['PROPERTY_' . $field['CODE']] = ${'find_' . $field['CODE']};
    }
    
    foreach ($fieldsParticipant as $field) {
        $arFilter['PROPERTY_PARTICIPANT.PROPERTY_' . $field['CODE']] = ${'find_' . $field['CODE']};
    }
    
}



//get data

if($_REQUEST['del_filter'] === 'Y'){
    $arFilter = array();
}



$rsData = $cData->GetList(array($by=>$order), $arFilter);

// преобразуем список в экземпляр класса CAdminResult
$rsData = new CAdminResult($rsData, $sTableID);

// аналогично CDBResult инициализируем постраничную навигацию.
$rsData->NavStart();

// отправим вывод переключателя страниц в основной объект $lAdmin
$lAdmin->NavText($rsData->GetNavPrint("Выбрано"));

$headers = array(
    array(
        'id' => 'ID',
        'content' => 'ID',
        'sort' => 'ID',
        'default' => true
    ),
    array(
        'id' => 'IBLOCK_SECTION_ID',
        'content' => 'Год(сезон)',
        'sort' => 'IBLOCK_SECTION_ID',
        'default' => true
    ),
     array(
        'id' => 'DATE_CREATE',
        'content' => 'Дата создания',
        'sort' => 'DATE_CREATE',
        'default' => true
    ),
     array(
        'id' => 'TIMESTAMP_X',
        'content' => 'Дата изменения',
        'sort' => 'TIMESTAMP_X',
        'default' => true
    )
);

foreach ($fields as $field) {
    $headers[] = array(
        'id' => $field['CODE'],
        'content' => $field['NAME'],
        'sort' => $field['CODE'],
        'default' => true
    );
}

$lAdmin->AddHeaders($headers);

$distance = $cData->GetDistance();

$seasons = $cData->GetSeasons();


//@todo add here resorting elements couse sorting in db is incorrect
while($arRes = $rsData->GetNext()):
    
  // создаем строку. результат - экземпляр класса CAdminListRow
  $row =& $lAdmin->AddRow($f_ID, $arRes); 

  // далее настроим отображение значений при просмотре и редаткировании списка из инфоблока результатов
  foreach($fieldsResult as $field){
      
        if($field['PROPERTY_TYPE'] === 'S' && $field['CODE']!== 'DISTANCE'){

            if($field['USER_TYPE'] === null){
                  $row->AddInputField($field['CODE'], array("size"=>20));
                  $row->AddViewField($field['CODE'], $arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]);
            }
            elseif($field['USER_TYPE'] === 'directory'){
                
                $t = unserialize(htmlspecialchars_decode($arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]));
                $res = '';
                $c = count($t['VALUE']);
                $i = 0;
                foreach ($t['VALUE'] as $value) {
                    $res .= $distance[$value]['UF_NAME'];
                    $i++;
                    if($i !== $c){
                        $res .= '&nbsp;/&nbsp;';
                    }
                }
               
                $row->AddInputField($field['CODE'], array("size"=>20));
                $row->AddViewField($field['CODE'], $res);
            }
            else{
                
            }

        }

        if($field['USER_TYPE'] === 'directory'){
            $t = unserialize(htmlspecialchars_decode($arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]));
            
            foreach ($distance as $key => $enumArr) {
                $reference[$enumArr['UF_XML_ID']] = $enumArr['UF_NAME'];
            }

            $r = CIBlockElement::GetList(Array("SORT"=>"ASC"), Array("ID"=>$arRes['ID'], "IBLOCK_SECTION_ID"=>$arRes['IBLOCK_SECTION_ID'], "IBLOCK"=>MY_IBLOCK_RESULT),false,false,Array("ID", "PROPERTY_DISTANCE"));
            $distance_arr = array();
            while ($ob = $r->GetNext()) {
                /*?><pre><?print_r($ob)?></pre><?*/
                $distance_arr = $ob['PROPERTY_DISTANCE_VALUE'];
            }
            $dist_elem = '';
            foreach ($distance_arr as $k => $v) {
                if ($k > 0) $dist_elem .= ' / ';
                $dist_elem .= $reference[$v];
            }


            $row->AddInputField($field['CODE'], array("size"=>20));
            $row->AddViewField($field['CODE'], $dist_elem);
            
        }

        
        
        if($field['PROPERTY_TYPE'] === 'L'){
            $row->AddInputField($field['CODE'], array("size"=>20));
            $row->AddViewField($field['CODE'], $arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]);
        }
     
        if($field['PROPERTY_TYPE'] === 'E'){
            $row->AddInputField($field['CODE'], array("size"=>20));
            $url = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$field['LINK_IBLOCK_ID'].'&type=participants&ID='.$arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ];
            $arr_elem = CIBlockElement::GetByID($arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]);
            $name_elem = '';

            if($ar_res = $arr_elem->GetNext()) $name_elem = $ar_res['NAME'];

            if (!empty($name_elem)) {
                $row->AddViewField($field['CODE'], $name_elem. ' [<a target = "_blank" href="'.$url.'">'.$arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ].'</a>]');
            }   else {
                $row->AddViewField($field['CODE'], '');
            }
          
        }
         
  }  
  
  // далее настроим отображение значений при просмотре и редаткировании списка из инфоблока участников
  foreach($fieldsParticipant as $field){
         if($field['PROPERTY_TYPE'] === 'S'){

            if($field['USER_TYPE'] === null){
                $row->AddInputField($field['CODE'], array("size"=>20));
                $row->AddViewField($field['CODE'], $arRes['PROPERTY_PARTICIPANT_PROPERTY_' . $field['CODE'] . '_VALUE' ]);
            }
            elseif($field['USER_TYPE'] === 'UserID'){
                $row->AddInputField($field['CODE'], array("size"=>20));
                $url = '/bitrix/admin/user_edit.php?ID=' . $arRes['PROPERTY_PARTICIPANT_PROPERTY_' . $field['CODE'] . '_VALUE' ];
                $row->AddViewField($field['CODE'], '<a target = "_blank" href="'.$url.'">'.$arRes['PROPERTY_PARTICIPANT_PROPERTY_' . $field['CODE'] . '_VALUE' ].'</a>');
            }
            elseif($field['USER_TYPE'] === 'DateTime'){
                $row->AddInputField($field['CODE'], array("size"=>20));
                $res = $arRes['PROPERTY_PARTICIPANT_PROPERTY_' . $field['CODE'] . '_VALUE' ];
               // $res = ConvertDateTime($res, 'DD.MM.YYYY', 's1');
               // $res = date('j.n.Y', MakeTimeStamp($res));
               // test_dump($res);
                $row->AddViewField($field['CODE'], $res);
            }
            else{
                ;
            }

        }
        
        if($field['PROPERTY_TYPE'] === 'L'){
            $row->AddInputField($field['CODE'], array("size"=>20));
            $row->AddViewField($field['CODE'], $arRes['PROPERTY_PARTICIPANT_PROPERTY_' . $field['CODE'] . '_VALUE' ]);
        }
    
        
        
      
         
  }
  
  
  $row->AddInputField("IBLOCK_SECTION_ID", array("size"=>20));
  $row->AddViewField("IBLOCK_SECTION_ID", $seasons[$arRes['IBLOCK_SECTION_ID']]);
  
  // сформируем контекстное меню
  $arActions = Array();

endwhile;





// резюме таблицы
$lAdmin->AddFooter(
  array(
    array("title"=>"MAIN_ADMIN_LIST_SELECTED", "value"=>$rsData->SelectedRowsCount()), // кол-во элементов
    array("counter"=>true, "title"=>"MAIN_ADMIN_LIST_CHECKED", "value"=>"0"), // счетчик выбранных элементов
  )
);

// сформируем меню из одного пункта - добавление рассылки
$aContext = array(
 
);

// и прикрепим его к списку
$lAdmin->AddAdminContextMenu($aContext);



// альтернативный вывод
$lAdmin->CheckListMode();

// установим заголовок страницы
$APPLICATION->SetTitle("Результаты забегов");


require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог


$filterNames = array('ID', 'Год'/*, 'Дата создания', 'Дата изменения'*/);
foreach ($fields as $field){
    if($field['CODE'] === 'PARTICIPANT'){
            continue;
       }
    $filterNames[] = $field['NAME'];
}

// создадим объект фильтра
$oFilter = new CAdminFilter(
  $sTableID."_filter",
  $filterNames
);
?>
<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
<?$oFilter->Begin();?>
<tr>
  <td><?="ID"?>:</td>
  <td>
    <input type="text" name="find_ID" size="47" value="<?echo htmlspecialchars($find_id)?>">
  </td>
</tr>


<tr>
  <td><?="Год"?>:</td>
  <td>
        <? 
        $years = $cData->getYears(); 
      
        $reference = array();
        $reference_id = array();
        foreach ($years as $enumArr) {
            $reference[] = $enumArr['VALUE'];
            $reference_id[] = $enumArr['ID'];
        }

        $arrList = array(
            "reference" => $reference, //array("POST_YES", "POST_NO"),
            "reference_id" => $reference_id, //array("Y","N",)
        );
      
      echo SelectBoxFromArray("find_SECTION_ID", $arrList, $find_SECTION_ID, "", "");
      ?>
  </td>
</tr>

<?/*
<tr>
  <td><?="Дата создания"?>:</td>
  <td>
    <?echo CalendarPeriod("date_from_DATE_CREATE", "03.03.2003", "date_to_DATE_CREATE", "03.03.2018", "find_form", "Y");?>
  </td>
</tr>

<tr>
  <td><?="Дата изменения"?>:</td>
  <td>
    <?echo CalendarPeriod("date_from_TIMESTAMP_X", "03.03.2003", "date_to_TIMESTAMP_X", "03.03.2018", "find_form", "Y");?>
  </td>
</tr>
*/?>
<?


foreach ($fieldsResult as $field){
  
        if($field['CODE'] === 'PARTICIPANT'){
             continue;
        }
    ?>
    <tr>
      <td><?=$field['NAME']?>:</td>
      <td>
          <?
                if($field['PROPERTY_TYPE'] === 'S'){
                    
                    if($field['USER_TYPE'] === null){
                        ?><input type="text" name="find_<?=$field['CODE']?>" size="47" value="<?echo htmlspecialchars(${'find_' . $field['CODE']})?>"><?
                    }
                    elseif($field['USER_TYPE'] === 'directory'){
                        $t = unserialize(htmlspecialchars_decode($arRes['PROPERTY_' . $field['CODE'] . '_VALUE' ]));
                        
                        $reference = array();
                        $reference_id = array();
                        foreach ($distance as $key => $enumArr) {
                            $reference[] = $enumArr['UF_NAME'];
                            $reference_id[] = $key;
                        }

                        $arrList = array(
                            "reference" => $reference, 
                            "reference_id" => $reference_id, 
                        );

                        echo SelectBoxFromArray("find_DISTANCE", $arrList, $find_DISTANCE, "", "");
                        
                    }
                    else{
                        ;
                    }

                }

                if($field['PROPERTY_TYPE'] === 'L'){
                  
                    $reference = array();
                    $reference_id = array();
                    foreach ($field['ENUM'] as $enumArr) {
                        $reference[] = $enumArr['VALUE'];
                        $reference_id[] = $enumArr['ID'];
                    }
                    
                    $arrList = array(
                        "reference" => $reference, //array("POST_YES", "POST_NO"),
                        "reference_id" => $reference_id, //array("Y","N",)
                      );
                      
                    echo SelectBoxFromArray("find_".$field['CODE'], $arrList, ${'find_' . $field['CODE']}, "", "");
                }

                if($field['PROPERTY_TYPE'] === 'E'){
                    ?>
                    <input type="text" name="find_<?=$field['CODE']?>" size="47" value="<?echo htmlspecialchars(${'find_' . $field['CODE']})?>"><?
                }

          ?>
          
      </td>
    </tr>
    
<?}


foreach($fieldsParticipant as $field){?>
    <tr>
      <td><?=$field['NAME']?>:</td>
      <td>
          <?
                   
                if($field['PROPERTY_TYPE'] === 'S'){

                    if($field['USER_TYPE'] === null){
                        ?><input type="text" name="find_<?=$field['CODE']?>" size="47" value="<?echo htmlspecialchars(${'find_' . $field['CODE']})?>"><?
                    }
                    elseif($field['USER_TYPE'] === 'UserID'){
                        ?><input type="text" name="find_<?=$field['CODE']?>" size="47" value="<?echo htmlspecialchars(${'find_' . $field['CODE']})?>"><?
                    }
                    elseif($field['USER_TYPE'] === 'DateTime'){
                        echo CalendarPeriod("date_from_".$field['CODE'], "25.10.2003", "date_to_".$field['CODE'], "29.10.2018", "find_form", "Y");
                    }
                    else{
                        ;
                    }

                }

                if($field['PROPERTY_TYPE'] === 'L'){
                    
                    $reference = array();
                    $reference_id = array();
                    foreach ($field['ENUM'] as $enumArr) {
                        $reference[] = $enumArr['VALUE'];
                        $reference_id[] = $enumArr['ID'];
                    }
                    
                    $arrList = array(
                        "reference" => $reference, //array("POST_YES", "POST_NO"),
                        "reference_id" => $reference_id, //array("Y","N",)
                      );
                      
                    echo SelectBoxFromArray("find_".$field['CODE'], $arrList, ${'find_' . $field['CODE']}, "", "");
                }?>
      </td>
    </tr>
<?}





$oFilter->Buttons(array("table_id"=>$sTableID,"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
$oFilter->End();
?>
</form>


<?

// выведем таблицу списка элементов
$lAdmin->DisplayList();

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>