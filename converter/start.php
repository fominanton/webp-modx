<?php
// Подключаем modx
define('MODX_API_MODE', true);
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';


ini_set('max_execution_time', 100);

$base = MODX_BASE_PATH;

//Бинарники для конвертации
$suppliedBinaries = [
    'winnt' => 'cwebp-110-windows-x64.exe', // Microsoft Windows 64bit
    'darwin' => 'cwebp-110-mac-10_15', // MacOSX
    'sunos' => 'cwebp-060-solaris', // Solaris
    'freebsd' => 'cwebp-060-fbsd', // FreeBSD
    'linux' => [
        // Dynamically linked executable.
        // It seems it is slightly faster than the statically linked
        'cwebp-110-linux-x86-64',

        // Statically linked executable
        // It may be that it on some systems works, where the dynamically linked does not (see #196)
        'cwebp-103-linux-x86-64-static',

        // Old executable for systems in case both of the above fails
        'cwebp-061-linux-x86-64'
    ]
];

$root = "/assets/templates/cmrt-nevrolog.ru/img";
$limit = 10;

$data = json_decode(imgList($root, $limit), true);

echo json_encode($data);


if (isset($data['cwebp']) && $data['cwebp'] != 'gd') {
    if ($data['cwebp'] == 'system') {
        $cwebp = '/usr/bin/cwebp';
    } else {
        if (is_file(__DIR__ . DIRECTORY_SEPARATOR . 'Binaries' . DIRECTORY_SEPARATOR . $data['cwebp'])) {
            $cwebp = __DIR__ . DIRECTORY_SEPARATOR . 'Binaries' . DIRECTORY_SEPARATOR . $data['cwebp'];
        }
    }
}

foreach ($data['images'] as $item) {
    if (!is_dir(dirname($item['min']))) {
        mkdir(dirname($item['min']), 0755, true);
    }
    if (!is_dir(dirname($item['webp']))) {
        mkdir(dirname($item['webp']), 0755, true);
    }
    convert($item['original'], $item['webp'], $item['min'], $item['original_path']);
}

function convert($file, $outfile, $outfile2, $path)
{

    global $data;
    global $cwebp;

    $param_png = "-metadata none -quiet -pass 10 -m 6 -alpha_q 100 -mt -alpha_filter best -alpha_method 1 -q 100 -low_memory";
    $param_png_min = "-metadata none -quiet -pass 10 -m 6 -alpha_q 40 -mt -alpha_filter best -alpha_method 1 -q 40 -low_memory";

    $param_jpeg = "-metadata none -quiet -pass 10 -m 6 -mt -q 100 -low_memory";
    $param_jpeg_min = "-metadata none -quiet -pass 10 -m 6 -mt -q 40 -low_memory";

    $imagetype = exif_imagetype($file);
    $gd_support = check_gd();

    $output = [];


    if (is_file($file)) {


        if ($imagetype == IMAGETYPE_JPEG || $imagetype == 2) {

            if ($data['cwebp'] != 'gd' || true) {
                exec($cwebp . ' ' . $param_jpeg . ' "' . $file . '" -o "' . $outfile . '" 2>&1', $output, $return_var);
                exec($cwebp . ' ' . $param_jpeg_min . ' "' . $file . '" -o "' . $outfile2 . '" 2>&1', $output, $return_var);

            }


        }

        if ($imagetype == IMAGETYPE_PNG || $imagetype == 3) {

            if ($data['cwebp'] != 'gd' || true) {
                exec($cwebp . ' ' . $param_png . ' "' . $file . '" -o "' . $outfile . '" 2>&1', $output, $return_var);
                exec($cwebp . ' ' . $param_png_min . ' "' . $file . '" -o "' . $outfile2 . '" 2>&1', $output, $return_var);

            }


        }
    }


    return $output;
}

function check_gd()
{ // Prior to GD library version 2.2.5, WEBP does not have alpha channel support
    if (extension_loaded('gd') && function_exists('gd_info')) {
        $gd = gd_info();

        if (!in_array('GD Version', $gd)) $gd['GD Version'] = '0.0.0';

        preg_match('/\\d+\\.\\d+(?:\\.\\d+)?/', $gd['GD Version'], $matches);
        $gd['Ver'] = $matches[0];

        if (version_compare($gd['Ver'], '2.2.5') >= 0) {
            $gd['WebP Alpha Channel Support'] = 1;
        } else {
            $gd['WebP Alpha Channel Support'] = 1;
        }


        if (!in_array('WebP Support', $gd)) $gd['WebP Support'] = 0;
        if (!in_array('JPEG Support', $gd)) $gd['JPEG Support'] = 0;
        if (!in_array('PNG Support', $gd)) $gd['PNG Support'] = 0;


        return $gd;
    } else {
        return false;
    }
}


function getBinary()
{ // Detect os and select converter command line tool
    global $suppliedBinaries;

    $gd_support = check_gd();
    $gd = false;


    if (
        $gd_support['WebP Support'] &&
        $gd_support['WebP Alpha Channel Support'] &&
        $gd_support['PNG Support']
    ) {
        $gd = true;
    }


    // Check disabled exec function
    $disablefunc = explode(",", str_replace(" ", "", @ini_get("disable_functions")));
    if (!is_callable("exec") || in_array("exec", $disablefunc)) {
        if ($gd) return "gd";

        return false;
    }


    $cwebp_path = __DIR__ . DIRECTORY_SEPARATOR . 'Binaries' . DIRECTORY_SEPARATOR;

    $output = [];

    if (!isset($suppliedBinaries[strtolower(PHP_OS)])) {
        if ($gd) return "gd";
        return false;

    }

    $bin = $suppliedBinaries[strtolower(PHP_OS)]; // Select OS

    if (is_array($bin)) { // Check binary

        foreach ($bin as $b) {
            if (is_file($cwebp_path . $b)) {
                if (!is_executable($cwebp_path . $b)) chmod($cwebp_path . $b, 0755);

                $output[] = "<hr />" . $cwebp_path . $b;
                exec($cwebp_path . $b . ' 2>&1', $output, $return_var);
                //  var_dump($return_var);

                if ($return_var == 0) {

                    $cwebp = $b;
                    break;
                }
            }
        }
    } else {
        if (is_file($cwebp_path . $bin)) {
            if (strtolower(PHP_OS) != 'winnt' && !is_executable($cwebp_path . $bin)) chmod($cwebp_path . $bin, 0755);

            $output[] = $cwebp_path . $bin;
            exec($cwebp_path . $bin . ' 2>&1', $output, $return_var);
            if ($return_var == 0) {
                $cwebp = $bin;
            }
        }
    }

    if (!isset($cwebp)) {
        var_dump($cwebp_path);;
        if (strtolower(PHP_OS) == 'linux' && is_file('/usr/bin/cwebp')) {
            $output[] = '/usr/bin/cwebp';
            exec('/usr/bin/cwebp' . ' 2>&1', $output, $return_var);
            if ($return_var == 0) {
                return 'system';
            }
        }


        if (is_numeric($return_var)) {
            if ($gd) return "gd";
            return false;

        } else {
            if ($gd) return "gd";
            return false;

        }

    }
    // Download bin file from https://developers.google.com/speed/webp/docs/precompiled

    return $cwebp;
}

function showTree($folder)
{

    $out = [];
    $files = scandir($folder);
    foreach ($files as $file) {
        if (($file == '.') || ($file == '..')) continue;
        $f0 = $folder . '/' . $file;
        if (is_dir($f0)) {
            $out = array_merge(showTree($f0), $out);
        } else {

            array_push($out, $f0);

        }
    }
    return $out;
}


function imgList($root, $limit)
{

    global $base;
    $images = [];
    $cwebp = getBinary();


    $images = showTree($base . $root);


    $images_f = [];
    $count = 0;
    $convert_count = 0;
    foreach ($images as $key => $file) {


        $type = explode(".", $file);
        $type = $type[count($type) - 1];

        if ($type !== 'png' && $type !== "jpg" && $type !== "jpg") {
            continue;
        }
        $filename = explode('/', $file);
        $filename = $filename[count($filename) - 1];

        $filepath = str_replace($base, "", $file);
        $filepath = explode("/", $filepath);
        unset($filepath[count($filepath) - 1]);
        $filepath = implode("/", $filepath);


        $outfile_webp = $base . "webp/original/$filepath/$filename.webp";
        $outfile_min = $base . "webp/min/$filepath/$filename.webp";


        if (filemtime($file) > filemtime($outfile_min)) {
            if ($count <= $limit) {
                array_push($images_f, ["original" => $file, "webp" => $outfile_webp, "min" => $outfile_min, "original_path" => $filepath]);
                $count++;
            }


        } else {
            $convert_count++;
        }


    }


    $ret = json_encode([
        'images' => $images_f,
        'count' => count($images),
        "convert" => $convert_count,
        'cwebp' => $cwebp
    ], JSON_UNESCAPED_UNICODE);


    return $ret;
}