<?php
$heap = new SplMaxHeap();
$heap->insert(15);
$heap->insert(20);
$heap->insert(11);
$heap->insert(12);
$heap->insert(21);
$heap->insert(15);
$heap->insert(10);
$heap->insert(18);

$heap->top();

// Then we iterate through each node for displaying the result
while ($heap->valid()) {

  echo $heap->current();
  $heap->next();
}
?>
