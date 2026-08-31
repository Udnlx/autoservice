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
        <h1 class="uk-heading-hero uk-text-center">Автомобили Справочник</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {

    if (!function_exists('carClean')) {
        function carClean($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        }
    }

    $search_query = trim((string)$input->get('q'));
    $search_active = $search_query !== '';

    $found_cars = [];

    if ($search_active) {
        $safe_query = $sanitizer->selectorValue($search_query);
        $cars_result = $pages->find("template=car, title%=$safe_query, limit=100");

        foreach ($cars_result as $carPage) {
            $ownerPage = $carPage->car_owner;
            $found_cars[] = [
                'id'           => $carPage->id,
                'title'        => $carPage->title,
                'brand'        => $carPage->car_brand,
                'model'        => $carPage->car_model,
                'number'       => $carPage->car_number,
                'vin'          => $carPage->car_vin,
                'year'         => $carPage->car_year,
                'owner_id'     => ($ownerPage && $ownerPage->id) ? $ownerPage->id : 0,
                'owner_title'  => ($ownerPage && $ownerPage->id) ? $ownerPage->title : '',
                'notes'        => $carPage->car_notes
            ];
        }
    }

    // Загружаем всех владельцев для select в модальном окне
    $all_owners = $pages->find("template=owner, sort=title, limit=500");

?>

<div id="content">
    <h1 class="uk-margin-remove uk-heading-hero uk-text-center">Автомобили Справочник</h1>
    <div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
                <a class="menu-link" href="#new_car_modal" uk-toggle>Новый автомобиль</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">

                <form class="uk-flex uk-flex-column" action="/avtomobili-spravochnik/" method="get">
                    <label for="q">Поиск автомобиля</label>

                    <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                        <input class="uk-input" id="q" type="text" name="q" value="<?php echo carClean($search_query); ?>" placeholder="Введите название автомобиля" autocomplete="off">

                        <button type="submit" class="uk-button uk-button-default" style="margin: 0 !important;">
                            Найти
                        </button>
                    </div>
                </form>

                <?php if (!$search_active) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Начните поиск, чтобы увидеть автомобили
                    </div>
                <?php } elseif (empty($found_cars)) { ?>
                    <div class="order-cart-empty uk-margin-small-top uk-text-center">
                        Ничего не найдено по запросу «<?php echo carClean($search_query); ?>»
                    </div>
                <?php } else { ?>
                    <div class="orders-list uk-flex uk-flex-column uk-margin-small-top">
                        <?php foreach ($found_cars as $car) { ?>
                            <?php
                                $car_title_display = trim(carClean($car['brand']) . ' ' . carClean($car['model']));
                                if ($car_title_display === '') {
                                    $car_title_display = carClean($car['title']);
                                }
                            ?>
                            <a class="order-list-item" href="/spravochnik-avto-prosmotr/?idcar=<?php echo (int)$car['id']; ?>">
                                <div class="order-list-item-top">
                                    <div class="order-list-item-title"><?php echo $car_title_display; ?></div>
                                    <?php if (!empty($car['number'])) { ?>
                                        <div class="order-status-badge status-new"><?php echo carClean($car['number']); ?></div>
                                    <?php } ?>
                                </div>

                                <div class="order-list-item-grid">
                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Год выпуска</div>
                                        <div class="order-list-item-value"><?php echo !empty($car['year']) ? carClean($car['year']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">VIN</div>
                                        <div class="order-list-item-value"><?php echo !empty($car['vin']) ? carClean($car['vin']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Владелец</div>
                                        <div class="order-list-item-value"><?php echo !empty($car['owner_title']) ? carClean($car['owner_title']) : '—'; ?></div>
                                    </div>

                                    <div class="order-list-item-cell">
                                        <div class="order-list-item-label">Примечания</div>
                                        <div class="order-list-item-value"><?php echo !empty($car['notes']) ? carClean($car['notes']) : '—'; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php } ?>

            </div>
        </div>

    </div>

<!--МОДАЛЬНОЕ ОКНО НОВЫЙ АВТОМОБИЛЬ-->
<div id="new_car_modal" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <button class="uk-modal-close-default" type="button" uk-close></button>

        <h3 class="uk-card-title uk-text-center">Новый автомобиль</h3>

        <form class="uk-flex uk-flex-column" action="/avtomobil-registratciia/" method="post">

            <div class="uk-margin-small-top">
                <label for="car_brand">Марка</label>
                <input class="uk-input" id="car_brand" type="text" name="car_brand" placeholder="Например: Renault" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_model">Модель</label>
                <input class="uk-input" id="car_model" type="text" name="car_model" placeholder="Например: Sandero" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_number">Гос. номер</label>
                <input class="uk-input" id="car_number" type="text" name="car_number" placeholder="Например: О123ХХ58" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_vin">VIN</label>
                <input class="uk-input" id="car_vin" type="text" name="car_vin" placeholder="17 символов" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_year">Год выпуска</label>
                <input class="uk-input" id="car_year" type="text" name="car_year" placeholder="Например: 2022" autocomplete="off" required>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_owner">Владелец</label>
                <select class="uk-select" id="car_owner" name="car_owner">
                    <option value="0">— Не выбран —</option>
                    <?php foreach ($all_owners as $ownerOption) { ?>
                        <option value="<?php echo (int)$ownerOption->id; ?>"><?php echo carClean($ownerOption->title); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="uk-margin-small-top">
                <label for="car_notes">Примечания</label>
                <textarea class="uk-textarea" id="car_notes" name="car_notes" rows="3" placeholder="Особенности, замечания..."></textarea>
            </div>

            <div class="uk-margin-small-top uk-flex uk-flex-column">
                <button type="submit" class="uk-margin-small-top uk-button uk-button-default" name="save_new_car" value="1">
                    Зарегистрировать
                </button>
            </div>

        </form>
    </div>
</div>
<!--МОДАЛЬНОЕ ОКНО НОВЫЙ АВТОМОБИЛЬ-->

</div>

<?php   
}
?>