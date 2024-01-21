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
    while (floatval($data[$key][$col]) < $lessThan) {
        $key++;
    }
    return $key;
}

function makeData($data, $video, $ffprobe)
{
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
    $timestampcsv = $datacsv[1][0] / 1000000000;
    $timestampvideo = filemtime($video);
    return floatval($timestampvideo - $timestampcsv);
};

if (isset($_POST['download1'])) {

    if (!empty($_FILES["csv"]["tmp_name"]) and !empty($_FILES['video']["tmp_name"])) {
        echo "<pre>";
        print_r($_FILES);
        echo "</pre>";

        $csvarray = readCsv($_FILES["csv"]["tmp_name"]);

        $listcsv = array();
        foreach ($_FILES['video']["name"] as $name) {
            array_push($listcsv, substr($name, 0, strlen($name) - 4));
        };

        echo "<pre>";
        // print_r(makeData($csvarray, 'testfile/00383.Mts', $ffprobe));
        // print_r(searchKey($csvarray, 1, 2));
        echo getStartSeconds($csvarray, $_FILES['video']["tmp_name"][0]);
        echo "</pre>";

        // $archiveFileName = 'baru.zip';
        // $zip = new ZipArchive;
        // if ($zip->open($archiveFileName, ZipArchive::CREATE) === TRUE) {
        //     // Add files to the zip file

        //     $i = 0;
        //     foreach ($listcsv as $name) {
        //         $zip->addFile(makeFileCsv(makeData($csvarray, $_FILES['video']["tmp_name"][$i], $ffprobe), $name));
        //         $i++;
        //     }


        //     // All files are added, so close the zip file.
        //     $zip->close();
        // }

        // header("Content-type: application/zip");
        // header("Content-Disposition: attachment; filename=$archiveFileName");
        // header("Content-length: " . filesize($archiveFileName));
        // header("Pragma: no-cache");
        // header("Expires: 0");
        // readfile("$archiveFileName");

        // unlink($archiveFileName);
        // foreach ($listcsv as $filecsv) {
        //     unlink("$filecsv.csv");
        // }
    }
}



?>
<pre>
    <?php
    // print_r(makeData($array, 1, 1, 3));

    // $timestamp_in_nanoseconds = 1705467139298720300;

    // // Convert nanoseconds to seconds
    // $timestamp_in_seconds = $timestamp_in_nanoseconds / 1000000000;

    // $addGmt7 = 6 * 60 * 60;

    // // Use the date function to format the timestamp
    // $formatted_date = date("Y-m-d H:i:s", $timestamp_in_seconds + $addGmt7);

    // echo $formatted_date;
    // echo "<br>";

    // echo $csvarray[1][0];
    // echo "<br>";

    // echo date("Y-m-d H:i:s", $csvarray[1][0] / 1000000000 + $addGmt7);
    // echo "<br>";
    // echo date("Y-m-d H:i:s", filemtime($csvsource) + $addGmt7);








    // foreach ($listvideo as $video) {
    //     echo "<br>";
    //     echo date("Y-m-d H:i:s", filemtime($video) + $addGmt7);
    //     echo "<br>";
    //     echo getVideoDuration($video, $ffprobe);
    // }

    $listcsv = array('00383', '00384', '00385', '00386');
    $i = 0;
    foreach ($listcsv as $csv) {
        echo "<br>";
        echo $csv;
        echo "<br>";
        echo $i;
        $i++;
    };

    $string = '00383.Mts';
    echo substr($string, 0, strlen($string) - 4);

    echo "<br>";


    // print_r(makeData($csvarray, $listvideo[2], $ffprobe));

    var_dump('C:\xampp\htdocs\gyroflowcsvdivider\testfile');
    var_dump(scandir('D:\testfile\\'));
    var_dump(scandir('../ffmpeg/bin/'));

    var_dump(getVideoDuration('D:\testfile\00383.Mts', $ffprobe));

    // tanda file terakhir
    ?>

</pre>


<form method="post" enctype="multipart/form-data">>
    <input type="file" name="csv" id="csv">
    <input type="file" name="video[]" id="video" multiple>
    <input type="submit" name="download1" value="download" />
</form>