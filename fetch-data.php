<?php
ini_set('max_execution_time', -1); //300 seconds = 5 minutes
//error_reporting(E_ALL);

function Zip($source, $destination, $include_dir = false)
{

    if (!extension_loaded('zip') || !file_exists($source)) {
        return false;
    }

    if (file_exists($destination)) {
        unlink ($destination);
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    $source = str_replace('\\', '/', realpath($source));

    if (is_dir($source) === true)
    {

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

        if ($include_dir) {

            $arr = explode("/",$source);
            $maindir = $arr[count($arr)- 1];

            $source = "";
            for ($i=0; $i < count($arr) - 1; $i++) {
                $source .= '/' . $arr[$i];
            }

            $source = substr($source, 1);

            $zip->addEmptyDir($maindir);

        }

        foreach ($files as $file)
        {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            if( in_array(substr($file, strrpos($file, '/')+1), array('.', '..')) )
                continue;

            $file = realpath($file);

            if (is_dir($file) === true)
            {
                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
            }
            else if (is_file($file) === true)
            {
                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        }
    }
    else if (is_file($source) === true)
    {
        $zip->addFromString(basename($source), file_get_contents($source));
    }

    return $zip->close();
}

$response='';
$yourName = !empty($_GET['yourName']) ? $_GET['yourName'] : $response.='Please supply your name<br>';
$queryName = !empty($_GET['queryName']) ? $_GET['queryName'] : $response.='Please supply a dataset name<br>';
$queryUrl = !empty($_GET['queryUrl']) ? $_GET['queryUrl'] : $response.='Please enter a search url<br>';
$recordCount = !empty($_GET['recordCount']) ? '%5Bsize%5D='.$_GET['recordCount'] : $response.='Please supply a record count<br>';

if ($response != '') {
  http_response_code(500);
  exit($response);
}

$ID = hash('md5',$yourName.time());

//while (($request=fgetcsv($requests)) !== false) {
//  print_r($request);
//  echo sprintf('<tr><td>%s</td><td>%s</td><td><a href="%s" target="_blank">View records</a></td><td>%s</td><td>%s</td></tr>',$request[1],$request[2],$request[3],$request[4],$request[6]);
//}

// $cvurl="http://collection.sciencemuseum.org.uk/search/objects?q=lacock&filter%5Bhas_image%5D=true&filter%5Bimage_license%5D=true&filter%5Bdate%5Bfrom%5D%5D=1800&filter%5Bdate%5Bto%5D%5D=1920&page%5Btype%5D=search";

//echo '<pre>'.$queryUrl.'<br>';
$cvurl = preg_replace("/%5Bsize%5D=(\d+)/", $recordCount, $queryUrl);
//$cvurl="http://collection.sciencemuseum.org.uk/objects/co8101483/opening-of-the-park-and-recreation-ground-book";
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $cvurl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Accept: application/vnd.api+json"));
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

$json = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
// Check for errors and display the error message
if($errno = curl_errno($curl)) {
  $error_message = curl_strerror($errno);
  echo "cURL error ({$errno}):\n {$error_message}";
}
curl_close($curl);


$response=json_decode($json,TRUE);
$totalResults = $response['meta']['count']['type']['all'];

// create a directory to store results in, based on md5 hash ID
$directory = 'downloads/'.$ID.'/';
mkdir($directory, 0777, true);
mkdir($directory.'img_l', 0777, true);
mkdir($directory.'img_m', 0777, true);
mkdir($directory.'img_t', 0777, true);

file_put_contents($directory."data.json", $json);

$csvHeaders=array('itemID','url','Title','Description','Identifier','Creditline','Location','largeImageURL','filename');
if ($datafile = fopen($directory.'data.csv', 'a')){
  fputcsv($datafile, $csvHeaders);
  fclose($datafile);
} else {
  echo 'Failed to write metadata csv file';
}

foreach($response['data'] as $item){
  //echo '</pre><hr><pre>';
  //print_r($item);
  $itemID=$item['id'];

  //$filters=['value'];
  //$itemDescription=array_intersect_key($item['attributes']['description'], array_flip($filters));
  //print_r($itemDescription);
  $itemUrl=$item['links']['self'];
  $itemTitle=$item['attributes']['summary_title'];
  $itemDescription=$item['attributes']['description'][0]['value'];
  $itemIdentifier=$item['attributes']['identifier'][0]['value'];
  $itemCreditline=!empty($item['attributes']['legal']['credit_line']) ? $item['attributes']['legal']['credit_line'] : '';
  $itemLocation=!empty($item['attributes']['locations'][0]['name'][0]['value']) ? $item['attributes']['locations'][0]['name'][0]['value'] : '';

  // get the image file and save it
  $mediaID=$item['attributes']['multimedia'][0]['admin']['id'];
  // save large media
  $largeURL='http://smgco-images.s3.amazonaws.com/media/'.$item['attributes']['multimedia'][0]['processed']['large']['location'];
  $filename=$mediaID.'.'.$item['attributes']['multimedia'][0]['processed']['large']['format'];
  $output = $directory.'img_l/'.$filename;
  file_put_contents($output, file_get_contents($largeURL));
  // save mid media
  $mediumURL='http://smgco-images.s3.amazonaws.com/media/'.$item['attributes']['multimedia'][0]['processed']['medium']['location'];
  $filename=$mediaID.'.'.$item['attributes']['multimedia'][0]['processed']['medium']['format'];
  $output = $directory.'img_m/'.$filename;
  file_put_contents($output, file_get_contents($mediumURL));
  // save thumbnail media
  $thumbURL='http://smgco-images.s3.amazonaws.com/media/'.$item['attributes']['multimedia'][0]['processed']['medium_thumbnail']['location'];
  $filename=$mediaID.'.'.$item['attributes']['multimedia'][0]['processed']['medium_thumbnail']['format'];
  $output = $directory.'img_t/'.$filename;
  file_put_contents($output, file_get_contents($thumbURL));

  // add the record to the csv file
  $csvdata = array($itemID,$itemUrl,$itemTitle,$itemDescription,$itemIdentifier,$itemCreditline,$itemLocation,$largeURL,$filename);
  if (file_exists($directory.'data.csv') && !is_writeable($directory.'data.csv')){
    echo 'csv file write failed';
    return false;
  }
  if ($datafile = fopen($directory.'data.csv', 'a')){
    fputcsv($datafile, $csvdata);
    fclose($datafile);
  } else {
    echo 'Failed to write metadata csv file';
  }
  // put in a small time delay to be nice to providers' servers
  sleep(0.1);

}
//print_r($response);

// log download to csv file
$requests=fopen('requests.csv','a');
fputcsv($requests, array($ID,$yourName,$queryName,$queryUrl,$totalResults,'non-commercial use','complete'));
//print_r($requests);
fclose($requests);

Zip('./'.$directory, './downloads/'.$ID.'.zip',false);
echo 'Successfully created download. Please refresh the page to see it in the list below';
?>