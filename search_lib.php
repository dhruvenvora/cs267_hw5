<?php
namespace HW2_Group\Hw2_composer;
/*
This 
*/
function rank(&$word_map, &$query, $N){
    $words = explode($query);
    
    for($i = 0;$i < count($words); $i++){
        $word = $word[$i];
        $doc_freq = $word_map[$word]["doc_count"];
        $term_freq = $word_map[$word]["term_count"];
        $idf = log($N/ $doc_freq);
        foreach($word as $key => $value){
            
            $files = $word_map[$word][$key]
        }      
    }
}
