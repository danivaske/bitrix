<?
IncludeModuleLangFile(__FILE__); // в menu.php точно так же можно использовать языковые файлы

if($APPLICATION->GetGroupRight("oneway_reports")>"D") // проверка уровня доступа к модулю
{
  // сформируем верхний пункт меню
  $aMenu = array(
    "parent_menu" => "global_menu_services", // поместим в раздел "Сервис"
    "sort"        => 100,                    // вес пункта меню
    "url"         => "/bitrix/admin/oneway_reports.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
    "text"        => "Отчет по результатам забегов",       // текст пункта меню
    "title"        => "Отчет по результатам забегов",       // текст пункта меню
    "icon"        => "form_menu_icon", // малая иконка
    "page_icon"   => "form_page_icon", // большая иконка
    "items_id"    => "menu_webforms",  // идентификатор ветви
    "items"       => array(),          // остальные уровни меню сформируем ниже.
  );

  // далее выберем список веб-форм и добавим для каждой соответствующий пункт меню
  //require_once($_SERVER["DOCUMENT_ROOT"]."/local/modules/oneway_reports/include.php");
  /*
  $aMenu["items"][] =  array(
        "text" => 'Результаты',
        "url"  => "form_result_list.php?lang=".LANGUAGE_ID."&WEB_FORM_ID=".$zr["ID"],
        "icon" => "form_menu_icon",
        "page_icon" => "form_page_icon",
        "more_url"  => array(
            "form_view.php?WEB_FORM_ID=".$zr["ID"],
            "form_result_list.php?WEB_FORM_ID=".$zr["ID"],
            "form_result_edit.php?WEB_FORM_ID=".$zr["ID"],
            "form_result_print.php?WEB_FORM_ID=".$zr["ID"],
            "form_result_view.php?WEB_FORM_ID=".$zr["ID"]
            ),
        "title" => "Результаты"
       );*/
  // вернем полученный список
  return $aMenu;
}
// если нет доступа, вернем false
return false;
?>