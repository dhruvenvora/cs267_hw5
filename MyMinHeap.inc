<?php
namespace HW2_Group\Hw2_composer;

class MyMinHeap extends \SplMinHeap
{
    /**
     * We modify the abstract method compare so we can sort our
     * rankings using the values of a given array
     */
    public function compare($array1, $array2)
    {
        $values1 = array_values($array1);
        $values2 = array_values($array2);
        return $values2[0] - $values1[0];
        /*if ($values1[0] === $values2[0]) return 0;
        return $values1[0] < $values2[0] ? 1 : -1;*/
    }
}
