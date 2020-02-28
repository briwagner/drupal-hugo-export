<?php

use Drupal\hugo_export\HugoMenuLoader;

$hugo = new HugoMenuLoader();
$items = $hugo->loadMenu("hugo_export");
var_dump($items);