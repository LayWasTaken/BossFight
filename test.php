<?php
$test = [9 => 2151, 65 => 1521532, 1 => 6346, 29 => 54];
$test2 = [9 => 2151, 652 => 152153, 1 => 6346, 29 => 54];
print_r(array_diff_key($test, $test2));

class Test {

    public int $test;

    public function __construct(){
    }

    public function isSet(){
        var_dump($this->test);
    }

}

echo (new Test)->isSet();