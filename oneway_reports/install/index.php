<?
IncludeModuleLangFile(__FILE__);

if(class_exists("oneway_reports")) return;
class oneway_reports extends CModule
{
	var $MODULE_ID = "oneway_reports";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	var $errors;

	function oneway_reports()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = SUBSCRIBE_VERSION;
			$this->MODULE_VERSION_DATE = SUBSCRIBE_VERSION_DATE;
		}

		$this->MODULE_NAME = "Отчеты по результатам забегов";
		$this->MODULE_DESCRIPTION = "Отчеты по результатам забегов";
		//$this->MODULE_CSS = "/bitrix/modules/subscribe/styles.css";
	}

	function InstallDB()
	{
		global $DB, $DBType, $APPLICATION;
                RegisterModule("oneway_reports");
                return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;
		UnRegisterModule("oneway_reports");
		return true;
	}

	function InstallFiles($arParams = array())
	{
                if(!file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/oneway_reports.php')){
                    copy($_SERVER['DOCUMENT_ROOT'] . '/local/modules/oneway_reports/install/admin/oneway_reports.php', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/oneway_reports.php');
                }
                return true;
                
	}

	function UnInstallFiles()
	{
                unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/oneway_reports.php');
                return true;
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$this->InstallDB();
                $this->InstallFiles();
				
			
		
	}

	function DoUninstall()
	{
                global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
                $this->UnInstallFiles();
                $this->UnInstallDB();
                
		
	}


}
