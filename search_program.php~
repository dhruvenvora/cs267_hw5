<?php
namespace HW2_Group\Hw2_composer;

use seekquarry\yioop\configs as SYC;
use seekquarry\yioop\library as SYL;
use seekquarry\yioop\library\PhraseParser;

include("lib.php");
require_once("ProximityRank.inc");
require_once("CosineRank.inc");
require_once 'vendor/autoload.php';

/*
This program builds an inverted index of the documents listed in a directory and
runs a search query on it.
*/
$rankingMethodList = ["cosine","proximity"];
$tokenizationMethodList = ["none","stem","chargram"];

if (isset($argv) && count($argv) == 5) {
    $dir = $argv[1];
    $query = $argv[2];
    $rankingMethod = $argv[3];
    $tokenizationMethod = $argv[4];
    
    $word_map = [];
    if (is_dir($dir)) {
        $files = glob($dir."/*.txt");
        createIndex($files, $word_map, $tokenizationMethod);
        //printMap($word_map);
        $keywords = explode(" ", $query);
		$keywords = tokenize($keywords, $tokenizationMethod);
        $commonDocId = findCommonDocuments($word_map, $keywords);
		
        if ($rankingMethod == 'cosine') {
			$fileCount = count($files);
            $cr = new CosineRank($fileCount);
            $rankedDocs = $cr->rankCosine($word_map, $keywords, 0);
        } else {
            $pr = new ProximityRank(count($keywords));
            $rankedDocs = $pr->rankProximity($word_map, $keywords, 0, $commonDocId);
        }
        arsort($rankedDocs);
        print_r($rankedDocs);
    } else {
        echo "Error! Not a directory.";
    }
} else {
    print "Usage: php search_program <directory> <query> <ranking_method>"
          ." <tokenization_method>\n";
}


