# Arrayz
Array manipulation library for Codeigniter 3.x and Non-Framework PHP

Usage Instructions:
------------------------
Created for two dimensional associative array / result array from codeigniter.

1. Load library and create instance: 

	**$this->load->library('Arrayz');**

	For CI,

	**$arrayz = $this->arrayz;**

	For Non-Framework PHPs, include the file. create instance, by following,

	**$arrayz = new Arrayz;**

2. After instance created,You can use as following,

	**$arrayz($array)->where('id','1')->get();**

3. **get() is required to return the output array/value.**   

Example Array:
--------------

$array = array (
  0 => 
  array (
   'id' =>'11',   
   'Name' =>'Giri',
   'SSN' =>'123524',   
   'street' =>'17 west stree',
   'state' =>'NY',
   'created_date' =>'0000-00-00 00:00:00',
  ),
  1 => 
  array (
   'id' =>'11',   
   'Name' =>'Anna',
   'SSN' =>'56789',   
   'street' =>'18 west stree',
   'state' =>'CA',
   'created_date' =>'0000-00-00 00:00:00',
  ),
);

select_where:
------------
	
      $arrayz($array)->select_where('id,name', ['id'=> '1'])->get(); 
      
      //Select the key found returns  id, name  and check the condition as id is equal to 1.
      
      $arrayz($array)->select_where('name,state', ['id >' => '1'], TRUE)->order_by('state', 'ASC')->get();
      
      //Preserve the key, select and filter it. Order by the array state
     
select:
-------
	
      $arrayz($array)->select('id,name')->get(); 
      
      //Select the key found returns  id, name

      //When using select with where, passed select key must be in where condition or else will skip the array. 

      //To prevent this, you can chain as like following,

      $arrayz($array)->where('state')->select('Name,SSN')->get();

      //Filtered with where and return the selected keys
          
     $arrayz($array)->select('id,name')->where('state', 'CA')->group_by('state')->get();
     
     //Select the ID and name and check that stats is equal to CA. we can chain almost all methods by this.


Pluck:
------    
      $arrayz($array)->pluck('st')->get(); 

      //Support RegEx key which are matching 'st' and returns street, state          
       
      Most usable case is When Posting ($_POST) Iterator based elements. Ex., count_1, count_2

Where:
------
      $arrayz($array)->where('id' ,'1')->get(); 

      // Will return the array where matches id is 1 

      $arrayz($array)->where('id' ,'>','3')->get(); 

      //Will return the array where id is greater than 3, =,!=, >, <>, >=, <=, === operators are supported. By default '='.

      $arrayz($array)->where('id' ,'>','3', TRUE)->get();

      //Preserve the actual key

      $arrayz($array)->where(['id >' => '3', 'name'=> 'Giri'])->get();

      //Multiple conditions. Similar to CI query builder where.

WhereIn: 
------
      $arrayz($array)->whereIn( 'id', ['1','3'] )->get(); 

      // Will return the array where matches id is 34 and 35

      $arrayz($array)->whereIn( 'id', ['1','3'], TRUE )->get(); 

      // Will return the array where matches id is 34 and 35 and preserve the actual key

WhereNotIn: 
------
      $arrayz($array)->whereNotIn('id', ['34','35'] )->get(); 

      // Will return the array where not matches id is 34 and 35

      $arrayz($array)->whereNotIn('id', ['34','35'], TRUE )->get(); 

      // Will return the array where not matches id is 34 and 35
      
group_by: 
---------
      Groupby by mentioned Key, similar to sql;
      
      $arrayz($array)->group_by('id')->get(); 

      // Will return the array group by by fmo id
      //using get_row() with group_by will return the array with 0 index.
      
order_by: 
---------
      Groupby by mentioned Key, similar to sql;
      
      $arrayz($array)->where( ['id >', '2 ])->order_by('name', 'asc')->get(); 

      // Will return the array based on where condition sort the array by the name

limit:
------
      $arrayz($array)->limit(10)->get(); 

      //Will return the first 10 elements

      $arrayz($array)->limit( 10, 5)->get(); 

      //Will return the 10 elements after the 5 the index (Offset)

like:
------
      $arrayz($array)->like('SSN', '01')->get(); 

      //Will return the elements SSN number having 01, in anywhere of the string. similar to %like% in mysql.
      
select_min:
----------
      
      $arrayz($array)->select_min('id')->get(); 

      //Will return minimum id value      
      
      $arrayz($array)->select_min('id', TRUE)->get(); 

      //Will return minimum id value's array
      
select_max:
----------
      
      $arrayz($array)->select_max('id')->get(); 

      //Will return maximum id value      
      
      $arrayz($array)->select_max('id', TRUE)->get(); 

      //Will return maximum id value's array           
      
select_avg:
----------
      
      $arrayz($array)->select_avg('id')->get(); 

      //Will return calculate the average of the id as value      

      $arrayz($array)->select_avg('id', 2)->get(); 

      //Will return calculate average of the id and round off it to 2

select_sum:
----------
      
      $arrayz($array)->select_sum('id')->get(); 

      //Will sum the id value 
      
distinct:
----------
      
      $arrayz($array)->distinct('id')->get(); 

      //remove duplicate id array and return distinct

get_row:
----------
      
      $arrayz($array)->where('id','<', '2')->get_row(); 

      //Return the single array, similar to limit(1)

toJson:
----------
      
      $arrayz($array)->where('id','<', '2')->toJson(); 

      //Return the output as json_encode
      
      
contains:
--------- 
      $arrayz($array)->contains('id','34')->get(); 

      //Search for the value id in 34. if found return true else false.

      $arrayz($array)->contains('34')->get(); 

      //Search for the value 34. if found return true else false.

collapse:
---------
      $arrayz($array)->collapse($array)->get();

      //flatten multidimensional array into single array

has:
----
      $arrayz($array)->has('id')->get(); 

      //When the key found returns true

Keys:
----
      $arrayz($array)->keys()->get(); 

      //Returns the key of the array. similar to array_keys

Values:
-------
      $arrayz($array)->values()->get(); 

      //Returns the values of the array. similar to array_values

Count:
------
     $arrayz($array)->count(); 

     //Returns the no of array/elements based on the array. similar to array count()


This is initiation to show, we can integrate or acheive all frameworks features in Codeigniter and Non-Framework PHP.
