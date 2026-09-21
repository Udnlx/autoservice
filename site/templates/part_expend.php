<?php namespace ProcessWire;

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Расход со склада</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    $part_id    = !empty($_POST['part_id'])    ? (int)$_POST['part_id']        : 0;
    $expend_qty = !empty($_POST['expend_qty']) ? (int)$_POST['expend_qty']     : 0;
    $expend_note = !empty($_POST['expend_note']) ? trim($_POST['expend_note']) : '';

    $success = false;
    $stock_error = false;
    $qty_now = 0;

    if ($part_id > 0 && $expend_qty > 0) {

        $partPage = $pages->get("id=$part_id, template=part");

        if ($partPage->id) {

            $qty_now = (int)$partPage->part_qty;

            if ($qty_now < $expend_qty) {
                // Не хватает — отказ с данными для алерта
                $stock_error = true;
            } else {
                $partPage->of(false);
                $partPage->part_qty = $qty_now - $expend_qty;
                $partPage->save('part_qty');

                $success = true;
            }
        }
    }

    if ($success) {
        $redirect = '/zapchast-prosmotr/?idpart=' . $part_id . '&expend=' . $expend_qty;
        if ($expend_note !== '') {
            $redirect .= '&expend_note=' . urlencode($expend_note);
        }
        $session->redirect($redirect);

    } elseif ($stock_error) {
        $redirect = '/zapchast-prosmotr/?idpart=' . $part_id
            . '&expend_error=1'
            . '&expend_need=' . $expend_qty
            . '&expend_have=' . $qty_now;
        $session->redirect($redirect);

    } else {
        $session->redirect('/zapchast-prosmotr/?idpart=' . $part_id . '&saved=0');
    }
}
?>