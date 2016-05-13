<?php
namespace HW5_Group\Hw5_composer;

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

if (isset($argv) && count($argv) == 4) {
    $index_file = $argv[1];
    $query = $argv[2];
    $relevance = $argv[3];

    $file_handle = fopen($index_file, "r") or die("Unable to open file!");
    $len_doc_map = fread($file_handle, 4);
    $len = unpack("N", $len_doc_map)[1];
    $doc_map_str = fread($file_handle, $len);

    $doc_map = [];
    $i = 0;
    while($i < $len){
        $docid_len = unpack("N",substr($doc_map_str, $i, 4))[1];
        $i += 4;
        $docid = substr($doc_map_str, $i, $docid_len);
        $i += $docid_len;
        $doc_len = unpack("N",substr($doc_map_str, $i, 4))[1];
        $i += 4;
        $doc_map[$docid] = $doc_len;
    }

    $len_prim_index = fread($file_handle, 4);
    $len = unpack("N", $len_prim_index)[1];
    $prim_index_str = fread($file_handle, $len);

    $primary_index = [];
    $i = 0;
    while($i < $len){
        $sec_offset = unpack("N",substr($prim_index_str, $i, 4))[1];
        $i += 4;
        array_push($primary_index,$sec_offset);
    }

    $len_sec_index = fread($file_handle, 4);
    $len = unpack("N", $len_sec_index)[1];
    $sec_index_str = fread($file_handle, $len);

    $sec_index = [];
    $i = 0;
    while($i < $len){
        $obj_len = unpack("N",substr($sec_index_str, $i, 4))[1];
        $i += 4;
        $word = substr($sec_index_str, $i, $obj_len);
        $i += $obj_len;
        $sec_offset = unpack("N",substr($sec_index_str, $i, 4))[1];
        $i += 4;
        $sec_index[$word] = $sec_offset;
    }

    //fseek($file_handle, $sec_index[$query], SEEK_CURR);
    $posting_len = fread($file_handle, 4);
    $len = unpack("N", $posting_len)[1];
    print $len;


} else {
    print "Usage: php search_program <directory> <query> <ranking_method>"
          ." <tokenization_method> <alpha>\n";
}
