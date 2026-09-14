<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Изменение запчасти</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

	$part_id = !empty($_POST['part_id']) ? (int)$_POST['part_id'] : 0;

    $part_title    = !empty($_POST['part_title'])    ? trim($_POST['part_title'])    : '';
    $part_sku      = !empty($_POST['part_sku'])      ? trim($_POST['part_sku'])      : '';
    $part_price    = $input->post->int('part_price');   // 0 допустим
    $part_qty      = $input->post->int('part_qty');     // 0 допустим — «нет в наличии»
    $part_unit     = !empty($_POST['part_unit'])     ? trim($_POST['part_unit'])     : '';
    $part_brand    = !empty($_POST['part_brand'])    ? trim($_POST['part_brand'])    : '';
    $part_oem      = !empty($_POST['part_oem'])      ? trim($_POST['part_oem'])      : '';
    $part_location = !empty($_POST['part_location']) ? trim($_POST['part_location']) : '';
    $part_notes    = !empty($_POST['part_notes'])    ? trim($_POST['part_notes'])    : '';

    // --- Допустимые значения единицы измерения ---
    $unit_values = [];
    $unit_field = $fields->get('part_unit');
    if ($unit_field && method_exists($unit_field, 'getOptions')) {
        foreach ($unit_field->getOptions() as $opt) {
            $unit_values[] = (string)$opt->value;
        }
    }
    if (empty($unit_values)) {
        $unit_values = ['1', '2', '3', '4', '5'];
    }

    $unit_valid = in_array((string)$part_unit, $unit_values, true);

    $success = false;

    if ($part_title && $part_sku && $unit_valid && $part_price >= 0 && $part_qty >= 0) {

        $partPage = $pages->get("id=$part_id, template=part");

        if ($partPage->id) {

            $partPage->of(false);

            $partPage->title         = $part_title;
            $partPage->part_sku      = $part_sku;
            $partPage->part_price    = $part_price;
            $partPage->part_qty      = $part_qty;
            $partPage->part_unit     = $part_unit;   // значение опции: 1..5, НЕ подпись
            $partPage->part_brand    = $part_brand;
            $partPage->part_oem      = $part_oem;
            $partPage->part_location = $part_location;
            $partPage->part_notes    = $part_notes;

            $partPage->save();

            $success = true;
        }
    }

    if ($success) {
        $session->redirect('/zapchast-prosmotr/?idpart=' . $part_id . '&saved=1');
    } else {
        $session->redirect('/zapchast-prosmotr/?idpart=' . $part_id . '&saved=0');
    }
}
?>