<?php
$has = false;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app')) as $f) {
    if($f->isFile() && $f->getExtension()=='php') {
        $c=file_get_contents($f->getPathname());
        if(strpos($c, 'Ãƒ')!==false){
            echo $f->getPathname()."\n";
            $has=true;
        }
    }
}
if(!$has) echo 'No mojibake files found.';
