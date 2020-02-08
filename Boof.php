<?php 
/**
 * boof.php
 *
 * advance template engine for php
 * @author Seyed Rahim Firouzi <seyed.rahim.firouzi@gmail.com>
 * @license MIT 2020
 */
class Boof_Scanner{
    private static $SPACERS=[' ',"\n","\r","\t"];
    private static $OPERATORS = [
        '!' , '>' , '<' , '=' , '?' , '&' , '@' , '#' ,
        '+' , '-' , '*' , '/' , '%' , '~' , '^',':'
    ];
    
    private $source='';
    private $index=0;
    private $length=0;
    
    private $rigth_clean=false;
    
    function __construct($source) {
        $this->source=$source;
        $this->length=strlen($source);
    }
    public function next() {
        while (true) {
            if ($this->index == $this->length){ // EOF
                return null;
            }
            if (substr($this->source, $this->index, 2) == '{{') {
                if(substr($this->source, $this->index, 3)=='{{-'){
                    $this->index=$this->index + 3;
                }else{
                    $this->index=$this->index + 2;
                }
                $data = $this->get_code();
                if(count($data)>0){ //for remove empty bracket
                    return ['type' => 'code','value' => $data];
                }
            }else{
                $data = $this->get_text();
                if ($data != '') {//for remove empty string ( never happen :) )
                    return ['type' => 'text','value' => $data];
                }
            }
        }
    }
    private function get_text() {
        $left_clean=false;
        $start = strpos($this->source, '{{', $this->index);
        if ($start === false) {
            $substr = substr($this->source, $this->index);
            $this->index = $this->length;
        }else{
            if(substr($this->source,$start,3)=='{{-'){
                $left_clean=true;
            }
            $len= $start - $this->index;
            $substr = substr($this->source, $this->index, $len);
            $this->index = $start;
        }
        if($left_clean){
            $substr= rtrim($substr);
        }
        if($this->rigth_clean){
            $substr= ltrim($substr);
            $this->rigth_clean=false;
        }
        return $substr;
    }
    
    private function get_code() {
        $out=[];
        $start =$this->index;
        $end=$this->length;
        for ($i = $start; $i < $end; $i++) {
            if (substr($this->source, $i, 3) == '-}}') {
                $this->rigth_clean=true;
                $this->index = $i + 3;
                return $out;
            }
            if (substr($this->source, $i, 2) == '}}') {
                $this->index = $i + 2;
                return $out;
            }
            $char = substr($this->source, $i, 1);
            if(in_array($char, self::$SPACERS)){// is space
                continue;
            }elseif(in_array($char, self::$OPERATORS)){ //is operator
                $data=$this->read_operator($i,$end);
                $out[]=$data['code'];
                $i=$data['index'];
            }elseif ($char=='"'){//is string type
                $data=$this->read_string($i,$end);
                $out[]=$data['code'];
                $i=$data['index'];
            }elseif (preg_match('/^[0-9]$/', $char)){//is number type
                $data=$this->read_number($i,$end);
                $out[]=$data['code'];
                $i=$data['index'];
            }elseif (preg_match('/^[_a-zA-Z]$/', $char)){//is varable,command, ...
                $data=$this->read_varable($i,$end);
                $out[]=$data['code'];
                $i=$data['index'];
            }
        }
        $this->index = $this->length;
        return $out;
    }
    private function read_varable($start,$end){
        $out=substr($this->source, $start, 1);
        for($i=$start+1;$i<$end;$i++){
            $char = substr($this->source, $i, 1);
            if (preg_match('/^[_a-zA-Z0-9\\.]$/', $char)){
                $out.=$char;
            }else{
                return ['index'=>$i-1,'code'=>$out];
            }
        }
        return ['index'=>$end,'code'=>$out];
        
    }
    
    private function read_number($start,$end){
        $out='';
        for($i=$start;$i<$end;$i++){
            $char = substr($this->source, $i, 1);
            if(is_numeric($out.$char)){
                $out.=$char;
            }else{
                return ['index'=>$i-1,'code'=>$out];
            }
        }
        return ['index'=>$end,'code'=>$out];
    }
    
    private function read_operator($start,$end){
        $out='';
        for($i=$start;$i<$end;$i++){
            $char = substr($this->source, $i, 1);
            if(in_array($char, self::$OPERATORS)){
                $out.=$char;
            }else{
                return ['index'=>$i-1,'code'=>$out];
            }
        }
        return ['index'=>$end,'code'=>$out];
    }
    
    private function read_string($start,$end){
        $out='"';
        for($i=$start+1;$i<$end;$i++){
            $char = substr($this->source, $i, 1);
            if ($char == '"') {
                return ['index'=>$i,'code'=>$out.'"'];
            } elseif($char == "\\") {
                $char2=substr($this->source, $i+1, 1);
                $i++;
                switch ($char2) {
                    case 'n':
                        $out.= "\n";
                        break;
                    case 'r':
                        $out.= "\r";
                        break;
                    case 't':
                        $out.= "\t";
                        break;
                    case '"':
                        $out.= '"';
                        break;
                    case "\\":
                        $out.= "\\";
                        break;
                }
            } elseif($char == "\n" or $char == "\r" or $char == "\t") {
                continue;
            } else {
                $out.= $char;
            }
        }
        return [
            'index'=>$i,
            'code'=>$out.'"'
        ];
    }
}

/**
 * Parser class
 * this class return AST tree for Boof
 *
 */
class Boof_Parser{
    private $lexer=null;
    private $item;
    private static $CONDITION=['>','>=','==','!=','<','<='];
    private static $OPERATION=['+','-','*','/','%','~'];
    
    public function parse($source) {
        $this->lexer = new Boof_Scanner($source);
        $this->item=$this->lexer->next();
        $code = $this->parse_block();
        return $code;
    }
    private function parse_block() {
        $out = [];
        $i=0;
        while (!is_null($this->item)) {
            if ($this->item['type'] == 'text') {
                if($i!=0 and !is_array($out[$i-1])){
                    $out[$i-1]=$out[$i-1] . $this->item['value'];
                }else{
                    $out[$i] = $this->item['value'];
                    $i++;
                }
            } else {
                $code = $this->item['value'][0];
                switch ($code) {
                    case 'if':
                        $out[$i] = $this->parse_if();
                        break;
                    case 'for':
                        $out[$i] = $this->parse_for();
                        break;
                    case 'macro':
                        $out[$i] = $this->parse_macro();
                        break;
                    case 'capture':
                        $out[$i] = $this->parse_capture();
                        break;
                    case 'else':
                        return $out;
                        break;
                    case 'end':
                        return $out;
                        break;
                    default:
                        $out[$i] =$this->parse_command();
                        break;
                }
                $i++;
            }
            $this->item=$this->lexer->next();
        }
        return $out;
    }
    private function parse_command(){
        $code=$this->item['value'];
        if ($code[0]=='var'){
            $var=$code[1];
            $code=$this->clean_function(array_slice($code, 3));
            array_unshift($code,'var', $var);
            return ['code'=>$code];
        }else{
            $code=$this->clean_function($code);
            return ['code'=>$code];
        }
    }
    
    private function clean_function($code){
        $len=count($code);
        $iscommand=false;
        for($i=1;$i<$len;$i=$i+2){
            if(in_array($code[$i], self::$OPERATION)){
                $iscommand=true;
            }else{
                $iscommand=false;
                break;
            }
        }
        if($iscommand){
            array_unshift($code , '=');
            return $code;
        }
        return $code;
    }
    
    private function parse_if() {
        $is_error=false;
        $out = [
            'code' => $this->item['value'],
            'block' => [],
            'else' => []
        ];
        if(count($this->item['value'])==2){
            
        }elseif(count($this->item['value'])==4){
            if(! in_array($this->item['value'][2], self::$CONDITION)) {
                $is_error=true;
            }
        }else{
            $is_error=true;
        }
        
        $this->item=$this->lexer->next();
        $out['block'] = $this->parse_block();
        if (!is_null($this->item)) {
            if ($this->item['value'][0] == 'else') {
                $this->item=$this->lexer->next();
                $out['else'] = $this->parse_block();
            }
            if (!is_null($this->item)) {
                if ($this->item['value'][0] != 'end'){
                    $is_error=true;
                }
            }else{
                $is_error=true;
            }
        }else{
            $is_error=true;
        }
        if($is_error){
            return '';
        }
        return $out;
    }
    private function parse_for() {
        $is_error=false;
        $out = [
            'code' => $this->item['value'],
            'block' => [],
            'else' => []
        ];
        if(count($this->item['value'])==4){
            if($this->item['value'][2]!='in') {
                $is_error=true;
            }
            
        }else{
            $is_error=true;
        }
        $this->item=$this->lexer->next();
        $out['block'] = $this->parse_block();
        if (!is_null($this->item)) {
            if ($this->item['value'][0] == 'else') {
                $this->item=$this->lexer->next();
                $out['else'] = $this->parse_block();
            }
            if (!is_null($this->item)) {
                if ($this->item['value'][0] != 'end'){
                    $is_error=true;
                }
            }else{
                $is_error=true;
            }
        }else{
            $is_error=true;
            
        }
        if($is_error){
            return '';
        }
        return $out;
    }
    
    private function parse_macro() {
        $is_error=false;
        $out = [
            'code' => $this->item['value'],
            'block' => []
        ];
        if(count($this->item['value'])<=2){
            $is_error=true;
        }
        $this->item=$this->lexer->next();
        $out['block'] = $this->parse_block();
        if (!is_null($this->item)) {
            if ($this->item['value'][0] != 'end'){
                $is_error=true;
            }
        }else{
            $is_error=true;
        }
        if($is_error){
            return '';
        }
        return $out;
    }
    
    private function parse_capture() {
        $is_error=false;
        $out = [
            'code' => $this->item['value'],
            'block' => []
        ];
        if(count($this->item['value'])!=2){
            $is_error=true;
        }
        $this->item=$this->lexer->next();
        $out['block'] = $this->parse_block();
        if (!is_null($this->item)) {
            if ($this->item['value'][0] != 'end'){
                $is_error=true;
            }
        }else{
            $is_error=true;
        }
        if($is_error){
            return '';
        }
        return $out;
    }
}


class Boof_VM{
    private $boof;
    
    private $bultins = [];
    private $macros = []; //macro
    private $functions=[];
    public function __construct($boof,$functions=[]) {
        $this->boof=$boof;
        $this->functions=$functions;
        
        $this->bultins['=']       = [$this, 'fun_calc'];
        $this->bultins['format']       = [$this, 'fun_format'];
        $this->bultins['enum']       = [$this, 'fun_enum'];
        $this->bultins['?']       = [$this, 'fun_smallif'];
        $this->bultins['%']       = [$this, 'fun_url'];
        $this->bultins['&']       = [$this, 'fun_html'];
        $this->bultins['json'] = array($this, 'fun_json');
        
        $this->bultins['layout']  = [$this, 'fun_layout'];
        $this->bultins['import'] = [$this, 'fun_import'];
        
        $this->bultins['split'] = array($this, 'fun_split');
        $this->bultins['sort'] = array($this, 'fun_sort');
        $this->bultins['rsort'] = array($this, 'fun_rsort');
         
    }
    public function render($source,$env=[]){
        $parser=new Boof_Parser();
        $code=$parser->parse($source);
        $content= $this->run_block($code, $env);
        return $content;
    }
    public function view($name, $env = []) {
        $code         = $this->boof->get_code($name);
        $content      = $this->run_block($code, $env);
        $layout=$this->get_value('layout.layout', $env);
        while ($layout!=''){
            if($this->get_value('layout.content', $env)==''){
                $this->set_value('layout.content', $content, $env);
            }
            $env=$this->get_value('layout', $env);
            $code = $this->boof->get_code($layout,"layout");
            if (count($code) != 0) {
                $content= $this->run_block($code, $env);
            }
            $layout=$this->get_value('layout.layout', $env);
        }
        return $content;
    }
    private function run_block(&$code, &$env = []) {
        $out = '';
        for ($i = 0; $i < count($code); $i++) {
            if (is_array($code[$i])) {
                $fun = $code[$i]['code'][0];
                if ($fun == 'for') {
                    $out .= $this->run_for($code[$i], $env);
                } elseif ($fun == 'macro') {
                    $this->add_macro($code[$i], $env);
                } elseif ($fun == 'capture') {
                    $content= $this->run_block($code[$i]['block'], $env);
                    $this->set_value($code[$i]['code'][1], $content, $env);
                } elseif ($fun == 'if') {
                    $out .= $this->run_if($code[$i], $env);
                } else {
                    $out .= $this->run_item($code[$i]['code'], $env);
                }
            } elseif (is_string($code[$i])) {
                $out .= $code[$i];
            }
        }
        return $out;
    }
    private function get_value($name, $envs) {
        $first = substr($name, 0, 1);
        if ($first == '"') {
            return substr($name, 1, -1);
        }elseif(is_numeric($name)){
            return $name+0;
        }
        if ($name == 'true') {
            return true;
        }
        if ($name == 'false') {
            return false;
        }
        
        if (!preg_match('/[a-zA-Z][a-zA-Z0-9\\.]*/', $name)) {
            return $name;//for operator
        }
        $parts  = explode('.', $name);
        $parent = $envs;
        for ($i = 0; $i < count($parts); $i++) {
            if (isset($parent[$parts[$i]])) {
                $parent = $parent[$parts[$i]];
            } else {
                return '';
            }
        }
        return $parent;
    }
    private function set_value($name,$value, &$envs) {
        $parts  = explode('.', $name);
        $parent = & $envs;
        for ($i = 0; $i < count($parts); $i++) {
            if (isset($parent[$parts[$i]])) {
                $parent = & $parent[$parts[$i]];
            } else {
                $parent[$parts[$i]]=[];
                $parent = & $parent[$parts[$i]];
            }
        }
        $parent=$value;
    }
    private function run_for(&$item, &$env) {
        $var_name = $item['code'][1];
        $arr_name = $item['code'][3];
        $arr = $this->get_value($arr_name, $env);
        if (is_array($arr)) {
            $arrayLen=count($arr);
            if (count($arr) == 0) {
                return $this->run_block($item['else'], $env);
            } else {
                $out = '';
                $index = 0;
                $oldFor=null;
                if(isset($env['for'])){
                    $oldFor=$env['for'];
                }
                $env['for']=[];
                $env['for']['count']=$arrayLen;
                
                foreach ($arr as $ai => $av) {
                    $env[$var_name] = $av;
                    $env['for']['index']=$index;
                    $env['for']['key']=$ai;
                    if($index==0){
                        $env['for']['first']=true;
                    }else{
                        $env['for']['first']=false;
                    }
                    if($index+1==$arrayLen){
                        $env['for']['last']=true;
                    }else{
                        $env['for']['last']=false;
                    }
                    $out .= $this->run_block($item['block'], $env);
                    $index++;
                }
                unset($var_name);
                if(is_null($oldFor)){
                    unset($env['for']);
                }else{
                    $env['for']=$oldFor;
                }
                return $out;
            }
        }
        return $this->run_block($item['else'], $env);
    }
    private function add_macro($item, &$vm) {
        if (count($item['code']) >= 2) {
            $this->macros[$item['code'][1]] = $item;
        }
    }
    private function run_if(&$item, &$env) {
        if (count($item['code']) == 2) {
            $var = $this->get_value($item['code'][1], $env);
            if ($var) {
                return $this->run_block($item['block'], $env);
            } else {
                return $this->run_block($item['else'], $env);
            }
        } elseif (count($item['code']) == 4) {
            $var1 = $this->get_value($item['code'][1], $env);
            $oper = $item['code'][2];
            $var2 = $this->get_value($item['code'][3], $env);
            $con  = false;
            switch ($oper) {
                case '>':
                    $con = ($var1 > $var2);
                    break;
                case '<':
                    $con = ($var1 < $var2);
                    break;
                case '==':
                    $con = ($var1 == $var2);
                    break;
                case '!=':
                    $con = ($var1 != $var2);
                    break;
                case '>=':
                    $con = ($var1 >= $var2);
                    break;
                case '<=':
                    $con = ($var1 <= $var2);
                    break;
            }
            if ($con) {
                return $this->run_block($item['block'], $env);
            } else {
                return $this->run_block($item['else'], $env);
            }
        } else {
            return '';
        }
    }
    
    private function run_item(&$item, &$env) {
        $fun = $item[0];
        if($fun == 'var'){
            if(count($item)>=3){
                $name=$item[1];
                $code=array_slice($item, 2);
                $data=$this->run_item($code, $env);
                $this->set_value($name, $data, $env);
            }
            return '';
        } elseif (isset($this->macros[$fun])) {
            return $this->run_macro($item, $env);
        }elseif (isset($this->functions[$fun])) {
            return $this->run_function($item, $env);
        }elseif (isset($this->bultins[$fun])) {
            return $this->run_bultin($item, $env);
        } else {
            return $this->get_value($fun, $env);
        }
    }
    private function run_bultin(&$item, &$env) {
        $par = [];
        $par[]=&$env;
        for ($i = 1; $i < count($item); $i++) {
            $par[] = $this->get_value($item[$i], $env);
        }
        $fun = $this->bultins[$item[0]];
        if (is_callable($fun)){
            return call_user_func_array($fun, $par);
        }
        return '';
    }
    
    private function run_function(&$item, &$env) {
        $par = [];
        for ($i = 1; $i < count($item); $i++) {
            $par[] = $this->get_value($item[$i], $env);
        }
        $fun = $this->functions[$item[0]];
        if (is_callable($fun))
            return call_user_func_array($fun, $par);
        return '';
    }
    
    private function run_macro(&$item, &$env) {
        $func = $this->macros[$item[0]];
        if ((count($func['code']) - 1) < count($item)) {
            return '';
        }
        $par = [];
        for ($i = 1; $i < count($item); $i++) {
            $par[$func['code'][$i + 1]] = $this->get_value($item[$i], $env);
        }
        return $this->run_block($func['block'], $par);
    }
    public function fun_url($env,$a = '') {
        return urlencode($a);
    }
    
    public function fun_html($env,$a = '') {
        return htmlentities($a, ENT_QUOTES, "UTF-8");
    }
    public function fun_enum($env,$id = '') {
        $code=func_get_args();
        $cycle=$id % (count($code)-2);
        return $code[2+$cycle];
    }
    /*
     * format start by %
     * %% is %
     * %s type pure string
     * %h html encode
     * %u url encode
     */
    public function fun_format($env,$format = '') {
        $code=func_get_args();
        $formatlen=strlen($format);
        $datacount=count($code)-2;
        $out='';
        $dataid=0;
        for($i=0;$i<$formatlen;$i++){
            $char=substr($format, $i, 1);
            if($char=='%'){
                if($i+1<$formatlen){
                    $char2=substr($format, $i+1, 1);
                    $data='';
                    if($dataid<$datacount){
                        if(!is_array($code[$dataid+2]))
                            $data=$code[$dataid+2];
                    }
                    switch ($char2) {
                        case '%':
                            $out.='%';
                            break;
                        case 's':
                            $out.=$data;
                            $dataid++;
                            break;
                        case 'h':
                            $out.=htmlentities($data, ENT_QUOTES, "UTF-8");
                            $dataid++;
                            break;
                        case 'u':
                            $out.=urlencode($data);
                            $dataid++;
                            break;
                    }
                    $i++;
                }
            }else{
                $out.=$char;
            }
        }
        return $out;
    }
    
    public function fun_calc($env,$a = '') {
        $code=func_get_args();
        $len=count($code);
        $out = '';
        for ($i = 1; $i < $len ; $i++) {
            $a = $code[$i].'';
            switch ($a) {
                case '+':
                    if(($i+1) < $len){
                        $out= $out + $code[$i+1];
                        $i++;
                    }
                    break;
                case '-':
                    if(($i+1) < $len){
                        $out= $out - $code[$i+1];
                        $i++;
                    }
                    break;
                case '*':
                    if(($i+1) < $len){
                        $out= $out * $code[$i+1];
                        $i++;
                    }
                    break;
                case '/':
                    if(($i+1) < $len){
                        $out= $out / $code[$i+1];
                        $i++;
                    }
                    break;
                case '~':
                    if(($i+1) < $len){
                        $out= $out . $code[$i+1];
                        $i++;
                    }
                    break;
                case '%':
                    if(($i+1) < $len){
                        $out= $out % $code[$i+1];
                        $i++;
                    }
                    break;
                default:
                    if(! is_array($a))
                        $out.= $a;
                        break;
            }
        }
        return $out;
    }
    
    public function fun_smallif($env,$con = false, $is = '', $els = '') {
        if ($con) {
            return $is;
        } else {
            return $els;
        }
    }
    public function fun_json($env,$table=[]){
        return json_encode($table);
    }
    public function fun_split($env,$str="",$spacer=" "){
        return explode($spacer,$str);    
    }
    public function fun_sort($env,$array=[],$field=""){
        $arr=$array;
        if($field==''){
            sort($arr);
            return $arr;
        }else{
            usort($arr, function($a, $b) use ($field){return $a[$field]> $b[$field];});
            return $arr;
        }
    }
    public function fun_rsort($env,$array=[],$field=""){
        $arr=$array;
        if($field==''){
            rsort($arr);
            return $arr;
        }else{
            usort($arr, function($a, $b) use ($field){return $a[$field]< $b[$field];});
            return $arr;
        }
    }
    public function fun_layout(&$env,$layoutname = '') {
        $this->set_value('layout.layout', $layoutname, $env);
    }
    
    public function fun_import($env,$obj) {
        $code = $this->boof->get_code($obj,"import");
        return $this->run_block($code, $env);
    }
}
/**
 * Boof class
 * main class of template engine
 *
 */

class Boof{
    private $functions=[];
    private $path="";
    private $import_path="";
    private $layout_path="";
    public function __construct($path,$import_path=null,$layout_path=null) {
        $this->path=$path;
        $this->import_path=$import_path;
        $this->layout_path=$layout_path;
        if (is_null($import_path)) {
            $this->import_path=$path;
        }
        if(is_null($layout_path)){
            $this->layout_path=$path;
        }
    }
    public function view($name,$env=[]) {
        $vm=new Boof_VM($this,$this->functions);
        return $vm->view($name,$env);
    }
    public function render($src,$env=[]) {
        $vm=new Boof_VM($this,$this->functions);
        return $vm->render($src,$env);
    }
    
    public function add_function($name, $func) {
        $this->functions[$name] = $func;
    }
    /**
     *
     * @param string $name
     * @param string $type enum ="page"|"import"|"layout"
     * @return array
     */
    public function get_code($name,$type="page"){
        if ($this->need_recompile($name,$type)){
            $source=$this->read_source($name,$type);
            if($source!=''){
                $parser=new Boof_Parser();
                $code=$parser->parse($source);
                $this->write_code($name, $code,$type);
                return $code;
            }else{
                return [];
            }
        }
        return $this->read_code($name);
    }
    ////////////////////////////////////
    private function need_recompile($name,$type="page"){
        if($type=='import'){
            $file=$this->import_path;
        }elseif ($type=='layout'){
            $file=$this->layout_path;
        }else{
            $file=$this->path;
        }
        if(substr($name,0,1)!='/'){
            $file.='/';
        }
        $file.=$name;
        $file=str_replace('/', DIRECTORY_SEPARATOR, $file);
        if(!file_exists($file)){
            $file.='.html';
        }
        if(file_exists($file)){
            $cache=$file.'.json';
            if(file_exists($cache)){
                if (filemtime($cache) >= filemtime($file)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
    
    private function read_source($name,$type="page"){
        if($type=='import'){
            $file=$this->import_path;
        }elseif ($type=='layout'){
            $file=$this->layout_path;
        }else{
            $file=$this->path;
        }
        if(substr($name,0,1)!='/'){
            $file.='/';
        }
        $file.=$name;
        $file=str_replace('/', DIRECTORY_SEPARATOR, $file);
        if(!file_exists($file)){
            $file.='.html';
        }
        if(file_exists($file)){
            $data = file_get_contents($file);
            if ($data !== false) {
                return $data;
            }
        }
        return "";
    }
    
    private function read_code($name,$type="page"){
        if($type=='import'){
            $file=$this->import_path;
        }elseif ($type=='layout'){
            $file=$this->layout_path;
        }else{
            $file=$this->path;
        }
        if(substr($name,0,1)!='/'){
            $file.='/';
        }
        $file.=$name;
        $file=str_replace('/', DIRECTORY_SEPARATOR, $file);
        if(!file_exists($file)){
            $file.='.html';
        }
        $file=$file.'.json';
        if(file_exists($file)){
            $data = file_get_contents($file);
            if ($data !== false) {
                $code = @json_decode($data, true);
                if ($code !== false) {
                    return $code;
                }
            }
        }
        return [];
    }
    
    private function write_code($name, $code,$type="page"){
        if($type=='import'){
            $file=$this->import_path;
        }elseif ($type=='layout'){
            $file=$this->layout_path;
        }else{
            $file=$this->path;
        }
        if(substr($name,0,1)!='/'){
            $file.='/';
        }
        $file.=$name;
        $file=str_replace('/', DIRECTORY_SEPARATOR, $file);
        if(!file_exists($file)){
            $file.='.html';
        }
        $file=$file.'.json';
        @file_put_contents($file, json_encode($code));
        
    }
}

?>