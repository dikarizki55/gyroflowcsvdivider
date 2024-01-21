<?php
require 'vendor/autoload.php';

$ffprobe = FFMpeg\FFProbe::create(
    array(
        'ffmpeg.binaries'  => 'ffmpeg/bin/ffmpeg',
        'timeout'          => 3600, // The timeout for the underlying process
        'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
    )
);

function getVideoDuration($source, $ffprobe)
{
    $duration = $ffprobe
        ->format($source) // extracts file informations
        ->get('duration');             // returns the duration property

    return $duration;
}

function readCsv($filename)
{
    $csvsource = $filename;
    $file = fopen($csvsource, "r");
    while (!feof($file)) {
        $data[] = fgetcsv($file);
    }
    fclose($file);

    return $data;
}


// searchKey, search array index inside array
function searchKey($data, $col, $lessThan)
{
    $key = 1;
    if (is_array($data)) {
        while (floatval($data[$key][$col]) < $lessThan) {
            $key++;
        }
        return $key;
    } else {
        var_dump("cant read array of search keys");
    }
}

function makeData($video, $ffprobe)
{
    $data = csvSelector(arrayfilepath('csv'), filemtime($video), 0, $video);

    if (is_array($data)) {
        $startLessThan = getStartSeconds($data, $video);
        $duration = getVideoDuration($video, $ffprobe);

        $start = searchKey($data, 1, $startLessThan);
        $end = searchKey($data, 1, $startLessThan + $duration);
        $length = $end - $start;

        $slicedData = array_slice($data, $start, $length);
        $newData = array();
        foreach ($slicedData as $dataslice) {
            $modified = $dataslice;
            $modified[1] = strval(floatval($modified[1]) - $startLessThan);
            array_push($newData, $modified);
        }

        array_unshift($newData, $data[0]);
        return $newData;
    } else {
        var_dump("data array error dari make data");
    }
}

function makeFileCsv($data, $fileName)
{
    $file = "$fileName.csv";
    $txt = fopen($file, "w") or die("Unable to open file!");
    foreach ($data as $line) {
        fputcsv($txt, $line);
    }
    fclose($txt);
    return $file;
}



function getStartSeconds($datacsv, $video)
{
    if (is_array($datacsv)) {
        $timestampcsv = $datacsv[1][0] / 1000000000;
        $timestampvideo = filemtime($video);
        return floatval($timestampvideo - $timestampcsv);
    } else {
        var_dump("cant read array to get time");
    }
};

function sliceScandir(string $inputname)
{
    return array_slice(scandir($_POST[$inputname]), 2, count(scandir($_POST[$inputname])) - 2);
}

function arrayfilepath($arrayname)
{
    $array = sliceScandir($arrayname);
    $newarray = array();
    foreach ($array as $item) {
        array_push($newarray, $_POST[$arrayname] . "\\" . $item);
    };
    return $newarray;
}

function csvSelector($array, $filemtime, $i, $video)
{
    $videomtime = floatval($filemtime);

    $csvfile = readCsv($array[$i]);
    $csvstart = floatval($csvfile[1][0] / 1000000000);
    $csvend = floatval($csvfile[count($csvfile) - 2][0] / 1000000000);

    if ($csvstart < $videomtime and $videomtime < $csvend) {
        return readCsv($array[$i]);
    } else {
        if (array_key_exists($i + 1, $array)) {
            csvSelector($array, $videomtime, $i + 1, $video);
        } else {
            var_dump("file csv tidak tersedia untuk $video ini");
        }
    }
}

if (isset($_POST['download1'])) {

    $listcsv = array();
    foreach (sliceScandir('video') as $name) {
        array_push($listcsv, substr($name, 0, strlen($name) - 4));
    };

    $validkey = array();
    for ($i = 0; $i < count($listcsv); $i++) {
        if (is_array(makeData(arrayfilepath('video')[$i], $ffprobe))) {
            array_push($validkey, $i);
        }
    }

    unlink("baru.zip");
    $archiveFileName = 'baru.zip';
    $zip = new ZipArchive;
    if ($zip->open($archiveFileName, ZipArchive::CREATE) === TRUE) {
        // Add files to the zip file

        foreach ($validkey as $i) {
            $zip->addFile(makeFileCsv(makeData(arrayfilepath('video')[$i], $ffprobe), $listcsv[$i]));
        }
        $zip->close();
    }


    // header("Content-type: application/zip");
    // header("Content-Transfer-Encoding: Binary");
    // header("Content-Disposition: attachment; filename=$archiveFileName");
    // header("Content-length: " . filesize($archiveFileName));
    // header("Pragma: no-cache");
    // header("Expires: 0");
    // readfile("$archiveFileName");

    foreach ($listcsv as $filecsv) {
        unlink("$filecsv.csv");
    }
    header('Location: ' . 'baru.zip');
}

?>

<form method="post" enctype="multipart/form-data">>
    <label for="csv">Input Csv Folder</label>
    <input type="text" id="csv" name="csv" value="D:\testfile\csv">
    <br>
    <label for="csv">Input Video Folder</label>
    <input type="text" id="video" name="video" value="D:\testfile\video">
    <br>
    <input type="submit" name="download1" value="download" />
</form>