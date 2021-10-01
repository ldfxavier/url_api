<?php

function validateUrl($url){
    $url = parse_url($url);

    unset($url['host']);
    unset($url['scheme']);

    return count($url) ? false : true;
}
