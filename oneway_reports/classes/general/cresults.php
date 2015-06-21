<?
IncludeModuleLangFile(__FILE__);


CModule::IncludeModule('iblock');

//get additional data with caching
if (!CModule::IncludeModule("highloadblock"))
    {
        throw new Exception('not installed highloadblock');
    }

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;





class CResults
{
	var $LAST_ERROR="";

	//Get list
	function GetList($aSort=array(), $aFilter=array())
	{
		global $DB;

		$arFilter = array(
                    'IBLOCK_ID' => MY_IBLOCK_RESULT
                );
                
                
                /*
                 * some preparered actions with filter
                 */
                
                //выполняем подзапрос к инфоблоку участников
                $participantFilter = array();
                $searchInParticipant = false;
                foreach($aFilter as $k => $v){
                    
                    if($v === null){
                        unset($aFilter[$k]);
                        continue;
                    }
                    
                    $matches = array();
                    if(preg_match('/^PROPERTY_PARTICIPANT\.(PROPERTY_[A-Z_]+)$/', $k, $matches)){
                        unset($aFilter[$k]);
                        $participantFilter['%'.$matches[1]] = $v;
                        $searchInParticipant = true;
                        continue;
                    }
                }
                
                
                //дата - особый случай
                
                if($_REQUEST['date_from_BIRTHDAY_FILTER_PERIOD'] === 'interval' && isset($_REQUEST['date_from_BIRTHDAY']) && isset($_REQUEST['date_to_BIRTHDAY'])){
                    $searchInParticipant = true;
                    $participantFilter[] = array(
                        'LOGIC' => 'AND',
                        '>=PROPERTY_BIRTHDAY' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_from_BIRTHDAY']))),
                        '<=PROPERTY_BIRTHDAY' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_to_BIRTHDAY'])))
                    );
                }

                //дата - особый случай
                
               /* if($_REQUEST['date_from_DATE_CREATE_FILTER_PERIOD'] === 'interval' && isset($_REQUEST['date_from_DATE_CREATE']) && isset($_REQUEST['date_to_DATE_CREATE'])){
                    $searchInParticipant = true;
                    $participantFilter[] = array(
                        'LOGIC' => 'AND',
                        '>=DATE_CREATE' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_from_DATE_CREATE']))),
                        '<=DATE_CREATE' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_to_DATE_CREATE'])))
                    );
                }

                //дата - особый случай
                
                if($_REQUEST['date_from_TIMESTAMP_X_FILTER_PERIOD'] === 'interval' && isset($_REQUEST['date_from_TIMESTAMP_X']) && isset($_REQUEST['date_to_TIMESTAMP_X'])){
                    $searchInParticipant = true;
                    $participantFilter[] = array(
                        'LOGIC' => 'AND',
                        'DATE_MODIFY_FROM' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_from_TIMESTAMP_X']))),
                        'DATE_MODIFY_TO' => date('Y-m-d', MakeTimeStamp(htmlspecialchars($_REQUEST['date_to_TIMESTAMP_X'])))
                    );
                }*/
                
                
                
                
                
                $participantIds = array();
                if(!empty($participantFilter)){
                    $resParticipant = CIBlockElement::GetList(array(), array_merge(array('IBLOCK_ID' => MY_IBLOCK_PARTICIPANTS), $participantFilter), false, false, array('ID'));
                    while($resAr = $resParticipant->GetNext()){
                        $participantIds[] = $resAr['ID'];
                    }
                }
                
                if(!empty($participantIds)){
                    
                    if(!isset($aFilter['PROPERTY_PARTICIPANT'])){
                        $aFilter['PROPERTY_PARTICIPANT'] = $participantIds;
                    }
                    
                }
                
                if($searchInParticipant === true && empty($participantIds)){
                    $aFilter['PROPERTY_PARTICIPANT'] = -1;
                }
               
                
                foreach($aFilter as $k => $v){
                    
                    if($v === null){
                        unset($aFilter[$k]);
                        continue;
                    }
                    
                    if($k === 'PROPERTY_PAY_STATUS' && intval($v) !== PAY_STATUS_PROPERTY_ENUM_ID_PAYED){
                        unset($aFilter[$k]);
                        $aFilter['!PROPERTY_PAY_STATUS'] = PAY_STATUS_PROPERTY_ENUM_ID_PAYED;
                        continue;
                    }

                    if ($k === 'PROPERTY_PROMOCODE') {

                        $ress = CIBlockElement::GetList(Array(), array("IBLOCK_ID"=>MY_IBLOCK_PROMOCODES, "NAME"=>trim($v)), false, Array(), array("ID"));
                        $id_promocode = '';
                        while($ob = $ress->GetNext()){ 
                            $id_promocode = $ob['ID'];
                            $aFilter['PROPERTY_PROMOCODE'] = $ob['ID'];
                        }

                        if (!empty($id_promocode)) $aFilter['PROPERTY_PROMOCODE'] = $id_promocode;
                    }                    
                    
                    if(
                            $k === 'PROPERTY_CITY' || 
                            $k === 'PROPERTY_COUNTRY' || 
                            $k === 'PROPERTY_SPORT_CLUB' || 
                            $k === 'PROPERTY_LEARNED'){
                        unset($aFilter[$k]);
                        $aFilter['%'.$k] = $v;
                        continue;
                    }
                    
                    
                }
		$arFilter = array_merge($aFilter);
                
                $fieldsResult = $this->GetFieldsResults();
                $arSelect = array('ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'DATE_CREATE', 'TIMESTAMP_X');
                foreach ($fieldsResult as $field) {
                    $arSelect[] = 'PROPERTY_' . $field['CODE'];
                }
                
                $fieldsParticipant = $this->GetFieldsParticipant();
                foreach ($fieldsParticipant as $field) {
                    $arSelect[] = 'PROPERTY_PARTICIPANT.PROPERTY_' . $field['CODE'];
                }
                
                foreach ($aSort as $key => $val){
                    $by = $key;
                    $order = $val;
                    break;
                }
                if(!in_array($by, array('ID', 'NAME', 'CODE'))){
                    $by = 'property_' . $by;
                }
                $aSort = array($by => $order);
  
                return CIBLockElement::GetList(array('ID' => 'ASC'), $arFilter, false, false, $arSelect);
	}
        
        
        
        function GetSeasons(){
            $result = array();
            $res = CIBlockSection::GetList(array(), array('IBLOCK_ID' => MY_IBLOCK_RESULT), false, array('ID', 'NAME'));
            while($arRes = $res->GetNext()){
                $result[$arRes['ID']] = $arRes['NAME'];
            }
            
            return $result;
        }
        
        
        function GetFields(){
            return array_merge($this->GetFieldsResults(), $this->GetFieldsParticipant());
        }
        
        
        
        function getYears(){
            $db = CIBlockSection::GetList(array('NAME' => 'asc'), array('IBLOCK_ID' => MY_IBLOCK_RESULT), false, array('ID', 'NAME'));
            $res = array();
            
            while($resar = $db->Fetch()){
                $res[] = array(
                    'ID' => $resar['ID'],
                    'VALUE' => $resar['NAME']
                );
            }
            
            return $res;
        }
        
        
        
        function GetFieldsResults(){
            
            /*
             * get props from results
             */
            static $resr = null;
            
            if($resr !== null){
                return $resr;
            }
            
            
            $resr = array();
            $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("ACTIVE" => "Y", "IBLOCK_ID" => MY_IBLOCK_RESULT));
            while ($prop_fields = $properties->GetNext())
            {
               
                //add enum to list props
                if($prop_fields['PROPERTY_TYPE'] === 'L'){
                    
                    $db_enum_list = CIBlockProperty::GetPropertyEnum($prop_fields['ID'], Array(), Array("IBLOCK_ID" => MY_IBLOCK_RESULT));
                    $prop_fields['ENUM'] = array();
                    while($ar_enum_list = $db_enum_list->GetNext())
                    {
                            $prop_fields['ENUM'][$ar_enum_list['ID']] = $ar_enum_list;                 
                    }
                    
                }
                
                $resr[$prop_fields['ID']] = $prop_fields;
                
            }
            
            return $resr;
            
        }
        
        
        /*
         * get distance array from highload blocks
         */
        function GetDistance(){
            
             /*
             * get distance
             */
            static $distance = null;
            
            if($distance !== null){
                return $distance;
            }
            
            
            $distance = array();
            
            $hlblock = HL\HighloadBlockTable::getById(MY_HIGHLOAD_IBLOCK_DISTANCE)->fetch();

            if (empty($hlblock))
            {
               throw new Exception('not founde highloadblock with id = ' . MY_HIGHLOAD_IBLOCK_DISTANCE);
            }

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();


            $rsData = $entity_data_class::getList(array(
               "select" => array("*"),
               "order" => array("ID" => "ASC")
            ));

            while($arData = $rsData->Fetch())
            {
                $distance[$arData['UF_XML_ID']] = $arData;
            } 
            
            
            return $distance;
            
        }
        
        
        
        function GetFieldsParticipant(){
            
            /*
             * get props from results
             */
            static $resp = null;
            
            if($resp !== null){
                return $resp;
            }
            
            
            $resp = array();
            
            /*
             * get props from participants
             */
            $properties = CIBlockProperty::GetList(Array("sort" => "asc", "name" => "asc"), Array("ACTIVE" => "Y", "IBLOCK_ID" => MY_IBLOCK_PARTICIPANTS));
            while ($prop_fields = $properties->GetNext())
            {
               
                //add enum to list props
                if($prop_fields['PROPERTY_TYPE'] === 'L'){
                    
                    $db_enum_list = CIBlockProperty::GetPropertyEnum($prop_fields['ID'], Array(), Array("IBLOCK_ID" => MY_IBLOCK_PARTICIPANTS));
                    $prop_fields['ENUM'] = array();
                    while($ar_enum_list = $db_enum_list->GetNext())
                    {
                            $prop_fields['ENUM'][$ar_enum_list['ID']] = $ar_enum_list;                 
                    }
                    
                }
                
                $resp[$prop_fields['ID']] = $prop_fields;
                
            }
            
            
            return $resp;
            
        }
        
        
        
        
        
        
}