# webp-modx

<p><b>converter/start.php</b> - запуст конвертации </p>
<p>Переменные</p>
<ul>
<li>$root - корень для поиска изображений</li>
<li>$limit - лимит конвертаций за итерацию</li>
</ul>
<p>Все конвертированные изображения складываются в /webp/min/ и /web/original/</p>
<p>в /webp/min/ содержатся сильно сжатые версии для первичного отображения на странице</p>
<p>в /web/original/ лежат конвертирвоанные версии без сильного сжатия. 
Эти версии для отображения после загрузки страницы</p>
<p>подмена /webp/min/ на /web/original/ осуществуляюется с помощью JS (converter/webpConverter.js)</p>

<p>converter/picture.php - сниппет вывода изображения</p>
<p>на входе указывается оригинальное изображение (jpg или png), в параметре $file</p>
<p>Сниппет проверяет наличие трех файлов: оригинального изображения, сжатой версии webp и версии webp без сжатия</p>
<p>При наличии всех трех файлов будет отображен тег picture с оригинальным изображением в img и сжатым webp в source</p>

<h2>Как использовать</h2>
<p>Разместить папку converter в корень сайта и запускать  converter/start.php по крону</p>
<p>