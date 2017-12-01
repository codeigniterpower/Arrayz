<?php namespace CodeIgniter\Arrayz;
/**
* Array Manipulations
* Contributor - Giri Annamalai M
* Version - 1.2
*/
class Arrayz
{
	private $source;
	private $operator;
	public function __construct($array=[])
	{
		$this->source = [];
		if($this->_chk_arr($array))
		{
			$this->source = new SplFixedArray(sizeof($array));
			// $this->source = $array;
		}
	}

	/*
	* Object to callable conversion
	*/
	public function __invoke($source=[])
	{
		$this->source = $source;
		return $this;
	}

	private function _chk_arr($array)
	{
		if(is_array($array) && count($array) > 0 )
		{
			return true;
		}
	}

	/*
	* Like SQL Where . Supports operators. @param3 return actual key of element
	*/
	public function where()
	{
		$args = func_get_args();		
		$source=[];
		if(is_string($args[0]))
		{
			if(func_num_args() == 2)
			{				
				$o[] = $args[0];
				$o[] = '=';
				$o[] = $args[1];				
				$this->conditions[0] = $o;
			}
			if(func_num_args() == 3 || func_num_args() == 4){
				$this->conditions[0] = $args;
			}
			$preserve = $args[3] ?? FALSE;
		}else if(is_array($args[0])) {			
			$this->conditions = $this->format_conditions($args[0]);
			$preserve = $args[1] ?? FALSE;
		}
		$type_select = 'resolve_where'.count($this->conditions);
/*		foreach($this->source as $k=>$v) {
			$this->{$type_select}($v, $k) ? $source[] = $v : '';
		}*/
		$search_key = $this->conditions[0][0]; 
		$search_value = $this->conditions[0][1]; 
		
		switch ($this->conditions[0][1]) {
		    default:
		    case '=':
		    case '==':   $fn = 'eq';
		    case '!=':	
		    case '<>':  $fn = 'neq';
		    case '<':   $fn = 'lt';
		    case '>':   $fn = 'gt';
		    case '<=':  $fn = 'lteq';
		    case '>=':  $fn = 'gteq';
		    case '===': $fn = 'eq3';
		    case '!==': $fn = 'neq3';
		}
		$eq = function($v, $k)
		{
			return $v > $k;
		};		
		$neq = function($v, $k)
		{
			return $v != $k;
		};		
		$lt = function($v, $k)
		{
			return $v < $k;
		};
		$gt = function($v, $k)
		{
			return $v > $k;
		};
		$lteq = function($v, $k)
		{
			return $v <= $k;
		};
		$gteq = function($v, $k)
		{
			return $v >= $k;
		};

		$eq3 = function($v, $k)
		{
			return $v === $k;
		};

		$neq3 = function($v, $k)
		{
			return $v !== $k;
		};		
		if(count($this->conditions)==1)
		{			
			foreach($this->source as $k=>$v)
			{
				$$fn($v[$search_key],$search_value) ? $source=$v : '';
			}			
		}

		// $source = array_filter($this->source, array($this, $type_select), ARRAY_FILTER_USE_BOTH);
		$this->_preserve_keys($source, $preserve);		
		return $this;
	}

	public function resolve_where1($v, $k)
	{	
		return $v[$this->conditions[0][0]] > 100 && $v['cap_type']=='AR';
		// return $this->_operator_check($v[$this->conditions[0][0]], $this->conditions[0][1], $this->conditions[0][2]);
	}		

	public function resolve_where2($v, $k)
	{				
		return $this->_operator_check($v[$this->conditions[0][0]], $this->conditions[0][1], $this->conditions[0][2]) && $this->_operator_check($v[$this->conditions[1][0]], $this->conditions[1][1], $this->conditions[1][2]);
	}	

	public function resolve_where3($v, $k)
	{				
		return $this->_operator_check($v[$this->conditions[0][0]], $this->conditions[0][1], $this->conditions[0][2]) && $this->_operator_check($v[$this->conditions[1][0]], $this->conditions[1][1], $this->conditions[1][2]) && $this->_operator_check($v[$this->conditions[2][0]], $this->conditions[2][1], $this->conditions[2][2]);
	}	

	public function resolve_where4($v, $k)
	{				
		return $this->_operator_check($v[$this->conditions[0][0]], $this->conditions[0][1], $this->conditions[0][2]) && $this->_operator_check($v[$this->conditions[1][0]], $this->conditions[1][1], $this->conditions[1][2]) && $this->_operator_check($v[$this->conditions[2][0]], $this->conditions[2][1], $this->conditions[2][2]) && $this->_operator_check($v[$this->conditions[3][0]], $this->conditions[3][1], $this->conditions[3][2]);
	}	

	public function resolve_multi_where($src_v, $src_k)
	{
		$resp = FALSE;
		foreach ($this->conditions as $k => $v) {
			$resp = ($this->_operator_check($src_v[$v[0]], $v[1], $v[2])) ?? $resp;			
			if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
		}
		return $resp;
	}

	private function format_select($select='')
	{		
		$select = array_map('trim', explode(",", $select));
		$this->select_fields = count($select) == 1 ? $select[0] : array_flip($select);
	}

	private function format_conditions($cond='')
	{
		$o = []; $i=0;
		array_walk($cond, function($v, $k) use (&$o, &$i) {
			$key = array_map('trim', explode(" ", $k));			
			$key[1] = $key[1] ?? '='; //Default is =			
			$key[2] = $v;			
			$o[$i] = $key;
			$i++;
		});
		return $o;
	}

	/*
	* Converting Two Dimensional Array with lImit offset 
	*/
	public function limit()
	{
		$args = func_get_args();
		$limit = ($args[0]!=1) ? $args[0]+1 : $args[0];
		$offset = isset ($args[1]) ? $args[1] : 0;		
		$preserve = isset($args[2])  ? $args[2] : TRUE;		
		$this->source = array_slice($this->source, $offset, $limit, $preserve);		
		if($limit == 1)
		{
			$this->source = array_values($this->source)[0];
		}
		return $this;
	}

	/*
	* Select the keys and return only them	
	* @param1: 'id, name, address', must be comma seperated.
	*/
	public function select()
	{
		$args = func_get_args();
		$select = $args[0];
		$op = [];		
		$select = array_map('trim', explode(",", $select));
		if(isset($args[1]) && $args[1]==TRUE) //Flat array if only one return key/value exists
		{			
			$this->source = array_column($this->source, $select[0]);			
		}
		else
		{
			$select = array_flip($select);
			array_walk($this->source, function(&$value, &$key) use(&$select, &$op){
				$value = array_intersect_key($value, $select);				
			});
		}		
		return $this;
	}
	
	/*
	* Select the keys and return only them	
	* @param1: 'id'. @param2: name to be the array
	* return the array with id as value, name as key
	*/
	public function select_column()
	{
		$args = func_get_args();
		$select = $args[0];
		$key = $args[1] ?? '';		
		$this->source = ($key!='') ? array_column($this->source, $select, $key) : array_column($this->source, $select);
		return $this;
	}
	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function whereIn()
	{
		$args = func_get_args();
		$op = [];
		$search_key = $args[0];
		$search_value = $args[1];
		$op = array_filter($this->source, function($src) use ($search_key, $search_value) {
			return (isset($src[$search_key])) && in_array( $src[$search_key], $search_value);
		},ARRAY_FILTER_USE_BOTH);
		$preserve = isset($args[2]) && $args[2] ? TRUE : FALSE;
		$this->_preserve_keys($op, $preserve);//Preserve keys or not		
		return $this;
	}

	/*
	* Group by a key value 
	*/
	public function group_by()
	{
		$args = func_get_args();		
		$grp_by = $args[0];
		$op = [];
		foreach ($this->source as $data) {
		  $grp_val = $data[$grp_by];
		  if (isset($op[$grp_val])) {
		     $op[$grp_val][] = $data;
		  } else {
		     $op[$grp_val] = array($data);
		  }
		}
		$this->source = $op;
		return $this;
	}

	/*
	* Check with operators
	*/
    private function _operator_check($retrieved, $operator , $value)
	{
		switch ($operator) {
		    default:
		    case '=':
		    case '==':  return $retrieved == $value;
		    case '!=':
		    case '<>':  return $retrieved != $value;
		    case '<':   return $retrieved < $value;
		    case '>':   return $retrieved > $value;
		    case '<=':  return $retrieved <= $value;
		    case '>=':  return $retrieved >= $value;
		    case '===': return $retrieved === $value;
		    case '!==': return $retrieved !== $value;
		}
	}

	/* Return output as Array */
	public function get()
	{		
	 	 if(is_array($this->source) && count($this->source)==0 || !is_array($this->source) && $this->source=='')
	 	 {
	 	 	return NULL;
	 	 }
		return $this->source;
	}
	
	/* Return output as JSON */
	public function toJson()
	{
		return (empty($this->source)) ? NULL : json_encode($this->source);
	}

	/* Return output as Single Row Array */
	public function get_row()
	{				
		if(is_array($this->source) && count($this->source)==0)
		{
			return NULL;
		}
		return array_values($this->source)[0];
	}
	
	/* Return array keys */
	public function keys()
	{		
		$this->source = array_keys($this->source);
		return $this;
	}
	
	/* Return array values */
	public function values()
	{		
		$this->source = array_values($this->source);
		return $this;
	}

	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function whereNotIn()
	{
		$args = func_get_args();
		$op = [];
		$search_key = $args[0];
		$search_value = $args[1];
		$op = array_filter($this->source, function($src) use ($search_key, $search_value) {
			return (isset($src[$search_key])) && !in_array( $src[$search_key], $search_value);
		},ARRAY_FILTER_USE_BOTH);
		$preserve = isset($args[2]) && $args[2] ? TRUE : FALSE;
		$this->_preserve_keys($op, $preserve);//Preserve keys or not		
		return $this;
	}

	/* Select a key and sum its values. @param1: single key of array to sum */
	public function sum()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];
		$this->select($key, TRUE);		
		$this->source = array_sum($this->source);
		return $this->source;
	}

	public function count()
	{
		return count($this->source);
	}

	private function _preserve_keys($op=[], $preserve=TRUE)
	{
		$this->source = ($preserve==TRUE) ? $op : array_values($op);
	}

	/*
	* Orderby by flat/normal array
	*/
	public function order_by()
	{
		$args = func_get_args();
		$op = [];
		$this->to_order = $this->source;
		$sort_mode = ['asc', 'desc'];		
		if(isset($this->source[0]) && is_array($this->source[0])) //Associatove Array
		{	
			$sort_order = ['asc' => SORT_ASC, 'desc' => SORT_DESC];
			$sort_by = $this->select($args[0], TRUE); //Select the key to Sort
			$args[1] = isset($args[1]) ? $args[1] : 'asc';
			array_multisort($sort_by->source, $sort_order[strtolower($args[1])], $this->to_order);		
			$this->source = $this->to_order;
		}
		else
		{
			$args_sort = strtolower($args[0]);			
			$sort_order = ['asc' => 'asort', 'desc' => 'arsort'];
			$sort_order[$args_sort]($this->source);
			$preserve = isset($args[1]) && $args[1] ? TRUE : FALSE;
			$this->_preserve_keys($this->source, $preserve);
		}
		return $this;
	}
	/*
	* Flat Where
	*/
	public function flat_where()
	{
		$args = func_get_args();
		$op = [];
		if(is_string($args[0])) //Single where condition
		{
			$cond = array_map('trim', explode(" ", $args[0]));
			$op = array_filter($this->source, function($src) use ($cond) {								
				return $this->_operator_check($src, $cond[0], $cond[1]);
			});			
			$preserve = isset($args[1]) && $args[1] ? TRUE : FALSE;
			$this->_preserve_keys($op, $preserve);
		}
		return $this;		
	}
	
	/*
	* Similar to Like query in SQL
	*/
	public function like()
	{	
		$args = func_get_args();
		$search_key = $args[0];
		$this->op = [];
		if(is_string($search_key))
		{
			$search_value = $args[1];
			$op = array_filter($this->source, function($src) use ($search_key, $search_value){
					return isset($src[$search_key]) && preg_match('/'.$search_value.'/', $src[$search_key]);
			},ARRAY_FILTER_USE_BOTH);
			$this->source = $op;
		}
		$preserve = isset($args[3]) && $args[3] ? TRUE : FALSE;
		$this->_preserve_keys($op, $preserve);
		return $this;
	}

	/* Select a key and sum its values. @param1: single key of array to sum */
	public function select_sum()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];
		$this->source = array_column($this->source, $key);
		$this->source = array_sum($this->source);
		return $this;
	}

	/* Select the maximum value using the key */
	public function select_max()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];
		$source = $this->source;
		$this->source = array_column($this->source, $key);
		$k = (isset($args[1]) && $args[1]) ? array_keys($this->source, max($this->source))[0] : '';
		$this->source = (isset($args[1]) && $args[1]) ? [$k => $source[$k]] : max($this->source);
		return $this;
	}

	/* Select the min value using the key */
	public function select_min()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];
		$source = $this->source;	
		$this->source = array_column($this->source, $key);
		$k = (isset($args[1]) && $args[1]) ? array_keys($this->source, min($this->source))[0] : '';
		$this->source = (isset($args[1]) && $args[1]) ? [$k => $source[$k]] : min($this->source);
		return $this;		
	}	

	/* Calculate avg value by key. @param2 is round off numeric */
	public function select_avg()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];		
		$this->source = array_column($this->source, $key);
		$this->source = (isset($args[1]) && is_numeric($args[1])) ? round((array_sum($this->source)/count($this->source)), $args[1]) : (array_sum($this->source)/count($this->source));
		return $this;
	}

	/* Select Distinct values*/
	public function distinct()
	{
		$args = func_get_args();		
		$op = [];
		$key = $args[0];
		$source = $this->source;		
		$this->source = array_column($this->source, $key);
		$this->source = $s = array_values(array_flip($this->source));
		array_walk($this->source, function(&$value, &$key) use(&$op, &$source){
			$op[] = $source[$value];
		});	
		$this->source = $op;	
		return $this;
	}

	/*
	* Assign the key from the array value
	* @param1: key, @param2: true, will return with the key value
	*/
	public function assign_key()
	{
		$args = func_get_args();
		$op = [];
		if(!empty($this->source[0]))
		{
			$to_key[] = $args[0];
			if(isset($args[1]) && $args[1])
			{
				$v = $this->source[0];
				$keys = array_keys($v);
				$select = array_diff($keys, $to_key);
				$select = array_flip($select);
				array_walk($this->source, function(&$value, &$key) use(&$select, &$op, &$to_key){
					$op[$value[$to_key[0]]] = array_intersect_key($value, $select);				
				});				
			}
			else
			{
				array_walk($this->source, function(&$value, &$key) use(&$op, &$to_key){
					$op[$value[$to_key[0]]] = $value;
				});				
			}			
           		$this->source = $op;
		}
		return $this;
	}

	/*
	* reverse the array
	*/
	public function reverse()
	{	
		$args = func_get_args();
		$preserve = isset($args[0]) ? $args[0] : TRUE;
		$this->source = array_reverse($this->source, $preserve);
		return $this;
	}	

	/*
	* Combine two arrays of each columns create 
	* @param1: first array, @param2: 2nd array
	*/
	public function join_each()
	{
		$args = func_get_args();
		$op = [];
		if(count($args)==1)
		{
			$i=0;
			$join = array_values($args[0]);			
			array_walk($this->source, function(&$value, &$key) use(&$join, &$op, &$i){
				$op[$key] = isset($join[$i]) ? $value + $join[$i] : $value;
			});			
		}
		else if (count($args) == 2)
		{			
			$arr1 = array_values($args[0]);			
			$arr2 = array_values($args[1]);			
			$i=0;
			array_walk($this->source, function(&$value, &$key) use(&$op, &$i, &$arr1, &$arr2){
				$op[$key] = (isset($arr1[$i]) && isset($arr2[$i])) ? ($value + $arr1[$i] + $arr2[$i]) : $value;
				$i++;				
			});			
		}		
		$this->source = $op;
		return $this;
	}	

	/*
	* Join two arrays of similar to SQL. Left and Inner Join Currently Supported
	* @param1: first array, @param2: 2nd array
	*/
	public function join()
	{
		$args = func_get_args();
		$op = [];
		$join_array = $args[0];
		$join_by = $args[1];
		$join_type = isset($args[2]) ? strtolower($args[2]) : 'left';//Default is left
		$join_by = (strpos($join_by, '=') !== FALSE) ? array_map('trim', explode("=", $join_by)) :  array_fill(0, 1, $args[1]); //Assign Joiners
		$join_keys = array_fill_keys(array_keys($join_array[0]), NULl);//For left join
		if(strtolower($join_by[0]) == strtolower($join_by[1]))
		{
			unset($join_keys[$join_by[1]]);			
		}
		$joiner_1 = array_flip(array_column($this->source, $join_by[0])); //Prepare 1		
		$joiner_2 = array_flip(array_column($join_array, $join_by[1]));	//Prepare 2
		array_walk($this->source, function(&$value, &$key) use(&$join_array, &$op, &$join_by, &$joiner_1,&$joiner_2, &$join_keys, &$join_type){			
				if(isset($value[$join_by[0]]) ){ //Are you there?
					$find = $value[$join_by[0]];
					if(isset($joiner_2[$find])) { //Do you know me?
						$op[$key] = $value + $join_array[$joiner_2[$find]]; //Yes
					}
					else if($join_type =='left') //Be with me even not you
					{
						$op[$key] = $value + $join_keys;
					}
				}
		});
		$this->source = $op;
		return $this;
	}

	/*
	* Match and return the array. supports regex
	*/
	public function pluck()
	{	
		$args = func_get_args();
		$search = $args[0];
		$this->intersected = [];
		if($search !='')
		{			
			array_walk_recursive($this->source, function(&$value, &$key) use(&$search){				
				if( preg_match('/'.$search.'/', $key) )
				{
					$this->intersected[][$key] = $value;
				}
			});	
			$this->source = $this->intersected;
		}
		return $this;
	}
	/*
	* To increase performance select+where are combined to work along.
	*/
	public function select_where()
	{
		$args = func_get_args();
		$select = array_map('trim', explode(",", $args[0]));
		$op = $o = [];	
		$cond = $args[1];//Format conditions		
		$this->format_select($cond);
		array_walk($cond, function($v, $k) use(&$o) {
			$key = array_map('trim', explode(" ", $k));
			$key[1] = (isset($key[1]) && $key[1] != "") ? $key[1] : '==';
			$key[2] = $v;
			$o[] = $key;
		});
		 //Filter and select it
		$select = array_flip($select);
		array_filter($this->source, function($src, $key) use ($o, $select, &$op){
			$resp = FALSE;
			foreach ($o as $k => $v) {
				$resp = (isset($src[$v[0]])) ? ($this->_operator_check($src[$v[0]], $v[1], $v[2])) : $resp;
				if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
			}
			($resp==TRUE) ? ( (count($select) == '1' ? ( isset(array_intersect_key($src, $select)[key($select)]) ?  $op[$key]  = array_intersect_key($src, $select)[key($select)]: FALSE ) : $op[$key] = array_intersect_key($src, $select) ) ): FALSE;
			return $resp;
		},ARRAY_FILTER_USE_BOTH);
		$preserve = isset($args[2]) ? $args[2] : TRUE;
		$this->_preserve_keys($op, $preserve);
		return $this;
	}

	/*
	* search and return true. 
	*/
	public function contains()
	{
		$args = func_get_args();
		$isValid = false;
		if ( func_num_args() == 2 ) 
		{			    
			$search_key = $args[0];
			$search_value = $args[1];
		}
		else
		{
			$search_key = '';
		    $search_value = $args[1];			
		}
		//If search value founds, to stop the iteration using try catch method for faster approach
		try {
			  array_walk_recursive($this->source, function(&$value, &$key) use(&$search_key, &$search_value){
		    	if($search_value != ''){
		    		if($search_value == $value && $key == $search_key){
		    			$isThere = true;	
		    		}
		    	}
		    	else
		    	{
		    		if($search_value == $value){
		    			$isThere = true;	
		    		}
		    	}
		    	// If Value Exists
		        if ($isThere) {
		            throw new Exception;
		        } 
		    });
		   }
		   catch(Exception $exception) {
			  $isValid = true;
		   }
		return $this->source = $isValid;
	}	


	/*
	* Converting Multidimensional Array into single array with/without null or empty 
	*/

	public function collapse()
	{
		$args = func_get_args();
		$empty_remove = !empty ($args[0]) ? $args[0] : false ;
		$op = [];			
		array_walk_recursive($this->source, function(&$value, &$key) use(&$op, &$empty_remove){
			if( $empty_remove ){
				if( $value != '' || $value != NULL )
				{
					$op[][$key] = $value;					
				}
			}
			else
			{
				$op[][$key] = $value;
			}								
		});
		$this->source = $op;		
		return $this;		
	}	

	/*
	* search the key exists and return true if found.
	*/
	public function has()
	{
		$args = func_get_args();
		$array = $args[0];
		$search_key = $args[1];
		$isValid = false;
		//If search value founds, to stop the iteration using try catch method for faster approach
		try {
			  array_walk_recursive($array, function(&$value, &$key) use(&$search_key){
	    		if($search_key == $key){
	    			$isThere = true;	
	    		}		    	
		    	// If Value Exists
		        if ($isThere) {
		            throw new Exception;
		        } 
		    });
		   }
		   catch(Exception $exception) {
			  $isValid = true;
		   }
	    return $isValid;	 	
	}

}
/* End of the file Arrayz.php */
