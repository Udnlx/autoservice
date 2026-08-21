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
    	<h1 class="uk-heading-hero uk-text-center">Новый заказ-наряд</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title uk-text-center">Нет прав на эту страницу, потеряна сессия или точка, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>
<?php    
} else {
?>

<div id="content">
	<h1 class="uk-margin-remove uk-heading-hero uk-text-center">Новый заказ-наряд</h1>
	<div>

        <div>
            <div class="pagemenu uk-width-1-1 uk-flex">
                <a class="menu-link" href="/">На главную</a>
            </div>
        </div>

        <div>
            <div class="uk-card uk-card-default uk-card-body uk-flex uk-flex-column">
                <form class="uk-flex uk-flex-column" id="select_seat" action="/zakaz-registratciia/" method="post">
                    <label>Дата и оператор заказа</label>
                    <div class="uk-margin-small-top">
                        <input class="uk-input" id="selected_date" type="text" name="selected_date" value="<?php echo $today; ?>" readonly>
                    </div>
                    <div class="uk-margin-small-top">
                        <input class="uk-input" id="selected_worker" type="text" name="selected_worker" value="<?php echo $operator; ?>" readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="client">Клиент</label>
                        <select class="uk-select" id="client" name="client" required>
                            <option value="" disabled selected>Выберите клиента</option>
                            <option value="Клиент 1">Клиент 1</option>
                            <option value="Клиент 2">Клиент 2</option>
                            <option value="Клиент 3">Клиент 3</option>
                        </select>
                    </div>
                    <div class="uk-margin-small-top">
                        <label for="car">Автомобиль</label>
                        <select class="uk-select" id="car" name="car" required>
                            <option value="" disabled selected>Выберите автомобиль</option>
                            <option value="Авто 1">Авто 1</option>
                            <option value="Авто 2">Авто 2</option>
                            <option value="Авто 3">Авто 3</option>
                        </select>
                    </div>

                    <!--КОРЗИНА РАБОТ-->
                    <div class="uk-margin-small-top">
                        <label for="work_select">Работы</label>

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                            <select class="uk-select" id="work_select">
                                <option value="" disabled selected>Выберите работу</option>
                                <option value="Замена масла" data-price="1500">Замена масла — 1500 ₽</option>
                                <option value="Замена фильтра" data-price="700">Замена фильтра — 700 ₽</option>
                                <option value="Диагностика" data-price="1000">Диагностика — 1000 ₽</option>
                                <option value="Шиномонтаж" data-price="2500">Шиномонтаж — 2500 ₽</option>
                                <option value="Развал-схождение" data-price="3000">Развал-схождение — 3000 ₽</option>
                            </select>

                            <button type="button" class="uk-button uk-button-default" id="add_work">
                                Добавить
                            </button>
                        </div>
                    </div>

                    <div class="uk-margin-small-top">
                        <div class="order-cart-box works-cart-box">
                            <div class="order-cart-header">
                                <div class="order-cart-header-title">
                                    <span>🛠</span>
                                    <span>Корзина работ</span>
                                </div>

                                <span class="uk-badge order-cart-badge-work">Работы</span>
                            </div>

                            <div id="works_cart" class="order-cart-body">
                                <div id="works_empty" class="order-cart-empty">
                                    Работы пока не выбраны
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--КОРЗИНА РАБОТ-->

                    <!--КОРЗИНА ЗАПЧАСТЕЙ-->
                    <div class="uk-margin-small-top">
                        <label for="part_select">Запчасти</label>

                        <div class="uk-flex uk-flex-middle order-add-row" style="gap: 10px;">
                            <select class="uk-select" id="part_select">
                                <option value="" disabled selected>Выберите запчасть</option>
                                <option value="Масляный фильтр" data-price="900">Масляный фильтр — 900 ₽</option>
                                <option value="Воздушный фильтр" data-price="1200">Воздушный фильтр — 1200 ₽</option>
                                <option value="Салонный фильтр" data-price="1100">Салонный фильтр — 1100 ₽</option>
                                <option value="Масло 5W-40" data-price="3500">Масло 5W-40 — 3500 ₽</option>
                                <option value="Свечи зажигания" data-price="2400">Свечи зажигания — 2400 ₽</option>
                            </select>

                            <button type="button" class="uk-button uk-button-default" id="add_part">
                                Добавить
                            </button>
                        </div>
                    </div>

                    <div class="uk-margin-small-top">
                        <div class="order-cart-box parts-cart-box">
                            <div class="order-cart-header">
                                <div class="order-cart-header-title">
                                    <span>⚙️</span>
                                    <span>Корзина запчастей</span>
                                </div>

                                <span class="uk-badge order-cart-badge-part">Запчасти</span>
                            </div>

                            <div id="parts_cart" class="order-cart-body">
                                <div id="parts_empty" class="order-cart-empty">
                                    Запчасти пока не выбраны
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--КОРЗИНА ЗАПЧАСТЕЙ-->

                    <div class="uk-margin-small-top">
                        <label for="works_price">Стоимость работ</label>
                        <input class="uk-input" id="works_price" type="text" name="works_price" value="0" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="parts_price">Стоимость запчастей</label>
                        <input class="uk-input" id="parts_price" type="text" name="parts_price" value="0" autocomplete="off" required readonly>
                    </div>

                    <div class="uk-margin-small-top">
                        <label for="total_price">Общая стоимость</label>
                        <input class="uk-input" id="total_price" type="text" name="total_price" value="0" autocomplete="off" required readonly>
                    </div>
                    <div class="uk-margin-small-top">
                        <label for="payment_type">Вид платежа</label>
                        <select class="uk-select" id="payment_type" name="payment_type" required>
                            <option value="Наличный расчет">Наличный расчет</option>
                            <option value="Безналичный расчет">Безналичный расчет</option>
                        </select>
                    </div>
                    
                    <div class="uk-margin-small-top uk-flex uk-flex-column">
                        <button class="uk-margin-small-top uk-button uk-button-default" type="submit">Зарегистрировать</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

<?php   
}
?>