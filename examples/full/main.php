<?php

include '../../Boof.php';

$viewPath= dirname ( __FILE__ ) .DIRECTORY_SEPARATOR.'view';
$importPath= dirname ( __FILE__ ) .DIRECTORY_SEPARATOR.'import';
$layoutPath= dirname ( __FILE__ ) .DIRECTORY_SEPARATOR.'layout';

$boof=new Boof($viewPath,$importPath,$layoutPath);

$boof->add_function('external', function ($a,$b){ return $a.' like '.$b.' number '; } );

$data=[
    'str'=>'seyed rahim firouzi',
    'a'=>12 ,
    'list'=>['red','green','blue'],
    'ass'=>[
        'zero'=>':)',
        'one'=>'first',
        'two'=>'second'
    ],
    'my'=>'seyed rahim firouzi',
    
    
];



echo $boof->view('main',$data);



?>