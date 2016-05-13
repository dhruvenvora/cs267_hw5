<?php
namespace HW2_Group\Hw2_composer;
require_once("MyMinHeap.inc");
require_once("MyMaxHeap.inc");

class BM25Rank
{
    private static $wordMap;
    private static $currentDocForWord = [];
    private static $totalDocs;
    private static $avgDocLength;
    private static $IDF = [];
    private static $TF = [];
    private static $reverseDocMap = [];
    const CAP_ON_TERM_FREQ = 1.2;
    const LENGTH_NORM = 0.75;

    /*
    This constructor initializes following variables
    */
    function __construct(&$word_map, $fileCount)
    {
        self::$totalDocs = $fileCount;
        self::$wordMap = $word_map;
    }

    function rankBM25(&$words, &$docLengthMap, $k)
    {
        self::$currentDocForWord = [];
        self::$IDF = [];
        self::$TF = [];
        self::$reverseDocMap = [];

        $this->findAvgLength($docLengthMap);
        foreach($words as $word){
            $this->calculateIDF($word);

            foreach(self::$wordMap[$word] as $docid=>$postingList){
                if(self::$reverseDocMap[$docid] == null){
                    self::$reverseDocMap[$docid] = [];
                }
                array_push(self::$reverseDocMap[$docid], $word);
                $freq = count($postingList);
                $this->calculateTF($word, $docid, $freq, $docLengthMap);
            }
        }
        return $this->calculateScore($words);
    }

    function findAvgLength(&$docLengthMap)
    {
        $avg = 0;
        foreach($docLengthMap as $docid=>$len){
            $avg += $len;
        }
        self::$avgDocLength = $avg / $totalDocs;
    }

    function calculateIDF($word)
    {
        $N = count(self::$wordMap[$word])-2;
        self::$IDF[$word] = log(self::$totalDocs / $N, 2);
    }

    function calculateTF($word, $docid, $freq, &$docLengthMap){
        $tfForWord = $freq * self::CAP_ON_TERM_FREQ;
        $normalizedLength = (1 - self::LENGTH_NORM)
                          + self::LENGTH_NORM
                          * ($docLengthMap[$docid] / self::$avgDocLength);
        $denom = $freq + self::CAP_ON_TERM_FREQ *$normalizedLength;
        $tfForWord = $tfForWord / $denom;

        self::$TF[$word][$docid] = $tfForWord;
    }

    function calculateScore($words)
    {
        $terms = new MyMinHeap(); // [word, document]
        $result = new MyMaxHeap(); // [docid, score]
        $result->insert(array(0 => 0));

        foreach($words as $word){
            self::$currentDocForWord[$word] = 1;
            $nd = $this->nextDoc($word);
            $terms->insert(array($word => $nd));
        }
        //print_r($terms);
        while(array_values($terms->top())[0] < INF){
            $d = array_values($terms->top())[0];
            $score = 0;
            while(array_values($terms->top())[0] == $d){
                $t = $terms->extract(); // pair [word, document]
                $key = array_keys($t)[0];
                $val = array_values($t)[0];
                //print $key ." ".$val."\n";
                //print self::$IDF[$key] ." ". self::$TF[$key][$val] . "\n";
                $score += self::$IDF[$key] * self::$TF[$key][$val];
                $nd = $this->nextDoc($key);
                $terms->insert(array($key => $nd));
            }

            if($score > array_values($result->top())[0]){
                $result->insert(array($d => $score));
            }
        }
        //$result->extract();
        //print_r($result);
        return $result;
    }

    function nextDoc($word)
    {
        $docs = array_keys(self::$wordMap[$word]);
        $i = self::$currentDocForWord[$word];
        if($i >= count($docs) || $i < 1){
            return INF;
        }
        self::$currentDocForWord[$word] = $i+1;
        return $docs[$i+1];
    }
}
