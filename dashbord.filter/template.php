<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?$this->addExternalCss("/style.css");?>

<div>
      <div class="body_toper-2">
            <span><?=$arResult["filter"]->getName()?></span>
                  <a class="b1" href="/dashboard/filters/<?=$arParams['project_id']?>/<?=$arResult["filter"]->getId()?>/edit?member_id=<?=$_REQUEST['member_id']?>">Изменить</a>
                  <!-- <button type="button">Экспорт в Excel</button> -->
      </div>
      <div class="nicescrolled" style="max-height:1015px;overflow-y:auto">
            <div class="body_table">
                  <table>
                        <thead>
                              <tr>
                                    <td class="">ID</td>
                                    <td class="">Дата и время</td>
                                    <?foreach($arResult["params"] as $param):?>
                                          <td class="p-1"><?=$param->getRussianName()?></td>
                                    <?endforeach;?>
                              </tr>
                        </thead>
                        <tbody>
                              <?if(count($arResult["filtred_array"]) == 0):?>
                                    <?
                                          $coll = $arResult["params"]->count() + 2;
                                    ?>
                                    <tr><td colspan=<?= $coll?>>Нет данных</td><tr>
                              <?else:?>
                                    <?foreach($arResult["filtred_array"] as $key => $row):?>
                                          <tr class="table-row">
                                                <td class=""><?=$row['gr_events_id']?></td>
                                                <td class=""><?=$row['gr_events_datetime']?></td>   
                                                <?foreach($arResult["params"] as $param):?>
                                                      <?if(!is_null($row['ev'.$param->getId().'_value_str']) || !is_null($row['ev'.$param->getId().'_value_float'])):?>
                                                            <?if(!is_null($row['ev'.$param->getId().'_value_str'])):?>
                                                                  <td class="p-1"><?=$row['ev'.$param->getId().'_value_str']?></td>
                                                            <?else:?>
                                                                  <?if($param->getType() == 'object'):?>

                                                                        <?    
                                                                              $object = json_decode($arResult["objects_values"]->getByPrimary($row['ev'.$param->getId().'_value_float'])->getValue(),true);
                                                                        ?>
                                                                        <td class="p-1">
                                                                              <a href=""><?=$object[array_key_first($object)]?></a>
                                                                        </td>
                                                                  <?elseif($param->getType() == 'list'):?>
                                                                        <?
                                                                              $list = $param->getValues()->getByPrimary($row['ev'.$param->getId().'_value_float'])->getValue();
                                                                        ?>
                                                                        <td class="p-1">
                                                                              <a href=""><?=$list?></a>
                                                                        </td>
                                                                  <?elseif($param->getType() == 'datetime'):?>
                                                                        <td class="p-1">
                                                                              <?=date('d.m.Y',$row['ev'.$param->getId().'_value_float']);?>
                                                                        </td>
                                                                  <?else:?>
                                                                        <td class="p-1"><?=$row['ev'.$param->getId().'_value_float']?></td>
                                                                  <?endif;?>
                                                            <?endif;?>
                                                      <?else:?>
                                                            <td class="p-1">-</td>
                                                      <?endif?>
                                                <?endforeach;?>
                                          </tr>
                                    <?endforeach;?>
                              <?endif;?>
                        </tbody>
                  </table>
            </div>
	</div>
</div>

<script type="text/javascript">
      $(document).ready(function(){
            $(document).on('click', '.load_more', function(){
                  var button_container = $('.pagin-container');
                  var targetContainer = $('tbody'),          
                  url =  $('.load_more').attr('href');
                  view = $('.view_rec');
                  if (url !== undefined) {
                        $.ajax({
                              type: 'GET',
                              url: url,
                              dataType: 'html',
                              success: function(data){
                                    $('.load_more').remove();
                                    var elements      =    $(data).find('.table-row'),
                                    pagination        =    $(data).find('.load_more');
                                    $('.view_rec').text($(data).find('.view_rec').text());
                                    targetContainer.append(elements);
                                    button_container.prepend(pagination); 
                                    jQuery('.nicescrolled').getNiceScroll().resize()
                              }
                        })
                  }
                  return false;
            });
      });
</script>

<div class="pagin-container">
<?
      $APPLICATION->IncludeComponent(
      "main.pagenavigation",
      "show_more_new",
      array(
            "NAV_OBJECT" => $arResult["nav"],
            "SEF_MODE" => "N",
            'FOR' => 'filters',
      ),
      false
      );
?>
</div>
