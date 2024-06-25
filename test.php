<?php
function gene(){
    echo "test";
    yield 1;
}

$test = gene();
var_dump($test->current());