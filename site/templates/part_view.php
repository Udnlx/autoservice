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
        <h1 class="uk-heading-hero uk-text-center">Запчасть Просмотр</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    $part_id = $input->get->int('idpart');

    if (!$part_id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Запчасть Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Не передан ID запчасти</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    $partPage = $pages->get("id=$part_id, template=part");

    if (!$partPage->id) {
        echo '<div id="content" style="max-width: 700px;">
            <h1 class="uk-heading-hero uk-text-center">Запчасть Просмотр</h1>
            <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
                <h3 class="uk-card-title uk-text-center">Запчасть не найдена</h3>
                <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
            </div>
        </div>';
        return;
    }

    if (!function_exists('partClean')) {
        function partClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    // --- Единицы измерения: значения и подписи из настроек поля ---
    $unit_options = [];
    $unit_field = $fields->get('part_unit');
    if ($unit_field && method_exists($unit_field, 'getOptions')) {
        foreach ($unit_field->getOptions() as $opt) {
            $unit_options[] = ['value' => (string)$opt->value, 'title' => (string)$opt->title];
        }
    }
    if (empty($unit_options)) {
        $unit_options = [
            ['value' => '1', 'title' => 'шт'],
            ['value' => '2', 'title' => 'л'],
            ['value' => '3', 'title' => 'кг'],
            ['value' => '4', 'title' => 'м'],
            ['value' => '5', 'title' => 'компл'],
        ];
    }

    $units_map = [];
    foreach ($unit_options as $u) {
        $units_map[$u['value']] = $u['title'];
    }

    if (!function_exists('partUnitLabel')) {
        function partUnitLabel($raw, $map) {
            if (is_object($raw) && !empty($raw->title)) {
                return (string)$raw->title;
            }
            $key = (string)$raw;
            return isset($map[$key]) ? $map[$key] : $key;
        }
    }

    $part = [
        'id'       => $partPage->id,
        'title'    => $partPage->title,
        'sku'      => $partPage->part_sku,
        'price'    => $partPage->part_price,
        'qty'      => $partPage->part_qty,
        'unit'     => $partPage->part_unit,
        'brand'    => $partPage->part_brand,
        'oem'      => $partPage->part_oem,
        'location' => $partPage->part_location,
        'notes'    => $partPage->part_notes
    ];

    $part_unit_label = partUnitLabel($part['unit'], $units_map);

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center"><?php echo partClean($part['title']); ?></h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="/zapchasti-spravochnik/">Справочник запчастей</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <div class="order-view-top">
                    <div>
                        <div class="order-view-number"><?php echo partClean($part['title']); ?></div>
                        <div class="order-view-subtitle">Карточка запчасти ID <?php echo partClean($part['id']); ?></div>
                    </div>

                    <div class="order-status-badge">
                        <?php echo ($part['price'] === null || $part['price'] === '') ? '—' : (int)$part['price'] . ' ₽'; ?>
                    </div>
                </div>

                <?php if ($input->get->int('expend') > 0) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">
                            Расход со склада: <strong><?php echo (int)$input->get->int('expend'); ?> <?php echo partClean($part_unit_label); ?></strong>
                            <?php if ($input->get('expend_note')): ?>
                                — <?php echo partClean($input->get('expend_note')); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php } elseif ($input->get->int('expend_error') === 1) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">
                            Недостаточно на складе — нужно <strong><?php echo (int)$input->get->int('expend_need'); ?> <?php echo partClean($part_unit_label); ?></strong>,
                            на складе <strong><?php echo (int)$input->get->int('expend_have'); ?> <?php echo partClean($part_unit_label); ?></strong>
                        </p>
                    </div>
                <?php } ?>

                <?php if ($input->get->int('coming') > 0) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">
                            Приход на склад: <strong><?php echo (int)$input->get->int('coming'); ?> <?php echo partClean($part_unit_label); ?></strong>
                            <?php if ($input->get('coming_note')): ?>
                                — <?php echo partClean($input->get('coming_note')); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php } elseif ($input->get->int('saved') === 1) { ?>
                    <div class="uk-alert-success uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Изменения сохранены</p>
                    </div>
                <?php } elseif ($input->get('saved') !== null && $input->get->int('saved') === 0) { ?>
                    <div class="uk-alert-danger uk-margin-small-top" uk-alert>
                        <a class="uk-alert-close" uk-close></a>
                        <p class="uk-margin-remove">Не удалось сохранить изменения</p>
                    </div>
                <?php } ?>

                <!--БЛОК ПРОСМОТРА-->
                <div id="part_view_block">
                    <div class="order-view-grid uk-margin-small-top">
                        <div class="order-info-box">
                            <div class="order-info-label">Наименование</div>
                            <div class="order-info-value"><?php echo partClean($part['title']); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Артикул</div>
                            <div class="order-info-value"><?php echo !empty($part['sku']) ? partClean($part['sku']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Бренд</div>
                            <div class="order-info-value"><?php echo !empty($part['brand']) ? partClean($part['brand']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">OEM / каталожный номер</div>
                            <div class="order-info-value"><?php echo !empty($part['oem']) ? partClean($part['oem']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Цена</div>
                            <div class="order-info-value"><?php echo ($part['price'] === null || $part['price'] === '') ? '—' : (int)$part['price'] . ' ₽'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Остаток</div>
                            <div class="order-info-value"><?php echo ($part['qty'] === null || $part['qty'] === '') ? '—' : (int)$part['qty'] . ' ' . partClean($part_unit_label); ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Единица измерения</div>
                            <div class="order-info-value"><?php echo !empty($part_unit_label) ? partClean($part_unit_label) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Место хранения</div>
                            <div class="order-info-value"><?php echo !empty($part['location']) ? partClean($part['location']) : '—'; ?></div>
                        </div>

                        <div class="order-info-box">
                            <div class="order-info-label">Описание</div>
                            <div class="order-info-value"><?php echo !empty($part['notes']) ? partClean($part['notes']) : '—'; ?></div>
                        </div>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="coming_edit_btn">
                            Приход
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="expend_edit_btn">
                            Расход
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="toggle_edit_btn">
                            Изменить
                        </button>
                        <a class="uk-margin-small-top uk-button uk-button-default" href="/">Перейти на главную</a>
                    </div>
                </div>
                <!--БЛОК ПРОСМОТРА-->

                <!--БЛОК РАСХОДА (скрыт по умолчанию)-->
                <form id="part_expend_form" class="uk-flex uk-flex-column" action="/zapchast-raskhod/" method="post" hidden>
                    <input type="hidden" name="part_id" value="<?php echo partClean($part['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="expend_qty">Количество (<?php echo partClean($part_unit_label); ?>)</label>
                        <input class="uk-input" id="expend_qty" type="number" name="expend_qty" min="1" step="1" placeholder="Например: 3" autocomplete="off" required>
                        <div style="margin-top: 4px; font-size: 0.85em; color: #999;">
                            На складе сейчас: <?php echo (int)$part['qty']; ?> <?php echo partClean($part_unit_label); ?>
                        </div>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="expend_note">Примечание</label>
                        <input class="uk-input" id="expend_note" type="text" name="expend_note" placeholder="Например: Выдано на ремонт" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_expend" value="1">
                            Списать со склада
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="cancel_expend_btn">
                            Отмена
                        </button>
                    </div>
                </form>
                <!--БЛОК РАСХОДА-->

                <!--БЛОК ПРИХОДА (скрыт по умолчанию)-->
                <form id="part_coming_form" class="uk-flex uk-flex-column" action="/zapchast-prikhod/" method="post" hidden>
                    <input type="hidden" name="part_id" value="<?php echo partClean($part['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="coming_qty">Количество (<?php echo partClean($part_unit_label); ?>)</label>
                        <input class="uk-input" id="coming_qty" type="number" name="coming_qty" min="1" step="1" placeholder="Например: 5" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="coming_note">Примечание</label>
                        <input class="uk-input" id="coming_note" type="text" name="coming_note" placeholder="Например: Закупка у поставщика" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_coming" value="1">
                            Добавить на склад
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="cancel_coming_btn">
                            Отмена
                        </button>
                    </div>
                </form>
                <!--БЛОК ПРИХОДА-->

                <!--БЛОК РЕДАКТИРОВАНИЯ (скрыт по умолчанию)-->
                <form id="part_edit_form" class="uk-flex uk-flex-column" action="/zapchast-redaktirovanie/" method="post" hidden>
                    <input type="hidden" name="part_id" value="<?php echo partClean($part['id']); ?>">

                    <div class="uk-margin-small-top">
                        <label for="part_title">Наименование запчасти</label>
                        <input class="uk-input" id="part_title" type="text" name="part_title" value="<?php echo partClean($part['title']); ?>" placeholder="Например: Масляный фильтр MANN W914/2" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_sku">Артикул</label>
                        <input class="uk-input" id="part_sku" type="text" name="part_sku" value="<?php echo partClean($part['sku']); ?>" placeholder="Например: W914/2" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_price">Цена (₽)</label>
                        <input class="uk-input" id="part_price" type="number" name="part_price" min="0" step="1" value="<?php echo ($part['price'] === null || $part['price'] === '') ? '' : (int)$part['price']; ?>" placeholder="Например: 450" autocomplete="off" required>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_unit">Единица измерения</label>
                        <select class="uk-select" id="part_unit" name="part_unit" required>
                            <option value="" disabled <?php echo empty($part_unit_label) ? 'selected' : ''; ?>>Выберите единицу</option>
                            <?php foreach ($unit_options as $u) { ?>
                                <option value="<?php echo partClean($u['value']); ?>" <?php echo ((string)$u['value'] === (string)$part['unit']) ? 'selected' : ''; ?>>
                                    <?php echo partClean($u['title']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_brand">Бренд</label>
                        <input class="uk-input" id="part_brand" type="text" name="part_brand" value="<?php echo partClean($part['brand']); ?>" placeholder="Например: MANN" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_oem">OEM / каталожный номер</label>
                        <input class="uk-input" id="part_oem" type="text" name="part_oem" value="<?php echo partClean($part['oem']); ?>" placeholder="Например: 7700274177" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_location">Место хранения</label>
                        <input class="uk-input" id="part_location" type="text" name="part_location" value="<?php echo partClean($part['location']); ?>" placeholder="Например: Стеллаж 3, полка B" autocomplete="off">
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="part_notes">Описание</label>
                        <textarea class="uk-textarea" id="part_notes" name="part_notes" rows="3" placeholder="Особенности, применимость..."><?php echo partClean($part['notes']); ?></textarea>
                    </div>

                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_part_changes" value="1">
                            Сохранить изменения
                        </button>
                        <button type="button" class="uk-margin-small-top uk-button uk-button-default" id="cancel_edit_btn">
                            Отмена
                        </button>
                    </div>
                </form>
                <!--БЛОК РЕДАКТИРОВАНИЯ-->

            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- Показ/скрытие формы редактирования ---
    var viewBlock = document.getElementById('part_view_block');
    var editForm  = document.getElementById('part_edit_form');
    var toggleBtn = document.getElementById('toggle_edit_btn');
    var cancelBtn = document.getElementById('cancel_edit_btn');

    var comingForm   = document.getElementById('part_coming_form');
    var comingBtn    = document.getElementById('coming_edit_btn');
    var cancelComing = document.getElementById('cancel_coming_btn');

    var expendForm   = document.getElementById('part_expend_form');
    var expendBtn    = document.getElementById('expend_edit_btn');
    var cancelExpend = document.getElementById('cancel_expend_btn');

    if (expendBtn) {
        expendBtn.addEventListener('click', function () {
            viewBlock.hidden  = true;
            editForm.hidden   = true;
            comingForm.hidden = true;
            expendForm.hidden = false;
            expendForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelExpend) {
        cancelExpend.addEventListener('click', function () {
            expendForm.hidden = true;
            viewBlock.hidden  = false;
            viewBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (comingBtn) {
        comingBtn.addEventListener('click', function () {
            viewBlock.hidden  = true;
            editForm.hidden   = true;
            expendForm.hidden = true;
            comingForm.hidden = false;
            comingForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelComing) {
        cancelComing.addEventListener('click', function () {
            comingForm.hidden = true;
            viewBlock.hidden  = false;
            viewBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            viewBlock.hidden  = true;
            comingForm.hidden = true;
            expendForm.hidden = true;
            editForm.hidden   = false;
            editForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            editForm.hidden   = true;
            comingForm.hidden = true;
            expendForm.hidden = true;
            viewBlock.hidden  = false;
            viewBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
});
</script>

<?php   
}
?>