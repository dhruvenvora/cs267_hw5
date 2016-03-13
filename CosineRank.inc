<?php
/*
This class defines the methods used for calculating document ranks based on
cosine ranking scheme.
*/
class CosineRank
{
    private static $doc_map;
    private static $totalDocs;
    /*
    This constructor initializes following variables
    */
    function __construct($fileCount) 
    {
        $this::$totalDocs = $fileCount;
    }
    
    /*
    This function calculates the rank of the documents based on the proximity 
    of the query words in the document.
    $word_map = Complete inverted index.
    $words = map of words to be searched.
    $k = top K documents with highest ranking are returned.
    */
    function rankCosine(&$word_map, &$words, $k)
    {
        $rankedDocs = array();
        
        $keyword_map = $this->selectUniqueKeywords($words);
    
        foreach($keyword_map as $word => $count){
            $docs = array_keys($word_map[$word]);
            $docCount = count($docs);
            for($j=2;$j<$docCount;$j++){
                if(isset($this::$doc_map[$docs[$j]]) == false){
                    $this->calculateMagnitude($docs[$j], $word_map);
                }
            }
        }
        
        //Once we find magnitude of all documents, calculate the dot product.
        //TODO: I am assuming that each word occurs only once in query.
        foreach($this::$doc_map as $docid => $magnitude){
            $score = $this->calculateDotProduct($word_map, $keyword_map, $docid, $magnitude);
            $rankedDocs[$docid] = $score;
        }
        
        return $rankedDocs;    
    }
    
    function selectUniqueKeywords(&$words)
    {
        $keywords_map = array();
        $len = count($words);
        for($i=0;$i<$len;$i++){
            if(isset($keywords_map[$words[$i]]) == false){
                $keywords_map[$words[$i]] = 1;
            } else {
                $keywords_map[$words[$i]] = $keywords_map[$words[$i]] + 1;
            }
        }
        return $keywords_map;
    }
    
    function calculateMagnitude($docid, &$word_map)
    {
        $magnitude = 0.0;
        foreach($word_map as $word => $listOfDocs){
            if(isset($word_map[$word][$docid]) == true){
                $w = $this->calculateWeight($word_map[$word], $docid);
                $magnitude += pow($w, 2);
            }
        }
        $magnitude = sqrt($magnitude);
        $this::$doc_map[$docid] = $magnitude;
    }
    
    function calculateWeight(&$word_docs, $docid){
        $idf = $this->findIDF($word_docs);
        $tf = $this->findTF($word_docs, $docid);
        return $idf*$tf;
    }
    
    function findIDF(&$word_docs){
        $doc_freq = count(array_keys($word_docs))-2;
        $idf = log($this::$totalDocs / $doc_freq, 2);
        return $idf;
    }
    
    function findTF(&$word_docs, $docid){
        $tf = count($word_docs[$docid]) * 1.0;//Converting to float
        if($tf > 0)
            $tf = log($tf, 2)+1;
        
        return $tf;
    }
    
    function calculateDotProduct(&$word_map, &$keyword_map, $docid, $magnitude){
        $score = 0;
        $magnitudeOfQuery = 0.0;
        foreach($keyword_map as $word => $count){
            $idf = $this->findIDF($word_map[$word]);
            $tf_query = $count;
            if($tf_query > 0)
                $tf_query = log($tf_query, 2)+1;
            
            $magnitudeOfQuery += pow($idf*$tf_query,2);
        }
        $magnitudeOfQuery = sqrt($magnitudeOfQuery);
          
        foreach($keyword_map as $word => $count){
            $idf = $this->findIDF($word_map[$word]);
            $tf_doc = $this->findTF($word_map[$word], $docid);
            
            $tf_query = $count;
            if($tf_query > 0)
                $tf_query = log($tf_query, 2)+1;
                
            $score +=  ($idf * $tf_query / $magnitudeOfQuery)
                        *($idf * $tf_doc / $magnitude);
        }
        
        return $score;
    }
}
