<?php 

function colorGradient($ratio) {
    if($ratio>0.5) {
        $color1 = "B6D7A8";
        $color2 = "FFD666";
    }
    elseif($ratio<0.5) {
        $color1 = "FFD666";
        $color2 = "EA9999";
    }
    else {
        return "#FFD666";
    }

    $r = ceil(hexdec(substr($color1, 0, 2)) * $ratio + hexdec(substr($color2, 0, 2)) * (1-$ratio));
    $g = ceil(hexdec(substr($color1, 2, 2)) * $ratio + hexdec(substr($color2, 2, 2)) * (1-$ratio));
    $b = ceil(hexdec(substr($color1, 4, 2)) * $ratio + hexdec(substr($color2, 4, 2)) * (1-$ratio));

    if($r == 0) {
        $newr = "00";
    }
    else {
        $newr = dechex($r);
    }

    if($g == 0) {
        $newg = "00";
    }
    else {
        $newg = dechex($g);
    }

    if($b == 0) {
        $newb = "00";
    }
    else {
        $newb = dechex($b);
    }

    $middle = "#" . $newr . $newg . $newb;

    return $middle;
}