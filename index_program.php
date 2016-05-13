<?php
namespace HW2_Group\Hw2_composer;

use seekquarry\yioop\configs as SYC;
use seekquarry\yioop\library as SYL;
use seekquarry\yioop\library\PhraseParser;

include("lib.php");
require_once 'vendor/autoload.php';

/*if (!defined('seekquarry\\yioop\\configs\\PROFILE')) {
    define('seekquarry\\yioop\\configs\\PROFILE', true);
}
if (!defined("seekquarry\\yioop\\configs\\NO_LOGGING")) {
    define("seekquarry\\yioop\\configs\\NO_LOGGING", true);
}*/
/*
This program builds an inverted index of the documents listed in a directory and
runs a search query on it.
*/
$rankingMethodList = ["cosine","proximity","bm25", "bm25f"];
$tokenizationMethodList = ["none","stem","chargram"];

if (isset($argv) && count($argv) == 3) {
    $dir = $argv[1];
    $output_file = $argv[2];

    $word_map = [];
    $doc_map = [];

    if (is_dir($dir)) {
        $files = glob($dir."/*.txt");
        natsort($files);

        createIndex($files, $word_map, $doc_map, "stem");
        createPostingList($word_map, $doc_map, $secorndary_index, $output_file);

    } else {
        echo "Error! Not a directory.";
    }
} else {
    print "Usage: php search_program <directory> <query> <ranking_method>"
          ." <tokenization_method> <alpha>\n";
}
