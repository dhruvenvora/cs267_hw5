<?php
namespace HW5_Group\Hw5_composer;

use seekquarry\yioop\configs as SYC;
use seekquarry\yioop\library as SYL;
use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\library\processors\HtmlProcessor;
use seekquarry\yioop\library\CrawlConstants;

require_once 'vendor/autoload.php';

/*
This function takes the list of files and an empty word map. It creates an
inverse index and maps list of files
*/
function createIndex(&$files, &$word_map, &$doc_map, $tokenization)
{

    for ($fil = 0;$fil<count($files);$fil++) {
        $file_id = $files[$fil];
        $content = file_get_contents($file_id);
        $words = explode(" ", $content);
        $count = count($words);

        $doc_id = substr($file_id, 6, 2);
        $doc_map[$doc_id] = $count;

        for ($j=0;$j<$count;$j++) {
  			$word = filter($words[$j]);
  			$processedWord = tokenize(array($word), $tokenization);
  			for ($index=0;$index<count($processedWord);$index++) {
  				mapWord($word_map, $processedWord[$index], $doc_id);
  			}
        }
    }
}

/*
This function maps the file number and offset against the word. If the word is
not in the map then the function will add it. After adding the offset, doc_count
(if different) and term_count are increamented.
*/
function mapWord(&$word_map, $word, $doc_id)
{
    if($word_map[$word] != null) {
        if($word_map[$word][$doc_id] == null) {
            $word_map[$word][$doc_id] = 1;
        } else {
            $count = $word_map[$word][$doc_id] + 1;
            $word_map[$word][$doc_id] = $count;
        }
    } else {
        $word_map[$word] = [];
        $word_map[$word][$doc_id] = 1;
    }
}

/*
This method will create a dictionary from the content of the file. It also keeps
track of document length.
*/
function createPostingList($word_map, $doc_map, $secorndary_index, $output_file){
    //offset_doc_map : [docid] => [offset]
    //$bin_doc_list : [docid_len][docid][doc_length]...
    $offset_doc_map = [];
    $bin_doc_list = getOffsetDocMap($doc_map, $offset_doc_map);

    //sec_index: [word]=>[offset_in_bin_posting_list]
    //bin_posting_list: [doc_list_len][doc_list][freq_list_len][freq_list]...
    $sec_index = [];
    $bin_posting_list = getOnDiskPostingList($word_map, $doc_map, $offset_doc_map, $sec_index);

    //primary_index: [offset]...
    //bin_sec_index: [word_length][word][offset]...
    $primary_index = [];
    $bin_sec_index = createPrimaryIndex($sec_index, $primary_index);

    //bin_primary_index: [offset]...
    $bin_primary_index = "";
    foreach ($primary_index as $offsets) {
        $bin_primary_index = $bin_primary_index.pack("N",$offsets);
    }

    $out_file = fopen($output_file, "w");

    $len = strlen($bin_doc_list);
    fwrite($out_file, pack("N", $len));
    fwrite($out_file, $bin_doc_list);

    $len = strlen($bin_primary_index);
    fwrite($out_file, pack("N", $len));
    fwrite($out_file, $bin_primary_index);

    $len = strlen($bin_sec_index);
    fwrite($out_file, pack("N", $len));
    fwrite($out_file, $bin_sec_index);

    $len = strlen($bin_posting_list);
    fwrite($out_file, pack("N", $len));
    fwrite($out_file, $bin_posting_list);

    fclose($out_file);
}

function getOffsetDocMap(&$doc_map, &$offset_doc_map)
{
    $bin_doc_list = "";
    $offset = 0;
    foreach ($doc_map as $doc_id=>$length) {
        $offset_doc_map[$doc_id] = $offset;
        $docid_len = strlen($doc_id);
        $bin_doc_list = $bin_doc_list.pack("N", $docid_len).$doc_id.pack("N", $length);
        $offset = strlen($bin_doc_list);
    }
    return $bin_doc_list;
}

function getOnDiskPostingList(&$word_map, &$doc_map, &$offset_doc_map, &$posting_map)
{
    $bin_posting_list = "";
    $offset = 0;
    foreach ($word_map as $word=>$postings) {
        $prev = 0;
        $doc_list = "";
        $freq_list = "";
        $len = 0;
        foreach ($postings as $doc_id=>$freq) {
            //we will store offset intead of actual document id.
            $diff = $offset_doc_map[$doc_id] - $prev;//delta
            $doc_list = $doc_list . toGammaCode($diff+1);
            $freq_list = $freq_list . toGammaCode($freq);
            $prev = $offset_doc_map[$doc_id];
        }
        $doc_list_len = strlen($doc_list);
        $freq_list_len = strlen($freq_list);
        $bin_posting_list = $bin_posting_list.pack("N",$doc_list_len).$doc_list;
        $bin_posting_list = $bin_posting_list.pack("N",$freq_list_len).$freq_list;
        $posting_map[$word] = $offset;
        if($word == "the"){
            print $doc_list_len." ".$doc_list." ".$freq_list_len." ".$freq_list;
        }
        $offset = strlen($bin_posting_list);
    }
    return $bin_posting_list;
}

function toGammaCode($num)
{
    $str = decbin($num);
    $len = strlen($str);
    $str = str_pad($str, 2*$len-1, "0", STR_PAD_LEFT);
    return $str;
}

function createPrimaryIndex(&$sec_index, &$primary_index)
{
    $offset = 0;
    $bin_sec_index = "";
    foreach ($sec_index as $word => $posting_offset) {
        array_push($primary_index, $offset);
        $word_len = strlen($word);
        $bin_sec_index = $bin_sec_index . pack("N", $word_len) . $word . pack("N", $posting_offset);
        $offset = strlen($bin_sec_index);
    }
    return $bin_sec_index;
}
/*
This method applies tokenization on words.
*/
function tokenize($words, $tokenizationMethod){
    $processedWord = array();

    for($j = 0;$j<count($words);$j++){
        $word = filter($words[$j]);
      if ($tokenizationMethod == 'chargram') {
		    $processedWord = array_merge($processedWord,
                                  PhraseParser::getNGramsTerm(array($word),5));
	    }
	    else if ($tokenizationMethod == 'stem') {
		    $processedWord = array_merge($processedWord,
                                PhraseParser::stemTerms(array($word),"en-US"));
	    }
	    else {
	      // no tokenization
		    array_push($processedWord, $word);
	    }
	}
	return $processedWord;
}

/*
This function filters the special characters from the word. It only allows
alphanumeric characters in the word.
*/
function filter($word)
{
    $word = trim($word);
    $word = preg_replace('/[^A-Za-z0-9\-]/','', $word);
    return strtolower($word);
}
