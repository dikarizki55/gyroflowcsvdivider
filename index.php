<?php
$file = fopen("gyroscope.csv", "r");
// while (!feof($file)) {
//     print_r(fgetcsv($file));
// }

while (!feof($file)) {
    $array[] = fgetcsv($file);
}
fclose($file);

// searchKey, search array index inside array
function searchKey($data, $col, $lessThan)
{
    $key = 1;
    while (floatval($data[$key][$col]) < $lessThan) {
        $key++;
    }
    return $key;
}

function makeData($data, $col, $startLessThan, $endLessThan)
{
    $start = searchKey($data, $col, $startLessThan);
    $end = searchKey($data, $col, $endLessThan);
    $length = $end - $start;

    $slicedData = array_slice($data, $start, $length);
    $newData = array();
    foreach ($slicedData as $dataslice) {
        $modified = $dataslice;
        $modified[$col] = strval(floatval($modified[$col]) - $startLessThan);
        array_push($newData, $modified);
    }

    array_unshift($newData, $data[0]);
    return $newData;
}

function makeFileCsv($data, $fileName)
{
    $file = $fileName;
    $txt = fopen($file, "w") or die("Unable to open file!");
    foreach ($data as $line) {
        fputcsv($txt, $line);
    }
    fclose($txt);
    return $file;
}

if (isset($_POST['download1'])) {

    $csvbaru = array();
    $i = 0;
    while ($i < 3) {
        array_push($csvbaru, "baru$i.csv");
        $i++;
    }

    $archiveFileName = 'baru.zip';
    $zip = new ZipArchive;
    if ($zip->open($archiveFileName, ZipArchive::CREATE) === TRUE) {
        // Add files to the zip file

        foreach ($csvbaru as $filecsv) {
            $zip->addFile(makeFileCsv(makeData($array, 1, 1, 3), $filecsv));
        }


        // All files are added, so close the zip file.
        $zip->close();
    }

    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$archiveFileName");
    header("Content-length: " . filesize($archiveFileName));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile("$archiveFileName");

    unlink($archiveFileName);
    foreach ($csvbaru as $filecsv) {
        unlink($filecsv);
    }
}



?>
<pre>
    <?php
    // print_r(makeData($array, 1, 1, 3));
    ?>
</pre>


<form method="post">
    <input type="submit" name="download1" value="download" />
</form>