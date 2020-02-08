<?php

include '../../Boof.php';

$viewPath= dirname ( __FILE__ ) ;

$boof=new Boof($viewPath);

$data=[
    'count'=>[1,2,3,4,5],
    'items'=>[
        ['type'=>'text','value'=>'user name:'],
        ['type'=>'input','value'=>'nothing','name'=>'user'],
        ['type'=>'text','value'=>'password:'],
        ['type'=>'password','name'=>'pass'],
        ['type'=>'text','value'=>'password again:'],
        ['type'=>'password','name'=>'repass'],
        ['type'=>'submit','value'=>'add user'],
    ]
];

$temp=<<<TEMP
{{import "form"}}

{{for i in count}}
    <div style="border:1px solid red;">
    sent to page index.php?id={{i}}

    {{var url = "index.php?id=" ~ i }}
    <div>{{form url "post" items }}</div>
    </div>
{{end}}



<div></div>

TEMP;

echo $boof->render($temp,$data);



?>