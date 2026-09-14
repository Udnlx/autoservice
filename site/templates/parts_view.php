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
        <h1 class="uk-heading-hero uk-text-center">Запчасти Справочник</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    if (!function_exists('partClean')) {
        function partClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    // --- Единицы измерения: тянем из настроек поля part_unit ---
    $unit_options = [];
    $unit_field = $fields->get('part_unit');
    if ($unit_field && method_exists($unit_field, 'getOptions')) {
        foreach ($unit_field->getOptions() as $opt) {
            $unit_options[] = ['value' => (string)$opt->value, 'title' => (string)$opt->title];
        }
    }
    // Запасной вариант, если поле или метод недоступны
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

    // --- Поиск ---
    $search_query = trim((string)$input->get('q'));
    $search_active = $search_query !== '';

    $found_parts = [];

    if ($search_active) {
        $safe_query = $sanitizer->selectorValue($search_query);
        $parts_result = $pages->find("template=part, title|part_sku|part_oem|part_brand%=$safe_query, sort=title, limit=100");

        foreach ($parts_result as $partPage) {
            $found_parts[] = [
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
        }
    }

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Запчасти Справочник</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="#new_part_modal" uk-toggle>Новая запчасть</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/zapchasti-spravochnik/" method="get">
                    <label for="q">Поиск запчасти</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo partClean($search_query); ?>" placeholder="Название, артикул, бренд или OEM" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (!$search_active) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Начните поиск, чтобы увидеть запчасти
                    </div>
                <?php } elseif (empty($found_parts)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Ничего не найдено по запросу «<?php echo partClean($search_query); ?>»
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_parts as $part) { ?>
                            <a class="order-list-item" href="/zapchast-prosmotr/?idpart=<?php echo (int)$part['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo partClean($part['title']); ?></div>
                                    <div class="order-status-badge status-new"><?php echo !empty($part['price']) ? (int)$part['price'] . ' ₽' : '—'; ?></div>
                                </div>

                                <div class="order-list-item-grid">
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Артикул</div>
                                        <div class="order-list-item-value"><?php echo !empty($part['sku']) ? partClean($part['sku']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Бренд</div>
                                        <div class="order-list-item-value"><?php echo !empty($part['brand']) ? partClean($part['brand']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">OEM</div>
                                        <div class="order-list-item-value"><?php echo !empty($part['oem']) ? partClean($part['oem']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Остаток</div>
                                        <div class="order-list-item-value"><?php echo ($part['qty'] === null || $part['qty'] === '') ? '—' : (int)$part['qty'] . ' ' . partClean(partUnitLabel($part['unit'], $units_map)); ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Место хранения</div>
                                        <div class="order-list-item-value"><?php echo !empty($part['location']) ? partClean($part['location']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Описание</div>
                                        <div class="order-list-item-value"><?php echo !empty($part['notes']) ? partClean($part['notes']) : '—'; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВАЯ ЗАПЧАСТЬ-->
<div id="new_part_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body uk-overflow-auto">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новая запчасть</h3>

        <form class="uk-flex uk-flex-column" action="/zapchast-registratciia/" method="post">

            <div class="uk-margin-small-top">
                <label for="part_title">Наименование запчасти</label>
                <input class="uk-input" id="part_title" type="text" name="part_title" placeholder="Например: Масляный фильтр MANN W914/2" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="part_sku">Артикул</label>
                <input class="uk-input" id="part_sku" type="text" name="part_sku" placeholder="Например: W914/2" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="part_price">Цена (₽)</label>
                <input class="uk-input" id="part_price" type="number" name="part_price" min="0" step="1" placeholder="Например: 450" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="part_qty">Количество на складе</label>
                <input class="uk-input" id="part_qty" type="number" name="part_qty" min="0" step="1" placeholder="Например: 10" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="part_unit">Единица измерения</label>
                <select class="uk-select" id="part_unit" name="part_unit" required>
                    <option value="" disabled selected>Выберите единицу</option>
                    <?php foreach ($unit_options as $u) { ?>
                        <option value="<?php echo partClean($u['value']); ?>"><?php echo partClean($u['title']); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="uk-margin-small-top">
                <label for="part_brand">Бренд</label>
                <input class="uk-input" id="part_brand" type="text" name="part_brand" placeholder="Например: MANN" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="part_oem">OEM / каталожный номер</label>
                <input class="uk-input" id="part_oem" type="text" name="part_oem" placeholder="Например: 7700274177" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="part_location">Место хранения</label>
                <input class="uk-input" id="part_location" type="text" name="part_location" placeholder="Например: Стеллаж 3, полка B" autocomplete="off">
            </div>

            <div class="uk-margin-small-top">
                <label for="part_notes">Описание</label>
                <textarea class="uk-textarea" id="part_notes" name="part_notes" rows="3" placeholder="Особенности, применимость..."></textarea>
            </div>

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_part" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВАЯ ЗАПЧАСТЬ-->

</div>

<?php   
}
?>