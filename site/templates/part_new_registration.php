<?php namespace ProcessWire;

$today = date("d-m-Y");

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Новая запчасть Регистрация</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $part_title             = !empty($_POST['part_title'])    ? trim($_POST['part_title'])    : NULL;
    $part_sku               = !empty($_POST['part_sku'])      ? trim($_POST['part_sku'])      : NULL;
    $part_price_purchase    = $input->post->int('part_price_purchase');   // 0 допустим, отрицательные ниже отсечём
    $part_price             = $input->post->int('part_price');   // 0 допустим, отрицательные ниже отсечём
    $part_qty               = $input->post->int('part_qty');     // 0 допустим — «нет в наличии»
    $part_unit              = !empty($_POST['part_unit'])     ? trim($_POST['part_unit'])     : NULL;
    $part_brand             = !empty($_POST['part_brand'])    ? trim($_POST['part_brand'])    : NULL;
    $part_oem               = !empty($_POST['part_oem'])      ? trim($_POST['part_oem'])      : NULL;
    $part_location          = !empty($_POST['part_location']) ? trim($_POST['part_location']) : NULL;
    $part_notes             = !empty($_POST['part_notes'])    ? trim($_POST['part_notes'])    : NULL;

    // Куда возвращаться после регистрации
    $return_to = !empty($_POST['return_to']) ? trim($_POST['return_to']) : '';

    $success = '';

    if ($part_title && $part_sku && $part_unit && $part_price_purchase >= 0 && $part_price >= 0 && $part_qty >= 0 && $operator != 'no_operator') {

        $parts_page = $pages->get('name=zapchasti');

        if ($parts_page && $parts_page->id) {

            $partPage = $pages->add('part', $parts_page);

            if ($partPage && $partPage->id) {

                $partPage->of(false);

                // Чистое имя страницы из артикула. При дубле PW сам доклеит -1
                $partPage->name = $sanitizer->pageName($sanitizer->text($part_sku));

                $partPage->title                    = $part_title;
                $partPage->part_sku                 = $part_sku;
                $partPage->part_price_purchase      = $part_price_purchase;
                $partPage->part_price               = $part_price;
                $partPage->part_qty                 = $part_qty;
                $partPage->part_unit                = $part_unit;   // значение опции: 1..5, НЕ подпись
                $partPage->part_brand               = $part_brand;
                $partPage->part_oem                 = $part_oem;
                $partPage->part_location            = $part_location;
                $partPage->part_notes               = $part_notes;

                $partPage->save();

                $success = 'Запчасть успешно зарегистрирована';

                if ($return_to === 'new_order') {
                    $session->redirect('/zakaz-novyi/?part_created=1');
                } else {
                    $session->redirect('/zapchast-prosmotr/?idpart=' . $partPage->id);
                }

            } else {
                $success = 'Запчасть не зарегистрирована!<br>Ошибка при создании страницы';
            }

        } else {
            $success = 'Запчасть не зарегистрирована!<br>Не найдена папка «Запчасти» (name=zapchasti)';
        }

    } else {
        $success = 'Запчасть не зарегистрирована!<br>Ошибка в данных';
    }

    $info = '';
    if ($success == 'Запчасть успешно зарегистрирована') {
        $info .= '
            <p class="uk-margin-remove">Запчасть успешно зарегистрирована<br>Сейчас произойдёт переход на страницу запчасти</p>
        ';
    } else {
        $info .= '
            <p class="uk-margin-remove">' . $success . '<br>Возможно некорректные данные или не заполнены обязательные поля.<br>Попробуйте позже или обратитесь в техподдержку</p>
            <a class="uk-margin-small-top uk-button uk-button-default" href="/zapchasti-spravochnik/">Вернуться в справочник</a>
        ';
    }

}

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo $success; ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/zapchasti-spravochnik/">Справочник запчастей</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <?php echo $info; ?>
            </div>
        </div>

    </div>
</div>