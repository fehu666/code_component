<?

namespace Grossnet\Dashboard;
// Класс для работы с фильтрами

class Filters
{
    // Получение всех фиьтров
    // Входной параметр id проекта 
    public static function getAllFilters($project_id)
    {
        $filters = FiltersDataTable::getList(array(
            'select' => array('*'),
            'filter' => array('fk_project' => $project_id, 'is_delete' => 'N')
        ))->fetchCollection();

        return $filters;
    }

    /**
     * Получить параметры для SQL запроса событий с учетом параметров фильтра $filter_id
     * @param int $filter_id
     * @param array $params_id id необходимых параметров и их тип
     * @param string $filter_type тип фильтра: including - включающий, exception - исключающий
     * @return array
     */
    public static function getFilterParams($filter_id, $params_id, $filter_type = 'including')
    {
        if ($filter_type !== 'including' && $filter_type !== 'exception') return false;

        if ($filter_id) {

            // Запрос данных фильтра по ID
            $arResult["filter"] = \Grossnet\Dashboard\FiltersDataTable::getByPrimary($filter_id, array(
                'select' => array(
                    '*',
                    'filters_params'
                ),
            ))->fetchObject();

            // Запрос параметров фильтрации по ID фильтра
            $arResult["filter_params"] = \Grossnet\Dashboard\FiltersParamsDataTable::getList(array(
                'select' => array('*'),
                'filter' => array('fk_filter' => $arResult["filter"]->getId()),
            ))->fetchCollection();
        }

        // Обьявления массива для записи условий фильтрации
        $condition_array = array();

        // Обьявления массива для записи таблиц участвующих в фильтрации
        $table_array = array();
        $select_array = array();

        // Цикл перебора параметров фильтрации 

        foreach ($params_id as $id) {
            $table_array[] = 'LEFT JOIN gr_value_events ev' . $id . ' ON ev' . $id . '.fk_events=gr_events.id AND ev' . $id . '.fk_param=' . $id . '';
            $select_array[] = 'ev' . $id . '.id AS ev' . $id . '_id, ev' . $id . '.value_float AS ev' . $id . '_value_float, ev' . $id . '.value_str AS ev' . $id . '_value_str, ev' . $id . '.fk_param AS ev' . $id . '_fk_param, ev' . $id . '.fk_events AS ev' . $id . '_fk_events';
        }

        foreach ($arResult["filter_params"] as $param) {
            // Если поле заполненно
            if ($param->getCondition() == 'filled') {
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float is not null or ev' . $param->getFkParam() . '.value_str is not null)';
            }
            // Если поле не заполненно
            if ($param->getCondition() == 'not_filled') {
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float is null or ev' . $param->getFkParam() . '.value_str is null)';
            }
            // Если поле равно строке
            if ($param->getCondition() == 'equal_to_string') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_str = "' . $param->getMeaning() . '"';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_str IS NOT NULL';
                }
            }
            // Если поле содержит часть строки
            if ($param->getCondition() == 'part_of_string') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_str LIKE "%' . $param->getMeaning() . '%"';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_str IS NOT NULL';
                }
            }
            // Если поле не содержит часть строки
            if ($param->getCondition() == 'exclude_string') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_str NOT LIKE "%' . $param->getMeaning() . '%"';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_str IS NOT NULL';
                }
            }
            // Если поле равно числу
            if ($param->getCondition() == 'equal_to_int') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float = ' . $param->getMeaning() . '';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле больше числа
            if ($param->getCondition() == 'more') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float > ' . $param->getMeaning() . '';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле меньше числа
            if ($param->getCondition() == 'less') {
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float < ' . $param->getMeaning() . '';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= '. AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле входит в диапазон
            if ($param->getCondition() == 'included_range') {
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $param->getMeaning() . ' AND ev' . $param->getFkParam() . '.value_float < ' . $param->getMeaning_2() . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле исключает диапазон
            if ($param->getCondition() == 'exclude_range') {
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float < ' . $param->getMeaning() . ' OR ev' . $param->getFkParam() . '.value_float > ' . $param->getMeaning_2() . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах текущей недели
            if ($param->getCondition() == 'week') {
                $date = date('Y-m-d', time());
                $date_start =  strtotime('monday this week', strtotime($date));
                $date_end =  strtotime('sunday this week', strtotime($date));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах прошлой недели
            if ($param->getCondition() == 'last_week') {
                $date = date('Y-m-d', time());
                $date_start = strtotime('-1 week monday 00:00:00', strtotime($date));
                $date_end = strtotime('-1 week sunday 23:59:59', strtotime($date));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах текущего месяца
            if ($param->getCondition() == 'month') {
                $date = date('Y-m-d', time());
                $date_start = strtotime(date('Y-m-1'));
                $date_end = strtotime(date('Y-m-t'));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах прошлого месяца
            if ($param->getCondition() == 'last_month') {
                $date = date('Y-m-d', time());
                $date_start = mktime(0, 0, 0, date('m') - 1, 01);
                $date_end   = mktime(23, 59, 59, date('m'), 0);
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах текущего квартала
            if ($param->getCondition() == 'quarter') {
                $current_quarter = ceil(date('n') / 3);
                $first_date = date('Y-m-d', strtotime(date('Y') . '-' . (($current_quarter * 3) - 2) . '-1'));
                $last_date = date('Y-m-t', strtotime(date('Y') . '-' . (($current_quarter * 3)) . '-1'));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . strtotime($first_date) . ' AND ev' . $param->getFkParam() . '.value_float < ' . strtotime($last_date) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах прошлого квартала
            if ($param->getCondition() == 'last_quarter') {
                $current_quarter = ceil(date('n') / 3);
                $first_date = date('Y-m-d', strtotime(date('Y') . '-' . (($current_quarter * 3) - 2) . '-1'));
                $last_date = date('Y-m-t', strtotime(date('Y') . '-' . (($current_quarter * 3)) . '-1'));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . strtotime($first_date) . ' AND ev' . $param->getFkParam() . '.value_float < ' . strtotime($last_date) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах текущего года
            if ($param->getCondition() == 'year') {
                $date = date('Y-m-d', time());
                $date_start = strtotime('first day of Jan', strtotime($date));
                $date_end = strtotime('last day of Dec', strtotime($date));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах прошлого года
            if ($param->getCondition() == 'last_year') {
                $date_start = mktime(0, 0, 0, 1, 1, date('Y') - 1);
                $date_end   = mktime(23, 59, 59, 1, 0, date('Y'));
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах последних N дней
            if ($param->getCondition() == 'last_n_days') {
                $date = date('Y-m-d', time());
                $date_start = strtotime("now");
                $date_end = strtotime("-" . $param->getMeaning() . "day");
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты равно дате
            if ($param->getCondition() == 'date') {
                $date = date('Y-m-d', time());
                $date_start = strtotime("now");
                $date_end = strtotime("-" . $param->getMeaning() . "day");
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты в пределах диапазона
            if ($param->getCondition() == 'contains_date') {
                $date = date('Y-m-d', time());
                $date_start = intval($param->getMeaning());
                $date_end = intval($param->getMeaning_2());
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float > ' . $date_start . ' AND ev' . $param->getFkParam() . '.value_float < ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если поле даты не в пределах диапазона
            if ($param->getCondition() == 'exclude_date') {
                $date = date('Y-m-d', time());
                $date_start = intval($param->getMeaning());
                $date_end = intval($param->getMeaning_2());
                $condition_array[$param->getFkParam()] = '(ev' . $param->getFkParam() . '.value_float < ' . $date_start . ' OR ev' . $param->getFkParam() . '.value_float > ' . $date_end . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если содержится обьект из списка
            if ($param->getCondition() == 'contains_object') {
                $object_filter = json_decode($param->getMeaningList());
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float IN (' . implode(',', $object_filter) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если не содержится обьект из списка
            if ($param->getCondition() == 'exclude_object') {
                $object_filter = json_decode($param->getMeaningList());
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float NOT IN (' . implode(',', $object_filter) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если содержится часть названия обьекта
            if ($param->getCondition() == 'contains_part_name_object') {
                $object_id = array();
                $selectOption = array(
                    'select' => array('*'),
                    'filter' => array('is_delete' => "N", 'fk_event_type_param' => $param->getFkParam())
                );
                $obj_values = \Grossnet\Dashboard\ValueObjectDataTable::getList($selectOption)->fetchAll();
                if (count($obj_values) == 0) {
                    $object_id[] = -1;
                }
                foreach ($obj_values as $value) {
                    $item_json = json_decode($value['value'], true);
                    if ($item_json[array_key_first($item_json)] == $param->getMeaning()) {
                        $object_id[] = $value['id'];
                    }
                }
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float IN (' . implode(',', $object_id) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если не содержится часть названия обьекта
            if ($param->getCondition() == 'exclude_part_name_object') {
                $object_id = array();
                $selectOption = array(
                    'select' => array('*'),
                    'filter' => array('is_delete' => "N", 'fk_event_type_param' => $param->getFkParam())
                );
                $obj_values = \Grossnet\Dashboard\ValueObjectDataTable::getList($selectOption)->fetchAll();
                if (count($obj_values) == 0) {
                    $object_id[] = -1;
                }
                foreach ($obj_values as $value) {
                    $item_json = json_decode($value['value'], true);
                    if ($item_json[array_key_first($item_json)] == $param->getMeaning()) {
                        $object_id[] = $value['id'];
                    }
                }
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float NOT IN (' . implode(',', $object_id) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если содержится элемент списка
            if ($param->getCondition() == 'contains_list') {
                $object_filter = json_decode($param->getMeaningList());
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float IN (' . implode(',', $object_filter) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
            // Если не содержится элемент списка
            if ($param->getCondition() == 'exclude_list') {
                $object_filter = json_decode($param->getMeaningList());
                $condition_array[$param->getFkParam()] = 'ev' . $param->getFkParam() . '.value_float NOT IN (' . implode(',', $object_filter) . ')';
                if ($filter_type == 'exception') {
                    $condition_array[$param->getFkParam()] .= ' AND ev' . $param->getFkParam() . '.value_float IS NOT NULL';
                }
            }
        }

        return [
            'arResult' => $arResult,
            'condition_array' => $condition_array,
            'table_array' => $table_array,
            'select_array' => $select_array
        ];
    }
}
