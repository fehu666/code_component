<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if ($arResult["with_container"]) :?>
    <div class="params_row" id="params_row_<?=$arResult["param"]->getId()?>">
<?endif?>
        <div class="params_inner row w-100">
            <input type="hidden" name="params_id[]" value="<?=$arResult["param"]->getId()?>">
            <div class="col-md-1">
                <div class="params_id"><?=$arResult["param"]->getId()?></div>
            </div>
            <div class="col-md-2">
                <input type="text" name="params_rus_name" id="" value="<?=$arResult["param"]->getRussianName()?>">
            </div>
            <div class="col-md-2">
                <input type="text" name="params_eng_name" id="" value="<?=$arResult["param"]->getEnglishName()?>">
            </div>
            <div class="col-md-2">
                <select name="params_type" id="" class="form-select">
                        <option <?=($arResult["param"]->getType() == 'string') ? 'selected' : ''?> value="string">Строка</option>
                        <option <?=($arResult["param"]->getType() == 'number') ? 'selected' : ''?> value="number">Число</option>
                        <option <?=($arResult["param"]->getType() == 'list') ? 'selected' : ''?> value="list">Список</option>
                        <option <?=($arResult["param"]->getType() == 'refillable_list') ? 'selected' : ''?> value="refillable_list">Пополняемый список</option>
                        <option <?=($arResult["param"]->getType() == 'boolean') ? 'selected' : ''?> value="boolean">Да/Нет</option>
                        <option <?=($arResult["param"]->getType() == 'datetime') ? 'selected' : ''?> value="datetime">Дата и время</option>
                        <option <?=($arResult["param"]->getType() == 'object') ? 'selected' : ''?> value="object">Объект</option>
                </select>
            </div>
            <div class="col-md-5 d-flex">
                <?if($arResult["param"]->getType() == 'number'):?>
                        <div class="mes">
                            <input type="text" name="params_mes" id="" value="<?=$arResult["param"]->getMeasurement()?>">
                        </div>
                <?endif;?>

                <?if($arResult["param"]->getType() == 'object'):?>
                        <select name="params_obj_type" id="">
                            <?foreach ($arResult["object"] as $object):?>
                                    <option value="<?=$object['id']?>"><?=$object['name']?></option>
                            <?endforeach;?>
                        </select>
                <?endif;?>

                <?if($arResult["param"]->getType() == 'list'):?>
                        <a href="#" data-id="<?=$arResult["param"]->getId()?>" class="btn btn-primary type_list_edit" data-bs-toggle="modal" data-bs-target="#exampleModal">...</a>
                <?endif;?>

                <?if($arResult["param"]->getType() == 'object'):?>
                        <a href="#" data-id="<?=$arResult["param"]->getId()?>" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addObjectType">+</a>
                        <a href="#" data-id="<?=$arResult["param"]->getId()?>" class="btn btn-primary d-flex flex-column flex-start" data-bs-toggle="modal" data-bs-target="#addObjectValues">
                            <span style="height: 5px; line-height: 5px; width: 10px">.</span>
                            <span style="height: 5px; line-height: 5px; width: 10px">.</span>
                            <span style="height: 5px; line-height: 5px; width: 10px">.</span>
                        </a>
                <?endif;?>

                <div class="requery_params">
                        <span>Обязательный</span>
                        <input type="checkbox" name="params_required" id="">
                </div>
                <a href="#" class="type_list">Меню бургер</a>
                <a href="#" class="delete_params" data-id="<?=$arResult["param"]->getId()?>">Удалить</a>
            </div>
        </div>
<?if ($arResult["with_container"]) :?>
    </div>
<?endif?>