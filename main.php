<?php
require_once "vendor/autoload.php";
require_once "credentials.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

$connectionString = $credentialString;
$containers = $containersArray;

function downloadBlobsSample($blobClient, $container, $app, $fileLogName, $dateStartTime, $dateEndTime)
{
    $creatingFile = False;
    // List blobs.
    $containerSearch = "uwproozndwa0" . $container;
    $dateStartFile = clone $dateStartTime;
    $dateStartFile->modify('+1 hour');
    $dateEndFile = clone $dateEndTime;
    $dateEndFile->modify('+1 hour');
    $prefix = strtolower($app) . '/' . $dateStartFile->format('Y') . '/' . $dateStartFile->format('m');
    $prefix .= "/" . $fileLogName;
    $listBlobsOptions = new ListBlobsOptions();
    $listBlobsOptions->setPrefix($prefix);
    $listBlobsOptions->setMaxResults(100);
    $tempPath = 'logs/tmp/';
    if (!is_dir($tempPath)) {
        mkdir($tempPath, 0777, true);
    }
    $path = 'logs/' . strtolower($app) .'/'. $dateStartTime->format('Y') . '/' . $dateStartTime->format('m') . '/' .
        $dateStartTime->format('d') . '/' . $dateStartTime->format('H:i') . '-' . $dateEndTime->format('H:i') . '/frontal_0' . $container . '/';
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    $fileName = $path . '0' . $container . '_' . $fileLogName . '.log';
    do {
        //global $myContainer;
        $blob_list = $blobClient->listBlobs($containerSearch, $listBlobsOptions);
        /** @var \MicrosoftAzure\Storage\Blob\Models\Blob $blob */
        foreach ($blob_list->getBlobs() as $blob) {
            $name = $blob->getName();
            /** @var \MicrosoftAzure\Storage\Blob\Models\BlobProperties $properties */
            $properties = $blob->getProperties();
            $lastModifiedStart = $properties->getLastModified();
            $lastModifiedEnd = clone $lastModifiedStart;
            $lastModifiedStart->modify('+1 hour');
            if ($dateStartTime->format('Y-m-d H:i:s') <= $lastModifiedStart->format('Y-m-d H:i:s') &&
                $dateEndTime->format('Y-m-d H:i:s') >= $lastModifiedEnd->format('Y-m-d H:i:s')){
                $pathinfo = pathinfo($name);
                $getBlobResult = $blobClient->getBlob($containerSearch, $blob->getName());

                $tempFile = $tempPath . $pathinfo['basename'] . '.log';
                file_put_contents($tempFile, $getBlobResult->getContentStream());
                $handle = fopen($tempFile,'r') or die ('File opening failed');

                while (!feof($handle)) {
                    $dd = fgets($handle);
                    $arr = preg_split('/\h*[][]/', $dd, -1, PREG_SPLIT_NO_EMPTY);
                    if (is_array($arr) && count($arr) > 1){
                        $dateLog = new \DateTime ($arr[0]);
                        if ($dateStartTime->format('Y-m-d H:i:s') <= $dateLog->format('Y-m-d H:i:s') &&
                            $dateEndTime->format('Y-m-d H:i:s') >= $dateLog->format('Y-m-d H:i:s')){
                            if (!$creatingFile){
                                $creatingFile = TRUE;
                            }
                            @file_put_contents($fileName, $dd, FILE_APPEND);
                        }
                    }
                }
                fclose($handle);
                unlink($tempFile);
            }
        }
        $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
    } while ($blob_list->getContinuationToken());
    if ($creatingFile) {
        echo 'FILE: ' . $fileName . ' CREATED!!' . PHP_EOL;
    }
    return $creatingFile;
}

if (isset($argv) && count($argv) > 1){
    if (isset($argv[1]) && !empty($argv[1])) {
        if ($argv[1] === "ozone" ||  $argv[1] === "ocp") {
            $app = $argv[1];
        } else {
            echo 'App Argument can only be "ozone" or "ocp".' .PHP_EOL;
            exit;
        }
    } else {
        echo 'App Argument can only be "ozone" or "ocp".' .PHP_EOL;
    }

    if (isset($argv[2]) && !empty($argv[2])) {
        $dateStartTime = new \DateTime($argv[2]);
    } else {
        echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
    }

    if (isset($argv[3]) && !empty($argv[3])) {
        $dateEndTime = new \DateTime($argv[3]);
    } else {
        echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
    }

    if (isset($argv[4]) && !empty($argv[4])) {
        $fileLogNameParam = $argv[4];
    }
} else {
    echo 'TO RUN THIS SCRIPT YOU MUST SET THE ARGUMENTS' .PHP_EOL;
    exit;
}

$blobClient = BlobRestProxy::createBlobService($connectionString);
$count = 0;
foreach ($containers as $container) {
    echo '*** FRONTAL 0' . $container . ' ****' . PHP_EOL;
    if (isset($fileLogNameParam)) {
        if (downloadBlobsSample($blobClient, $container, $app, $fileLogNameParam, $dateStartTime, $dateEndTime)){
            $count++;
        }
    } else {
        foreach ($fileNamesArray[$app] as $fileLogName){
            if (downloadBlobsSample($blobClient, $container, $app, $fileLogName, $dateStartTime, $dateEndTime)){
                $count++;
            }
        }
    }
    echo PHP_EOL;
}

echo 'Total Files number: ' .$count.PHP_EOL;

