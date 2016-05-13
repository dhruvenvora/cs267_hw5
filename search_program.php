<?php
namespace HW2_Group\Hw2_composer;

use seekquarry\yioop\configs as SYC;
use seekquarry\yioop\library as SYL;
use seekquarry\yioop\library\PhraseParser;

include("lib.php");
require_once("ProximityRank.inc");
require_once("CosineRank.inc");
require_once("BM25Rank.inc");
require_once 'vendor/autoload.php';

if (!defined('seekquarry\\yioop\\configs\\PROFILE')) {
    define('seekquarry\\yioop\\configs\\PROFILE', true);
}
/*
This program builds an inverted index of the documents listed in a directory and
runs a search query on it.
*/
$rankingMethodList = ["cosine","proximity","bm25", "bm25f"];
$tokenizationMethodList = ["none","stem","chargram"];

if (isset($argv) && count($argv) == 6) {
    $dir = $argv[1];
    $query = $argv[2];
    $rankingMethod = $argv[3];
    $tokenizationMethod = $argv[4];
    $alpha = $argv[5];

    $word_map = [];
    if (is_dir($dir)) {
        $files = glob($dir."/*.html");
        //createIndex($files, $word_map, $tokenizationMethod);
        createHTMLIndex($files, $word_map, $rankingMethod, $tokenizationMethod);
        //printMap($word_map);

        $keywords = explode(" ", $query);
		    $keywords = tokenize($keywords, $tokenizationMethod);
        $commonDocId = findCommonDocuments($word_map, $keywords);

        if ($rankingMethod == "bm25") {
            $totalDocs = count($files);
            $bm25 = new BM25Rank($word_map, $totalDocs);
            $rankedDocs = $bm25->rankBM25($keywords, $docLengthMap, 0);
            print_r($rankedDocs);
        } else if ($rankingMethod == 'bm25f') {
            createHTMLIndexBM25f($files, $word_map1, $word_map2, $tokenizationMethod);

            $totalDocs = count($files);
            $bm25f_1 = new BM25Rank($word_map1, $totalDocs);
            $rankedDocs1 = $bm25f_1->rankBM25($keywords, $docLengthMap, 0);

            $bm25f_2 = new BM25Rank($word_map2, $totalDocs);
            $rankedDocs2 = $bm25f_2->rankBM25($keywords, $docLengthMap, 0);

            /*while ($rankedDocs1->valid()) {
              list ($doc, $score) = each ($rankedDocs1->current());
              echo $doc . ': ' . $score . PHP_EOL;
              $rankedDocs1->next();
            }*/
            print_r($rankedDocs1);
            print_r($rankedDocs2);
        } else if ($rankingMethod == 'cosine') {
			      $fileCount = count($files);
            $cr = new CosineRank($fileCount);
            $rankedDocs = $cr->rankCosine($word_map, $keywords, 0);
        } else {
            $pr = new ProximityRank(count($keywords));
            $rankedDocs = $pr->rankProximity($word_map, $keywords, 0, $commonDocId);
        }
        arsort($rankedDocs);
        //print_r($rankedDocs);
    } else {
        echo "Error! Not a directory.";
    }
} else {
    print "Usage: php search_program <directory> <query> <ranking_method>"
          ." <tokenization_method>\n";
}
