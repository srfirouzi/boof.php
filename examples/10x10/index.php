<?php

include '../../Boof.php';


$boof=new Boof('');

$code=<<<CODE
{{var data=[1,2,3,4,5,6,7,8,9,10] }}
<style>
td{
border:1px solid blue;
text-align:center;
valign:center;
width:30px;
height:30px;
}
</style>
<h1> 10 X 10</h1>
<table>
{{-for i in data-}}
  <tr>
    {{-for j in data-}}
    <td>{{i*j}}</td>
    {{-end-}}
  </tr>
{{-end-}}
</table> 

CODE;


echo $boof->render($code);



?>