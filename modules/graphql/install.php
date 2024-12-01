<?php

defined('BASEPATH') or exit('No direct script access allowed');

$graphqltoken = get_option('graphqltoken');
$timestamp = time();

if ($graphqltoken === null || $graphqltoken === '') {
    add_option('graphqltoken', $timestamp);
} else {
}