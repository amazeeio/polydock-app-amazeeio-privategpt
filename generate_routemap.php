<?php

declare(strict_types=1);

include 'vendor/autoload.php';

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Routemap\Routemapper;

var_dump(Routemapper::base64encodedRoutes(132, 'aaiprivategpt-testingroutes1'));

// $command = 'lagoon list deploytargets --output-json'; // Replace with the actual command
// $output = null;
// $returnVar = null;
// exec($command, $output, $returnVar);

// if ($returnVar === 0) {
//     $jsonString = implode("\n", $output);
//     $data = json_decode($jsonString, true);
//     if (json_last_error() === JSON_ERROR_NONE) {

//         if (isset($data['data']) && is_array($data['data'])) {
//             foreach ($data['data'] as $item) {
//                 // Example: print each field
//                 echo 'ID: '.$item['id']."\n";
//                 echo 'Name: '.$item['name']."\n";
//                 echo 'Console URL: '.$item['consoleurl']."\n";
//                 echo 'SSH Host: '.$item['sshhost']."\n";
//                 echo 'SSH Port: '.$item['sshport']."\n";
//                 echo 'Build Image: '.$item['buildimage']."\n";
//                 echo 'Router Pattern: '.$item['routerpattern']."\n";
//                 echo "-----------------------------\n";
//             }
//         } else {
//             echo "No data found.\n";
//         }
//     } else {
//         echo 'Failed to parse JSON: '.json_last_error_msg()."\n";
//     }
// } else {
//     echo "Command failed with exit code $returnVar\n";
// }
