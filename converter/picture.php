<?php
/*
 * Входные параметры:
 * $file - файл избражения
 * $class - классы
 * $css - style атрибут
 * $alt - альт изображения
 */

if (file_exists($file)) {


    if (file_exists("webp/original/$file.webp")) {
        $original_img = "data-srcset-full='/webp/original/$file.webp'";
    }

    if (file_exists("webp/min/$file.webp")) {

        $webp = "<source $original_img srcset='/webp/min/$file.webp' type='image/webp'>";
    }

    isset($class) ? $class = "class ='$class'" : true;
    isset($css) ? $css = "style ='$css'" : true;
    isset($alt) ? $alt = "alt='$alt'" : true;

    echo "  <picture >
            $webp
            <img src='/$file' $class $css $alt loading='lazy'>
        </picture>";

}