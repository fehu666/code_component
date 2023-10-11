<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>




<?foreach($arResult['list'] as $key => $list):?>
    <div class="row">
        <div class="col-md-3"><?=$key?></div>
        <div class="col-md-9">
            <?foreach($list as $list_elem):?>
                <a href="/dashboard/event_types/edit/<?=$list_elem['id']?>/"><?=$list_elem['name']?></a>
            <?endforeach;?>
        </div>
    </div>
<?endforeach;?>