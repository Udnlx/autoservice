<?php

namespace ProcessWire;

require_once '../index.php';

ini_set('max_execution_time', 0);
ini_set('memory_limit', '4096M');

$array = array(
array('Белинков А.','Россия и черт','Спб.ж.Звезда','2000 г.','288','',''),
array('Бордонов Жорж С/С в 3 т.','том 1 Агранты.Золотые копи Вильльма','М.Спб.Прибой','1993 г.','544','',''),
array('Бордонов Жорж С/С в 3 т.','том 2 Копья Иерасул.Реквием по','М.Спб.Прибой','1993 г.','432','',''),
);

$books = '';
foreach ($array as $items) {
	if ($items[0] == '') {
        $items[0] = 'Без автора';
    }
    if ($items[1] == '') {
        $items[1] = 'Без названия';
    }
	$pages->add('book_itm', 1016 , [
	'title' => $items[0] . ' - ' . $items[1],
	'author_book' => $items[0],
	'name_book' => $items[1],
	'publisher_book' => $items[2],
	'year_book' => $items[3],
	'page_book' => $items[4],
	'location_book' => $items[5],
	'note_book' => $items[6]
	]);
	$books .= $items[0] . ' - ' . $items[1] . ' - ' . $items[2] . ' - ' . $items[3] . ' - ' . $items[4] . ' - ' . $items[5] . ' - ' . $items[6] . '<br>';
}



?>







<div id="content">
	<div class="uk-001">
		<div class="uk-001-content uk-margin-auto" style="min-height: 400px;">
			<h1>ADD BOOKS</h1>
			<?php echo $books ?>
		</div>
	</div>
</div>