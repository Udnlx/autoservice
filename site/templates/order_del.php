<?php namespace ProcessWire;

if (!function_exists('orderClean')) {
    function orderClean($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (isset($_SESSION['operator'])) {
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>
    <div id="content" style="max-width: 700px;">
        <h1 class="uk-heading-hero uk-text-center">Удаление заказ-наряда</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php
} else {

    // Название статуса удаления — в одном месте
    $delete_status = 'Удалена';

    // Закрытые заявки: их нельзя удалять
    $locked_statuses = ['Отменена', 'Завершена'];

    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

    $deleted      = false;
    $error_text   = '';
    $order_title  = '';

    if (!$order_id) {

        $error_text = 'Не передан ID заявки';

    } else {

        $orderPage = $pages->get("id=$order_id, template=order_item");

        if (!$orderPage->id) {

            $error_text = 'Заявка не найдена';

        } else {

            $order_title = (string)$orderPage->title;

            // =========================================================
            // ЗАЩИТА ОТ ДВОЙНОГО УДАЛЕНИЯ.
            // Повторный POST (F5, вторая вкладка) не должен ещё раз
            // вернуть запчасти на склад. Считаем успехом и выходим.
            // =========================================================
            if ($orderPage->isHidden() && $orderPage->isUnpublished()) {

                $deleted = true;

            } elseif (in_array((string)$orderPage->status_order, $locked_statuses, true)) {

                $error_text = 'Заявка «' . $orderPage->status_order . '» — удаление невозможно';

            } else {

                // =========================================================
                // ВОЗВРАТ ЗАПЧАСТЕЙ НА СКЛАД.
                // Как в ветке отмены order_edit.php: берём СТАРУЮ корзину,
                // ничего не проверяем и не перезаписываем репитеры.
                // =========================================================
                foreach ($orderPage->autoparts as $oldItem) {
                    $pid = (int)$oldItem->part_id;
                    if ($pid <= 0) {
                        continue;   // запчасть без привязки к справочнику — возвращать некуда
                    }

                    $partPage = $pages->get("id=$pid, template=part");
                    if (!$partPage->id) {
                        continue;   // запчасть удалена из справочника
                    }

                    // max(1, ...) — страховка от очень старых заявок, где qty в базе пустое
                    $return_qty = max(1, (int)$oldItem->qty);

                    $partPage->of(false);
                    $partPage->part_qty = (int)$partPage->part_qty + $return_qty;
                    $partPage->save('part_qty');
                }
                // =========================================================
                // /ВОЗВРАТ ЗАПЧАСТЕЙ НА СКЛАД
                // =========================================================

                // Статус + скрытие. Саму страницу НЕ удаляем.
                $orderPage->of(false);
                $orderPage->status_order = $delete_status;
                $orderPage->addStatus(Page::statusHidden);
                $orderPage->addStatus(Page::statusUnpublished);
                $orderPage->save();

                $deleted = true;
            }
        }
    }
?>

<div id="content" style="max-width: 700px;">
    <h1 class="uk-heading-hero uk-text-center">Удаление заказ-наряда</h1>

    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">

        <?php if ($deleted) { ?>

            <div class="uk-alert-success" uk-alert>
                <p class="uk-margin-remove"><strong>Заказ удален</strong></p>
                <?php if ($order_title !== '') { ?>
                    <p class="uk-margin-small-top uk-margin-remove-bottom"><?php echo orderClean($order_title); ?> больше не отображается в списке заказов.</p>
                <?php } ?>
            </div>

            <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>

        <?php } else { ?>

            <div class="uk-alert-danger" uk-alert>
                <p class="uk-margin-remove"><strong>Удалить заявку не удалось</strong></p>
                <p class="uk-margin-small-top uk-margin-remove-bottom"><?php echo orderClean($error_text); ?></p>
            </div>

            <?php if ($order_id && $order_title !== '') { ?>
                <a class="uk-margin-small uk-button uk-button-default" href="/zakaz-prosmotr/?idorder=<?php echo (int)$order_id; ?>">Вернуться к заявке</a>
            <?php } ?>
            <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>

        <?php } ?>

    </div>
</div>

<?php
}
?>