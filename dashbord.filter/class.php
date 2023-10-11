<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Entity\Query;

use Bitrix\Main\Application;
use Bitrix\Main\Diag\Debug;


Loader::includeModule('grossnet.dashboard');

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CEventsTypeEdit extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable {
    protected $errors = array();
    /** @var ErrorCollection */
    protected $errorCollection;

    public function configureActions()
    {
        return [
            'getEditListData' => [
                '-prefilters' => [
                    '\Bitrix\Main\Engine\ActionFilter\Authentication' 
                ],
            ],
        ];
    }

    public function onPrepareComponentParams($arParams) {
        $this->errorCollection = new ErrorCollection();
        return $arParams;
    }

    protected function listKeysSignedParameters()
    {			
        return [
            'filter_id',
            'project_id',
        ];
    }

    public function executeComponent()
    {
        
        if (!empty($_POST)) {
    

        } else {
            if($this->startResultCache())
            {

                $project = Grossnet\Dashboard\ProjectsDataTable::getByPrimary($this->arParams['project_id'], array(
                    'select' => array('*','events_type')
                ))->fetchObject();

                foreach ($project->getEventsType() as $event_type) {
                    $event_type_arr[] = $event_type->getId();
                }

                
                $selectOption = array(
                    'select' => array('*', 'event_type', 'VALUES', 'OBJECTS'),
                    'filter' => array('is_delete' => "N", 'event_type.id' => $event_type_arr)
                );

              

                $params = Grossnet\Dashboard\ParamsTypesEventsDataTable::getList($selectOption)->fetchCollection();
                $params_id = array();
                $objects_id = array();
                $objects_id_unic = array();
                foreach ($params as $param) {
                    $params_id[] = $param->getId();
                    if ($param->getObjectType() != 0) {
                        $objects_id[] = $param->getObjectType();
                    }
                }
                $objects_id_unic = array_unique($objects_id);
                $selectOptionObj = array(
                    'select' => array('*'),
                    'filter' => array('is_delete'=> 'N', 'fk_type' => $objects_id_unic)
                );
                $objects_values = Grossnet\Dashboard\ValueObjectDataTable::getList($selectOptionObj)->fetchCollection();

                // Вынес во внешнюю функцию, т.к. нужно использовать в отчетах 
                $filterParams = Grossnet\Dashboard\Filters::getFilterParams($this->arParams['filter_id'], $params_id);
                $this->arResult = array_merge($this->arResult, $filterParams['arResult']);
                $condition_array = $filterParams['condition_array'];
                $table_array = $filterParams['table_array'];
                $select_array = $filterParams['select_array'];
                
                // Объявление пагинации
                $nav = new \Bitrix\Main\UI\PageNavigation("more_news");
                $nav->allowAllRecords(true)
                ->setPageSize(isset($_COOKIE['paginationSizeForfilters']) && !empty($_COOKIE['paginationSizeForfilters']) ? $_COOKIE['paginationSizeForfilters'] : PAGE_SIZE[0])
                ->initFromUri();
                // Код запроса данных из таблиц gr_events и gr_value_events на основании условий фильтра
                
                $insert_where = 'WHERE gr_events.fk_project = '.$project->getId().'';

                if (count($condition_array) != 0) {
                    $insert_where .= ' AND ';
                };
                $insert_dot = (count($select_array) != 0) ? ' , ' : '';
                $sql_query = 'SELECT gr_events.id AS gr_events_id, gr_events.datetime AS gr_events_datetime, gr_events.fact_datetime AS gr_events_fact_datetime, gr_events.fk_event_type AS gr_events_fk_event_type, gr_events.fk_project AS gr_events_fk_project'.$insert_dot.' '.implode(' , ',$select_array).'  FROM gr_events '.implode(' ',$table_array).' '.$insert_where.' '.implode(' AND ',$condition_array).' ORDER BY gr_events.id
                LIMIT '.$nav->getLimit().' OFFSET '.$nav->getOffset();

                // Код запроса подсчета колличества элементов на основании услоыий фильтра
                $sql_count_query = 'SELECT COUNT(*) FROM gr_events '.implode(' ',$table_array).' '.$insert_where.' '.implode(' AND ',$condition_array).' ORDER BY gr_events.id';
                // Выполнение запросов к базе данных

                global $DB;

                try {
                    $res =  Application::getConnection()->query($sql_query);
                } catch (Exception $e) {
                    error_log($e->getMessage() . PHP_EOL, 3, __DIR__. '/logs/db_error.log');
                    echo 'Ошибка!';
                    die();
                }

                $res_count = $DB->Query($sql_count_query, false, $err_mess.__LINE__)->fetch();
                // Формирование массива для вывода таблицы
                $name_array=array();
                while ($row = $res->Fetch()) {
                    array_push($name_array, $row);
                }
                // Запись колличества элементов в пагинацию 
                $nav->setRecordCount($res_count ["COUNT(*)"]);

                $this->arResult["filtred_array"] = $name_array;
                $event_type_arr = array();
                // Получение данных о проекте
                $project = Grossnet\Dashboard\ProjectsDataTable::getByPrimary($this->arParams['project_id'], array(
                    'select' => array('*','events_type')
                ))->fetchObject();

                // Получение id Типов событий из проекта
                foreach ($project->getEventsType() as $event_type) {
                    if (!$event_type->getIsDelete()) {
                        $event_type_arr[] = $event_type->getId();
                    }
                }

                // Получение данных Типов событий по id
                $selectOption = array(
                    'select' => array('*', 'event_type', 'VALUES', 'OBJECTS'),
                    'filter' => array('is_delete' => "N", 'event_type.id' => $event_type_arr)
                );
                $params = Grossnet\Dashboard\ParamsTypesEventsDataTable::getList($selectOption)->fetchCollection();

                $this->arResult["params"] = $params;
                $this->arResult["objects_values"] = $objects_values;
                $this->arResult["nav"] = $nav;

                // Подключение компонента
                $this->includeComponentTemplate();
            }
        }
    }

    /**
     * Getting array of errors.
     * @return Error[]
     */
    public function getErrors()
    {
        return $this->errorCollection->toArray();
    }

    /**
     * Getting once error with the necessary code.
     * @param string $code Code of error.
     * @return Error
     */
    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }
}
