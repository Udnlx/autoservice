<?php namespace ProcessWire;

if(isset($_SESSION['operator'])){
    $operator = $_SESSION['operator'];
} else {
    $operator = 'no_operator';
}

if ($operator == 'no_operator') {
?>

    <div id="content" style="max-width: 700px;">
    	<h1 class="uk-heading-hero uk-text-center">Касса</h1>
        <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
            <h3 class="uk-card-title">Сессия потеряна, перезайти</h3>
            <a class="uk-margin-small uk-button uk-button-default" href="/login/">Перезайти</a>
        </div>
    </div>

<?php    
} else {
    $menu = '
        <a class="uk-margin-small uk-button uk-button-default" href="">Наличный расчет</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Безналичный расчет</a>
        <a class="uk-margin-small uk-button uk-button-default" href="">Карта</a>
        <a class="uk-margin-small uk-button uk-button-default" href="/">На главную</a>
    ';
?>

<div id="content" style="max-width: 700px;">
	<h1 class="uk-heading-hero uk-text-center">Касса</h1>
    <div class="uk-card uk-card-default uk-card-body uk-width-1-1 uk-flex uk-flex-column">
        <h3 class="uk-card-title">Выберите кассу</h3>
        <?php echo $menu; ?>
    </div>
</div>

<?php   
}
?>