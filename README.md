<div align="center">
  <p><b>B O O F . P H P</b></p></a>
  <p>Boof is a simple and fast template engine for use on the web (for php)</p>
</div>

# boof.php

Boof is a simple and fast template engine for use on the web (for php)

## feature

1. simple
1. single file (only include Boof.php and make instance of Boof class )
1. Codec utf-8 without BOM
1. The template consists of two parts
     - tag
     - static part
1. Tag components only with a ascii code
1. Ability to use Unicode within the tag within the static string
1. Use caching compile result to improve performance(in json file on template directory)
1. seprate path for view , import , layout
1. only work by utf8 file format (better don't use BOM)
1. use global namespace on php

# Boof class

## Boof(view_path,import_path=null,layout_path=null)

- view_path : directory of template files
- import_path : directory of import files for use by import bultin. if didn't set,use view path
- layout_path : directory of layout files for use by layout bultin. if didn't set,use view path

```php
$viewPath= dirname ( __FILE__ ).'/views' ;
// import and layout file in view path
$boof=new Boof($viewPath);
```
## method

### view(name,env=[]) 

render template file and return rendering text

* name : file name with extension (can, don't write ".html" extension ),for directory seprator use "/"
* env : associative array for data 

```php
$boof->view('users/list',['title'=>'user list','items'=>$lists]);
```

### render(src,env=[]) 

render template text and return rendering text

* src : template text
* env : associative array for data 


```php
$boof->render('<h1>{{title}}</h1>',['title'=>'user list']);
```

### add_function(name, func)

add external function in template engine

* name : name of function on template
* func : callable element for used to run ,return reader text

```php
$boof->add_function("hello",function($name){return "hello ,".$name; });
echo $boof->render('{{hello "seyed rahim" }}');
```

## other class used inner Boof.php file

- Boof_Scanner
- Boof_Parser
- Boof_VM

---

# Boof Language 

## TAG

The tag is a piece of code that we have between the two symbols {{,}} and parts separated by space , The tag part run by template engine then return result

## static part

Each section of the template that is not a tag is a static part. static part appear without computation in the output.

example

```
hello , <b> {{ your.name }} </b>
{{format "%s-%s-%s" 2019 1 1 }}
```

if define

your.name = "Seyed Rahim Firouzi"

output

```
hello , <b> Seyed Rahim Firouzi </b>
2019-1-1
```


## Whitespace 

If the tag is followed by the start with - and before the end with - the previous and the following space are deleted.

example

```
123
     {{- var a = "line2" -}}      
line3
```

output

```
1234
line3
```

## Variable

Values are specified by the variables to the template engine or at the template level. The variable name follows the standard C programming language and is used to access item of objects from the point. For item of array is used for pointing. Also, varible is dynamic variables ,ability to change the type automatically.

example

```
ali  
object.name
array.2
layout.content
red5
```

## Variable

Each programming language needs to define a variable, and thus defines the type of variable. The Boof Template language is also not excluded. Variants of variables are

### null

This type of variable in essence means no definition or non-existence, and in practice it does not have meaning. It becomes a string "".

example

```
{{var data = null }}
```

### bool

The boolean type is a variable with the correct or false value. And only these two values are taken

- true
- false

example

```
{{ var data = true}}
```

### number

Any integer or decimal number

sample

```
{{ var data = 12.34 }}
```

### string

A string that can be composed of control character and unicode control agents. To define the values of a string in "," . Controls character are:

- \n  new line
- \r  end line
- \\"  " character 
- \\\  \ character 
- \t  tab character

sample

```
{{ var data = "this is a book \n این یک کتاب هست" }}
```

sample

```
{{ null }} 
{{ true }} 
{{ 12.34 }}
{{ "hellow \t world" }}
```

## falsy value

maybe used value in control flow object,must design way to convert to boolean type ,every value is true .only table value convert to false

falsy table

|  type |   value    |
|:-----:|:----------:|
|null   |null        |
|bool   |false       |
|number |0           |
|string |""          |
|array  |empty array |
|object |empty object|

## Control elements 

### if

for define condition in run flow ,
use two type 

- with opereator
- without opereator

```
{{ if ...}}
 part run if condition is true 
{{ else }}
 part run if condition is false 
{{end}}
```

can remove else parts

```
{{ if ...}}
 part run if condition is true 
{{end}}
```

#### with opereator

this mode use one of operator for check or condition

```
{{ if var1 operator var2}}
 part run if condition is true 
{{ else }}
 part run if condition is false 
{{end}}
```

operator list
 1. (==) check elements equal
 2. (!=) check elements don't equal
 3. (>) check elements greater
 4. (<) check elements smaller
 5. (>=) check elements greater or equal
 6. (<=) check elements smaller or equal
 
sample

```
{{ var garde = 20 }}
{{ if grade >= 3 }}
   this is best grade
{{else}}
    this is bad grade
{{end}}
```
output

```
   this is best grade
```

#### without operator

this mode only one value used by control flow,if is Truthy,run true elements.or run else part

```
{{ if var1 }}
 part run if condition is true 
{{ else }}
 part run if condition is false 
{{end}}

```

sample

```
{{var holiday = true}}
{{ if holiday }} chose best day,{{end}}
```
output

```
 chose best day,
```

### for 

used "for" for define loop in template engine,in loop define variable automatically for use better than loop.

```
{{ for var1 in array}}
  block run for element of array
{{else}}
if array is empty run this block
{{end}}
```
this variable define in loop block 

| variable  |             use           |
|:---------:|:-------------------------:|
|for.index  |number of repeat in loop   |
|for.key    |key on current value       |
|for.first  |in first time is true      |
|for.last   |in last time is true       |

first elemnt index equal 0

```
{{ var list = split "1,2,3,4" "," }}
{{ for ele in list }}
   {{ for.index + 1 }} - {{ ele }} <br/>
{{ end }}
```

output

```
1 - 1 <br/>
2 - 2 <br/>
3 - 3 <br/>
4 - 4 <br/>
```

### macro

used to define a function within the template. this function is sandbox and completely independent on the variable outside the function.

```
{{macro macroname param1 param2 ... }}
   body of macro
{{end}}

```
- macroname macro name
- param1  parameter one
- param1 parameter two
- ...

for call

```
{{macroname param1 param2 ...}}
```
- macroname macro name
- param1  parameter one
- param1 parameter two
- ...

sample

```
{{macro call a b}}
   {{a}} call {{b}} for work <br/>
{{end}}

{{ call "seyed rahim" "ali"}}
{{ call "bob" "alice"}}

```
output

```

seyed rahim call ali for work <br/>
bob call alice for work <br/> 
```

### capture

reander book of code and save in varable

```
{{capture varname }}
   caputre of macro
{{end}}

```
- varname : varible name for save rendered block on it 

```
{{capture var }}
   hello,seyed rahim
{{end}}

```

var content is "hello,seyed rahim"

## items

### var

used for set value on variable

```
var variableName = data
```

- variableName variable Name
- data value for set in variable

don't return any things

in right side you can use:
1. static value
2. calculate operator
3. Bultin
4. user native function
5. macro

```
{{ var name = "salam"}}
{{ var age = 5 + 1 }}
{{ var day = ? ligth  true false }}
{{ var html = ! "<>&" }}
{{ var data = userFunc p1 p2 p3 }}
{{ var form = macroName p1 p2 p3 }}
```

### calculate operator

used for simple calculate , first operator is high priority

1. (+) equal mathematical +
2. (-) equal mathematical -
3. (/) equal mathematical /
4. (*) equal mathematical *
5. (%) equal mathematical remaining
6. (~) concat string mode

```
{{ 1 + 2 * 3}}
{{5 % 2}}
{{ "hello " ~ "world"}}
```

output 

```
9
1
hello world
```

### Bultin

function define in template engine ,list of bultin

1. ?
1. & 
1. %
1. enum
1. format
1. layout
1. import

for use from  function,builtin or macro you must type function name and separate parameter after name by space, for operator don't need space after operator

#### 

for use fast way from if/else

```
{{ ? condtion truePart FalsePart }}
```

- condtion condtion
- truePart part return if condition is true 
- FalsePart part return if condition is true 

sample

```
{{ ?  true  "this is true"  "this is false"}}
```

output:

```html
this is true
```

#### &

used for encode to html (for special character like <,>,& )

```
{{ & data }}
```
- data data for decode to html

codic string print in output

#### enum 

used for convert number to case of value

```
enum <number> <case zero> <case one> <case two>

```

- number number for mapping
- caseZero if number is zero return this case
- caseOne if number is one return this case
- caseTwo if number is two return this case
- ...

#### %

used for encode to url (for special character like space )

```
{{ % data }}
```

- data data for decode to url

codic string print in output

#### split

split string by seprator and return array(used by var)

```
split string seprator
```
1. string for split
1. seprator 

```
{{var arr=split "1,2,3,4,5" ","}}
```
arr = [1,2,3,4,5]

#### json

convert element to json string

```
json obj
```
1. obj : object for convert json string

```
{{var obj=split "1,1" ","}}
{{json obj}}
```

output:

```json
["1","1"] 
```

#### sort,rsort

for sort array(and reverse sort =rsort) and return array(used by var)

```
sort arr field
```
1. arr :array for sort
1. field : field name for sort

```
{{var a= split "1 3 5 4 2 7" " "}}
{{var sa=sort a }}
```
if array is array of associative array ,by can sort by field

```
{{var list=sort user "name" }}
```

#### format 

used for formated text,like c language but by different elements

list of elements
1. %s used paramter without any encode
2. %h used paramter with html encode
3. %u used paramter with url encode

```
format formatedString p1 p2 p3 ...
```

- formatedString format string 
- p1 parameter one
- p2 parameter two
- p3 parameter 3
- ...

sormated string print

example

```
{{ ? true "true" "false" }}
{{ & "go->" }} 
{{ enum 2 zero first second }}
{{ % "a b"}}
{{ format "%s:%s:%s %h" 12 24 30  ">"}}
```

output

```html
true
go-&gt;
second
a+b
12:24:30 &gt;
```

#### import

import other template file and run it

```
{{import "header"}}
```

1. for directory separator use "/"
1. file name with extension (can, don't write ".html" extension )
1. import method run in runtime
1. use file in import directory

#### layout

used for set layout.layout is other page ,this page render than replace in layout page

1. this page set in "content" variable in layout page
1. other variable in layout page set in page by "layout." before name(sample : "title" -> in main page "layout.title")
1. if seted value in "layout.content" ,don't use page resoult for output
1. this command like set valve in "layout.layout" 
1. use file in layout directory

# sample

lay.html

```
<div>{{title}}</div>
<div>
{{content}}
</div>
```

header.html

```
<h1>header</h1>
```

main.html

```
{{layout "lay" //equal var layout.layout = "lay"}}
{{import "header"}}
{{var layout.title = "hello"}}
seyed rahim
```

 main page output

```html
<div>hello</div>
<div>
<h1>header</h1>
seyed rahim
</div>
```

### native function 
The functions that are introduced from the outside of the template engine are the calling method, such as the macro

for call

```
{{fun p1 p2 ...}}
```

- fun  native functin name
- p1 parameter one
- p2 parameter two
- ...

### variable

If the name of a variable or a static value used in a tag, its values are printed in the output without any spaces.

sample

```
{{ "hello,world"}}
```

output

```
hello,world
```
