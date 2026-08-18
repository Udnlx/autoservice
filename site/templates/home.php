<?php namespace ProcessWire;

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>

    <div id="content" style="max-width: 700px;">
    	<h1 class="uk-heading-hero uk-text-center">Автосервис</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title">Сессия потеряна, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>

<?php    
} else {
$menu = '';
if ($operator == 'admin') {
    $menu = '
        <a class="uk-margin-small uk-button uk-button-default" href="/zakaz-novyi/">Новый заказ</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Движение</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Автомобили</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Клиенты</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Склад</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Справочники</a>
    ';
}
?>

<div id="content" style="max-width: 700px;">
	<h1 class="uk-heading-hero uk-text-center">Автосервис</h1>
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <h3 class="uk-card-title">Выберите действие</h3>
        <?php echo $menu; ?>
    </div>
</div>

<?php   
}
?>