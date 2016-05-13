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
    //first find the offset of all the documents from $doc_map
    $offset_doc_map = [];
    $bin_doc_list = getOffsetDocMap($doc_map, $offset_doc_map);
    print_r($offset_doc_map);
    $posting_map = [];
    $bin_posting_list = getPostingsFromWordMap($word_map, $doc_map, $offset_doc_map, $posting_map);
    //print_r($posting_map);
    $primary_index = [];
    $bin_sec_index = createPrimaryIndex($posting_map, $primary_index);
    //print_r($primary_index);

    $out_file = fopen($output_file, "w");
    fwrite($out_file, $bin_doc_list);
    fwrite($out_file, "\n\n");
    fwrite($out_file, $bin_posting_list);
    fwrite($out_file, "\n\n");
    fwrite($out_file, $bin_sec_index);
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
    //print $bin_doc_list."\n";
    return $bin_doc_list;
}

function getPostingsFromWordMap (&$word_map, &$doc_map, &$offset_doc_map, &$posting_map)
{
    $bin_posting_list = "";
    $offset = 0;
    foreach ($word_map as $word=>$postings) {
        $docs = array_keys($postings);
        $prev = 0;
        $doc_list = "";
        $freq_list = "";
        $len = 0;
        foreach ($docs as $doc_id=>$freq) {
            $diff = $offset_doc_map[$doc_id] - $prev;
            $gamma = toGammaCode($diff);
            $doc_list = $doc_list . $gamma;
            $freq_list = $freq_list . toGammaCode($freq);
            $prev = $offset_doc_map[$doc_id];
        }
        $lenDoc = strlen($doc_list);
        $lenFreq = strlen($freq_list);
        $bin_posting_list = $bin_posting_list.pack("N",$lenDoc).$doc_list.pack("N",$lenFreq).$freq_list;
        $posting_map[$word] = $offset;
        $offset += strlen($bin_posting_list);
    }
    //print $bin_posting_list."\n";
    return $bin_posting_list;
}

function toGammaCode($num)
{
    $str = decbin($num);
    $len = strlen($str);
    $str = str_pad($str, 2*$len-1, "0", STR_PAD_LEFT);
    return $str;
}

function createPrimaryIndex(&$posting_map, &$primary_index)
{
    $offset = 0;
    $bin_sec_index = "";
    foreach ($posting_map as $word => $sec_offset) {
        array_push($primary_index, $offset);
        $len = strlen($word);
        $bin_sec_index = $bin_sec_index . pack("N", $len) . $word . pack("N", $sec_offset);
        $offset = strlen($bin_sec_index);
    }
    //print $bin_sec_index."\n";
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

/*
This method calculates the vector for the current document. The dimention of the
vector is specified by the word. The vector looks like,
docid = [word1=>count1, word2=>count2 ...]
*/
function createVector(&$docVector, &$word){
    if(isset($docVector[$word]) == false){
        $docVector[$word] = 1;
    }
    $docVector[$word] = $docVector[$word]+1;
}

/*
This function normalizes the vector. First it finds the magnitude of the vector
    sum(value1^2+value2^2+...)
and then every value is divided by magnitude giving a unit or normalized vector.
*/
function normalizeVector(&$docVector, $fil){
    $sum = 0;
    foreach($docVector as $key=>$value){
        $sum += pow($docvector[$key], 2);
    }
    $docMagnitude[$fil] = sqrt($sum);

}

/*
This function prints the map.
*/
function printMap(&$word_map)
{
    ksort($word_map);
    if ($word_map != null) {
        foreach ($word_map as $word => $value) {
            $line = $word . ":" ;
            $data = "";
            foreach ($word_map[$word] as $page => $occurance) {
                if (is_array($occurance)) {
                    $data = $data."(".$page;
                    foreach ($occurance as $occ) {
                        $data = $data.",".$occ;
                    }
                    $data = $data."),";
                }
            }
            $line = $line.$word_map[$word]['doc_count'].":"
                    .$word_map[$word]['term_count'].":"
                    .rtrim($data, ",")."\n";
            print $line;
        }
    }
}

/*
This method prints heap values to the output
*/
function printHeaps($heap)
{
    $res = [];
    // For displaying the ranking we move up to the first node
    $heap->top();

    // Then we iterate through each node for displaying the result
    while ($heap->valid()) {
        list ($key, $value) = each ($heap->current());
        $res[$key] = $value;
        //print "(".$key.",".$value.")".PHP_EOL;
        $heap->next();
    }

    arsort($res);
    foreach($res as $key=>$value){
        print "(".$key.",".$value.")".PHP_EOL;
    }

    print "\n";
}

/*
This methos prints query map.
*/
function printMapForQuery(&$word_map, $words)
{
    if ($word_map != null) {
        foreach ($words as $word) {
            $line = $word . ":" ;
            $data = "";
            foreach ($word_map[$word] as $page => $occurance) {
                if (is_array($occurance)) {
                    $data = $data."(".$page.",".count($occurance);
                    /*foreach ($occurance as $occ) {
                        $data = $data.",".$occ;
                    }*/
                    $data = $data."),";
                }
            }
            $line = $line.$word_map[$word]['doc_count'].":"
                    .$word_map[$word]['term_count'].":"
                    .rtrim($data, ",")."\n";
            print $line;
            print "\n";
        }
    }
}

/*
This function returns the common documents where all the query words are present
*/
function findCommonDocuments(&$word_map, &$keywords)
{
    $count = count($keywords);
    //Use document list from 0th word as a reference.
    $docidList = array_keys($word_map[$keywords[0]]);
    $words = array();
    $commonDocId = array();

    $totalDocs = count($docidList);

    //TODO: Galloping search TBD !!!
    //Starting for index 2 as 0,1 points to doc_count and term_count
    for($i=2;$i<$totalDocs;$i++) {
        $all = true;// Assuming that all words are present in document.
        for($j=1;$j<$count;$j++) {
            if(isset($word_map[$keywords[$j]][$docidList[$i]]) == false) {
                $all = false;//Assumption failed :(
                break;
            }
        }
        if($all == true) {
            array_push($commonDocId, $docidList[$i]);
        }
    }

    return $commonDocId;
}

function findDocumentsWithAtleastOneWord(&$word_map, &$keywords)
{
    $docList = array();
    $len = count($keywords);
    for($i=0;$i<$len;$i++){
        foreach($word_map[$keywords[$i]] as $docid => $pos){

        }
    }
}
