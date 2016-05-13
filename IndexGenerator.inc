<?php
use seekquarry\yioop\configs as SYC;
use seekquarry\yioop\library as SYL;
use seekquarry\yioop\library\PhraseParser;
use seekquarry\yioop\library\processors\HtmlProcessor;
use seekquarry\yioop\library\CrawlConstants;

class IndexGenerator
{
    private $word_map;

    function getIndex(){
        return $word_map;
    }

    function createIndex(&$files, &$word_map, $rankingMethod, $tokenizationMethod){
        $hp = new HtmlProcessor([], 20000, CrawlConstants::CENTROID_SUMMARIZER);
        for ($fil = 0;$fil<count($files);$fil++) {
            $content = file_get_contents($files[$fil]);
            $summary = $hp->process($content, "http://www.gh.com");

            $title = $summary[CrawlConstants::TITLE];
            $desc = $summary[CrawlConstants::DESCRIPTION];

            if($rankingMethod != "bm25f"){
                $content = $desc;
            }else{
                //TODO: check for special characters in the title.
                $len = strlen($title);
                $pos = strpos($desc, $title);
                $content = trim($desc);
                $content = substr($content, $len);
                $content = trim($content);
            }

            $this->createDictionary($content);
        }
    }

    function createDictionary($content){
        $words = explode(" ", $content);
        $count = count($words);
        for ($j=0;$j<$count;$j++) {
            $word = filter($words[$j]);
            $processedWord = $this->tokenize(array($word), $tokenizationMethod);
            $offset = $j + 1;//We store offset starting from 1
            $f = $fil+1;
            for ($index=0;$index<count($processedWord);$index++) {
                $this->mapWord($word_map, $processedWord[$index], $offset, $f);
            }
        }
    }

    /*
    This method applies tokenization on words.
    */
    function tokenize($words, $tokenizationMethod){
        $processedWord = array();

        for($j = 0;$j<count($words);$j++){
            $word = filter($words[$j]);
            if ($tokenizationMethod == 'chargram') {
        		    $processedWord = PhraseParser::getNGramsTerm(array($word),5);
        	  } else if ($tokenizationMethod == 'stem') {
        		    $processedWord = PhraseParser::stemTerms(array($word),"en-US");
        	  } else {
        	      // no tokenization
        		    array_push($processedWord,$word);
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
    This function maps the file number and offset against the word. If the word is
    not in the map then the function will add it. After adding the offset, doc_count
    (if different) and term_count are increamented.

    word -> ["term_count"-> 1,
             "doc_count"-> 1,
             [0->1,2,3],
             [1->1,2,3]
            ]
    */
    function mapWord(&$word_map, $word, $offset, $f)
    {
        if (isset($word_map[$word])) {
            if (isset($word_map[$word][$f])){
                $term_count = $word_map[$word]['term_count']+1;
                $word_map[$word]['term_count'] = $term_count;
                array_push($word_map[$word][$f], $offset);
            } else {
                $doc_count = $word_map[$word]['doc_count']+1;
                $word_map[$word]['doc_count'] = $doc_count;
                $word_map[$word][$f] = array();
                $term_count = $word_map[$word]['term_count']+1;
                $word_map[$word]['term_count'] = $term_count;
                array_push($word_map[$word][$f],$offset);
            }
        }
        else {
            $word_map[$word] = array();
            $word_map[$word]['doc_count'] = 1;
            $word_map[$word]['term_count'] = 1;
            $word_map[$word][$f] = array();
            array_push($word_map[$word][$f], $offset);
        }
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
}
