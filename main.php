<?php
require_once "vendor/autoload.php";
require_once "credentials.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

$connectionString = $credentialString;
$containers = $containersArray;

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
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$argv[2])) {
            $date = $argv[2];
        } else {
            echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
            exit;
        }
    } else {
        echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
    }

    if (isset($argv[3]) && !empty($argv[3])) {
        if (preg_match("/^(2[0-3]|[01][0-9]):[0-5][0-9]$/", $argv[3])) {
            $startTime = $argv[3];
        } else {
            echo 'StartTime Argument must be set and with format (HH:MM).' .PHP_EOL;
            exit;
        }
    } else {
        echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
    }

    if (isset($argv[4]) && !empty($argv[4])) {
        if (preg_match("/^(2[0-3]|[01][0-9]):[0-5][0-9]$/", $argv[4])) {
            $endTime = $argv[4];
        } else {
            echo 'StartTime Argument must be set and with format (HH:MM).' .PHP_EOL;
            exit;
        }
    } else {
        echo 'Date Argument must be set and with format (yyyy-mm-dd).' .PHP_EOL;
    }
} else {
    echo 'TO RUN THIS SCRIPT YOU MUST SET THE ARGUMENTS' .PHP_EOL;
    exit;
}

$blobClient = BlobRestProxy::createBlobService($connectionString);
$count = 0;
foreach ($containers as $container) {
    $count += downloadBlobsSample($blobClient, $container, $date, $app, $startTime, $endTime);
}

echo 'Total Files number: ' .$count.PHP_EOL;

function downloadBlobsSample($blobClient, $container, $date, $app, $startTime = null, $endTime = null)
{
    $count = 0;
    try {
        // List blobs.
        $dateExploded = explode('-',$date);
        $prefix = strtolower($app).'/'.$dateExploded[0].'/'.$dateExploded[1];
        $listBlobsOptions = new ListBlobsOptions();
        $listBlobsOptions->setPrefix($prefix);
        $listBlobsOptions->setMaxResults(100);
        do {
            //global $myContainer;
            $blob_list = $blobClient->listBlobs($container, $listBlobsOptions);
            foreach ($blob_list->getBlobs() as $blob) {
                if (strpos($blob->getName(), $date) !== false) {
                    $pathinfo = pathinfo($blob->getName());
                    $timeInfo = explode("-",$pathinfo['extension']);
                    $delimitersTimeInfo = count($timeInfo);
                    $dateStartTime = strtotime($date . " " .$startTime);
                    $controlStartTime = date('Y-m-d H:i', $dateStartTime);
                    $dateEndTime = strtotime($date . " " .$endTime) + 60*60;
                    $controlEndTime = date('Y-m-d H:i', $dateEndTime);

                    $dayLogFile = intval($timeInfo[$delimitersTimeInfo - 3]);
                    $monthLogFile = intval($timeInfo[$delimitersTimeInfo - 4]);
                    $yearLogFile = intval($timeInfo[$delimitersTimeInfo - 5]);
                    $preLogHourFile = intval($timeInfo[$delimitersTimeInfo - 2]) - 1;
                    $logHourFile = ($preLogHourFile < 0) ? 23 : $preLogHourFile;
                    $logMinuteFile = intval($timeInfo[$delimitersTimeInfo - 1]);

                    $fileStringTime = $yearLogFile . "-" . sprintf("%02d", $monthLogFile) . "-" . sprintf("%02d", $dayLogFile) . " " . sprintf("%02d", $logHourFile) . ":" . sprintf("%02d", $logMinuteFile);
                    $fileTime = ($preLogHourFile < 0) ? strtotime('-1 day', strtotime($fileStringTime)) : strtotime($fileStringTime);
                    $controlFileTime = date('Y-m-d H:i', $fileTime);
                    if ($controlFileTime > $controlStartTime && $controlFileTime < $controlEndTime) {
                        $path = 'logs/' . $pathinfo['dirname'] .'/'. $dateExploded[2] . '/' . $startTime . '-' . $endTime . '/' . $container;
                        if (!is_dir($path)) {
                            // dir doesn't exist, make it
                            mkdir($path, 0755, true);
                        }
                        $filename = $path . "/" .  $pathinfo['filename'];
                        $getBlobResult = $blobClient->getBlob($container, $blob->getName());
                        if (!file_exists($filename)) {
                            file_put_contents($filename, $getBlobResult->getContentStream());
                            echo $filename.PHP_EOL;
                            $count++;
                        } else {
                            file_put_contents($filename, $getBlobResult->getContentStream(), FILE_APPEND);
                        }
                    }
                }
            }
            $listBlobsOptions->setContinuationToken($blob_list->getContinuationToken());
        } while ($blob_list->getContinuationToken());
        echo PHP_EOL;
    } catch (ServiceException $e) {
        $code = $e->getCode();
        $error_message = $e->getMessage();
        echo $code.": ".$error_message.PHP_EOL;
    }
    return $count;
}

