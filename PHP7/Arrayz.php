<?php
/**
* Array as Table
* Contributor - Giri Annamalai M
* Version - 2.0
* PHP version - 7.0
*/
class Arrayz
{
	public function __construct($array=[])
	{
		$this->conditions = $this->select_fields = $this->prior_functions = $this->worker = $this->source = $this->functions = [];
		$this->condition_cnt = 0;
		if(is_array($array) && count($array) > 0 )		
			$this->source = $array;
	}

	public function __invoke($source=[])
	{
		$this->source = $source;
		return $this;
	}

	/*
	* Select the keys and return only them	
	* @param1: 'key1,key2', must be comma seperated.
	*/
	public function select()
	{		
		$args = func_get_args();
		$preserve = $args[1] ?? FALSE;
		$this->select_fields = $this->format_select($args[0], $preserve);		
		$this->functions['select'] = 'resolve_select';
		$this->worker['select'] = ['preserve' => $preserve];		
		return $this;
	}	

	public function resolve_select()
	{
		if(count($this->field_cnt) ==1 && $this->worker['select']['preserve'])
		{			
			$this->source = array_column($this->source, $this->select_fields);		
		}
		else
		{					
			array_walk($this->source, function(&$value, &$key) {
				$value = array_intersect_key($value, $this->select_fields);				
			});
		}		
	}

	public function select_column()
	{		
		$args = func_get_args();			
		$this->functions['select_column'] = 'resolve_select_column';
		$this->worker['select_column'] = ['select' => $args[0], 'key' => $args[1] ?? ''];
		return $this;
	}	

	public function resolve_select_column()
	{
		extract($this->worker['select_column']);
		$this->source = ($key != '') ? array_column($this->source, $select, $key) : array_column($this->source, $select);		
	}

	function eq($a, $b){ return $a == $b;}
	function neq($a, $b){ return $a != $b;}
	function lt($a, $b){ return $a < $b;}
	function gt($a, $b){ return $a > $b;}
	function lteq($a, $b){ return $a <= $b;}
	function gteq($a, $b){ return $a >= $b;}
	function eq3($a, $b){ return $a === $b;}
	function neq3($a, $b){ return $a !== $b;}

	/*
	* Where	
	* @param1: can be array, or string
	*/
	public function where()
	{		
		$args = func_get_args();		
		$preserve = '';		
		if(is_string($args[0]))
		{
			if(func_num_args() == 2)
			{				
				$o[0] = $args[0];
				$o[1] = 'eq';
				$o[2] = $args[1];
				$this->conditions[0] = $o;				
				$this->condition_cnt = 1;
				$preserve = $args[3] ?? TRUE;
			}
			else
			{
				$preserve = $args[3] ?? TRUE;
				$args[1] = $this->set_func($args[1]);
				$this->conditions[0] = $args;
				$this->condition_cnt = 1;				
			}
		}else {			
			$this->conditions = $this->format_conditions($args[0]);
			$preserve = $args[1] ?? TRUE;
			$this->condition_cnt = count($this->conditions);
		}		
		$this->prior_functions['where'] = 'resolve_where';
		$this->worker['where'] = ['preserve' => $preserve];		
		return $this;
	}

	public function whereIn()
	{
		$args = func_get_args();
		$this->worker['whereIn'] = ['search_key' => $args[0], 'search_value' => $args[1],'preserve'=>$args[2] ?? TRUE];
		$this->prior_functions['whereIn'] = 'resolve_whereIn';
		return $this;
	}

	public function whereNotIn()
	{
		$args = func_get_args();
		$this->worker['whereNotIn'] = ['search_key' => $args[0], 'search_value' => $args[1], 'preserve'=>$args[2] ?? TRUE];		
		$this->prior_functions['whereNotIn'] = 'resolve_whereNotIn';
		return $this;
	}

	/*
	* Flat Where
	*/
	public function flat_where()
	{
		$args = func_get_args();		
		$cond = array_map('trim', explode(" ", $args[0]));
		$cond[0] = $this->set_func($cond[0]);
		$this->worker['flat_where'] = ['cond' => $cond, 'preserve'=>$args[1] ?? TRUE];		
		if(empty($this->worker['select'])){
			$this->prior_functions['flat_where'] = 'resolve_flat_where';
		}
		else{
			$this->functions['flat_where'] = 'resolve_flat_where';
		}
		return $this;		
	}

	public function resolve_flat_where()
	{
		$op = [];
		extract($this->worker['flat_where']);		
		foreach ($this->source as $key => $value) {			
			if($this->{$cond[0]}($value, $cond[1]))			
				$op[$key] = $value;
		}
		$this->source = $preserve ? $op : array_values($op);		
	}

	public function resolve_flat_where_row()
	{
		if(!empty($this->functions['order_by']))
		{
			$this->resolve_order_by();
			unset($this->function['order_by']);
		}
		$op = [];
		extract($this->worker['flat_where']);		
		foreach ($this->source as $key => $value) {			
			if($this->{$cond[0]}($value, $cond[1]))
			{
				$op[$key] = $value;
				break;
			}
		}
		$this->source = $preserve ? $op : array_values($op);		
	}

	/*
	* Orderby by Key
	*/
	public function order_by()
	{
		$args = func_get_args();		
		$sort_mode = ['asc', 'desc'];		
		if( func_num_args() > 1 && !is_bool($args[1])) //Associatove Array
		{			
			$sort_order = ['asc' => SORT_ASC, 'desc' => SORT_DESC];			
			$args[1] = $args[1] ?? 'asc';
			$this->worker['order_by'] = ['is_flat' => FALSE,'sort_order' => $sort_order[strtolower($args[1])], 'sort_by_key' => $args[0]];
		}
		else
		{			
			$args_sort = strtolower($args[0]);			
			$sort_order = ['asc' => 'asort', 'desc' => 'arsort'];		
			$preserve = $args[1] ?? TRUE;
			$this->worker['order_by'] = ['is_flat' => TRUE,'sort_method' => $sort_order[$args_sort],  'preserve' => $preserve];
		}		
		$this->functions['order_by'] = 'resolve_order_by';
		return $this;
	}

	public function resolve_order_by()
	{
		extract($this->worker['order_by']);		
		if(!$is_flat) //Associatove Array
		{			
			$sort_by = array_column($this->source, $sort_by_key);
			array_multisort($sort_by, $sort_order, $this->source);			
		}	
		else{
			$sort_method($this->source);
		}
	}

	public function keys()
	{
		$this->functions['keys'] = 'resolve_keys';
		return $this;
	}

	public function resolve_keys()
	{
		$this->source = array_keys($this->source);
	}

	public function values()
	{
		$this->functions['values'] = 'resolve_values';
		return $this;
	}

	public function resolve_values()
	{
		$this->source = array_values($this->source);
	}

	public function get()
	{
		$this->resolver();
 		unset($this->worker);
		if(is_array($this->source) && count($this->source)==0)
 	 	{
 	 		return NULL;
 	 	}		
 	 	if(!is_array($this->source) && $this->source=='')
 	 	{
	 	 	return NULL;
 		}		
		return $this->source;		
	}

	public function get_row()
	{			
		$fn_cnt = count($this->functions);
		if($fn_cnt == 0)
		{				
			if(is_array($this->source) && count($this->source)==0)
			{
				return NULL;
			}			
			$this->source = current($this->source);	
			return $this->source;		
		}		
		foreach ($this->prior_functions as $key => $v) {			
			$this->{$v.'_row'}();
		}
		$this->prior_functions = [];
		foreach ($this->functions as $key => $val) {
			$this->{$val}();
		}
		$this->functions = [];
		if(is_array($this->source) && count($this->source)==0)
		{
			return NULL;
		}
		unset($this->worker);
		$this->source = current($this->source);
		return $this->source;
	}

	/* Resolve and coordinate combining function calls */
	public function resolver()
	{

		foreach ($this->prior_functions as $key => $v) {
			$this->{$v}();
		}
		
		$this->prior_functions = [];
		foreach ($this->functions as $key => $val) {
			$this->{$val}();
		}
		$this->functions = [];
	}

	public function resolve_where()
	{
		$conditions = $this->conditions;
		$cond_cnt = $this->condition_cnt;		
		$op = [];
		if(!empty($this->worker['select']))
		{	
			$field_cnt = $this->field_cnt;
			if($field_cnt==1 && $cond_cnt == 1)
			{
				foreach ($this->source as $key => $value) {
					if( $this->{$conditions[0][1]}($value[$conditions[0][0]], $conditions[0][2]) ) {
							$op[$key] = $value[$this->select_fields];
					}
				}
			}else if($field_cnt>1 && $cond_cnt == 1)
			{				
				foreach ($this->source as $key => $value) {						
					if( $this->{$conditions[0][1]}($value[$conditions[0][0]], $conditions[0][2]) ) {	
							$op[$key] = array_intersect_key($value, $this->select_fields);
					}
				}				
			}else if($field_cnt==1 && $cond_cnt > 1)
			{				
				foreach ($this->source as $key => $value) {						
					$resp = FALSE;
					foreach ($conditions as $k => $v) {						
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}					
					if($resp) $op[$key] = $value[$this->select_fields];
				}
			}else if($field_cnt>1 && $cond_cnt > 1)
			{				
				foreach ($this->source as $key => $value) {
					$resp = FALSE;
					foreach ($conditions as $k => $v) {						
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}
					if($resp) $op[$key] = array_intersect_key($value, $this->select_fields);
				}
			}		
			$this->source = $this->worker['where']['preserve'] ? $op : array_values($op);
			unset($this->functions['select']);			
			return ;
		}
		else{			
			if($cond_cnt==1)
			{
				$fn = $conditions[0][1];
				foreach ($this->source as $key => $value) {								
					if($this->{$fn}($value[$conditions[0][0]], $conditions[0][2]))
						$op[$key] = $value;			
				}
				$this->source = $op;				
			}else
			{
				foreach ($this->source as $key => $value) {
					$resp = FALSE;				
					foreach ($conditions as $k => $v) {						
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}
					if($resp) $op[$key] = $value;
				}				
			}
			$this->source = $this->worker['where']['preserve'] ? $op : array_values($this->source);
			return ;					
		}
	}
	
	public function resolve_where_row()
	{				
		$conditions = $this->conditions;		
		$cond_cnt = $this->condition_cnt;
		$op = [];
		if(!empty($this->functions['select']))
		{			
			$field_cnt = $this->field_cnt;
			if($field_cnt==1 && $cond_cnt == 1)
			{				
				foreach ($this->source as $key => $value) {
					if( $this->{$conditions[0][1]}($value[$conditions[0][0]], $conditions[0][2]) ) {	
							$op[$key] = $value[$this->select_fields];
							break;
					}				
				}				
			}else if($field_cnt>1 && $cond_cnt == 1)
			{
				foreach ($this->source as $key => $value) {						
					if( $this->{$conditions[0][1]}($value[$conditions[0][0]], $conditions[0][2]) ) {
							$op[$key] = array_intersect_key($value, $this->select_fields);
							break;
					}
				}
			}else if($field_cnt==1 && $cond_cnt > 1)
			{
				foreach ($this->source as $key => $value) {						
					$resp = FALSE;
					foreach ($conditions as $k => $v) {					
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}
					if($resp){
						$op[$key] = $value[$this->select_fields];
						break;
					} 
				}
			}else if($field_cnt>1 && $cond_cnt > 1)
			{
				foreach ($this->source as $key => $value) {
					$resp = FALSE;
					foreach ($conditions as $k => $v) {						
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}
					if($resp) {
						$op[$key] = array_intersect_key($value, $this->select_fields);
						break;
					}
				}
			}
			$this->source = $this->worker['where']['preserve'] ? $op : array_values($this->source);
			unset($this->functions['select']);			
			return ;
		}
		else{
			if($cond_cnt==1)
			{	
				$fn = $conditions[0][1];
				foreach ($this->source as $key => $value) {					
					if( $this->{$fn}($value[$conditions[0][0]], $conditions[0][2]) )
					{					
						$op[$key] = $value;
						break;
					}
				}			
			}else{
				foreach ($this->source as $key => $value) {
					foreach ($conditions as $k => $v) {
						$resp = $this->{$v[1]}($value[$v[0]], $v[2]);
						if($resp==FALSE)  break; //If one condition fails break it. It's not the one, we are searching
					}
					if($resp)
					{
						$op[$key] = $value;
						break;
					}
				}				
			}		
			$this->source = $this->worker['where']['preserve'] ? $op : array_values($this->source);
			return ;
		}
	}	
	private function format_conditions($cond=''): array
	{		
		$o = [];
		$i = 0;
		array_walk($cond, function($v, $k) use (&$o, &$i) {
			$key = array_map('trim', explode(" ", $k));			
			$key[1] = $key[1] ?? '='; //Default is =			
			$key[1] = $this->set_func($key[1]);
			$key[2] = $v;			
			$o[$i] = $key;
			$i++;
		});
		return $o;
	}
	public function set_func($operator)
	{
		switch ($operator) {
			case '=':
			case '==':  $eq = 'eq';break;
			case '!=':
			case '<>':  $eq = 'neq';break;
			case '<':   $eq = 'lt';break;
			case '>':   $eq = 'gt';break;
			case '<=':  $eq = 'lteq';break;
			case '>=':  $eq = 'gteq';break;
			case '===': $eq = 'eq3';break;
			case '!==': $eq = 'neq3';break;
			default: $eq = 'eq'; break;
		}
		return $eq;
	}

	public function format_select(string $select='', bool $flat=FALSE)
	{		
		$select = array_map('trim', explode(",", $select));		
		$this->field_cnt = $flat ? 1 : 2;
		return (count($select) == 1 && $flat) ? $select[0] : array_flip($select);		
	}

	/* Get which is prior */
	public function priorizer()
	{
		$sort_by = array_column($this->worker, 'priority');
		array_multisort($sort_by, SORT_ASC, $this->worker);
	}

	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function resolve_whereIn()
	{
		extract($this->worker['whereIn']);
		$op = [];		
		if(!empty($this->worker['select']))
		{			
			if($this->field_cnt == 1)
			{
				array_walk($this->source, function(&$src, $k) use ($search_key, $search_value, &$op) {
					if((isset($src[$search_key])) && in_array( $src[$search_key], $search_value))
					{
						$op[$k] = $src[$this->select_fields];
					}
				},ARRAY_FILTER_USE_BOTH);				
			}
			else if($this->field_cnt > 1)
			{				
				array_walk($this->source, function(&$src, $k) use ($search_key, $search_value, &$op) {
					if((isset($src[$search_key])) && in_array( $src[$search_key], $search_value))
					{
						$op[$k] = array_intersect_key($src, $this->select_fields);
					}
				},ARRAY_FILTER_USE_BOTH);				
			}			
			unset($this->functions['select']);
		}
		else
		{			
			$op = array_filter($this->source, function($src) use ($search_key, $search_value) {
				return (isset($src[$search_key])) && in_array( $src[$search_key], $search_value);
			},ARRAY_FILTER_USE_BOTH);			
		}		
		$this->source = $preserve ? $op : array_values($op);		
	}

	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function resolve_whereIn_row()
	{
		extract($this->worker['whereIn']);
		$op = [];
		if(!empty($this->worker['select']))
		{			
			if($this->field_cnt == 1)
			{
				foreach ($this->source as $k => $src) {
					if((isset($src[$search_key])) && in_array( $src[$search_key], $search_value))
					{
						$op[$k] = $src[$this->select_fields];
						break;
					}					
				}
			}
			else if($this->field_cnt > 1)
			{
				foreach ($this->source as $k => $src) {
					if((isset($src[$search_key])) && in_array( $src[$search_key], $search_value))
					{
						$op[$k] = array_intersect_key($src, $this->select_fields);
						break;
					}					
				}
			}			
			unset($this->functions['select']);
		}
		else
		{			
			foreach ($this->source as $k => $src) {
				if((isset($src[$search_key])) && in_array( $src[$search_key], $search_value))
				{
					$op[$k] = $value;
					break;
				}
			}		
		}	
		$this->source = $preserve ? $op : array_values($this->source);		
	}

	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function resolve_whereNotIn()
	{
		extract($this->worker['whereNotIn']);
		$op = [];
		if(!empty($this->worker['select']))
		{			
			if($this->field_cnt == 1)
			{
				array_walk($this->source, function(&$src, $k) use ($search_key, $search_value, &$op) {
					if((isset($src[$search_key])) && !in_array( $src[$search_key], $search_value))
					{
						$op[$k] = $src[$this->select_fields];
					}
				},ARRAY_FILTER_USE_BOTH);			
			}
			else if($this->field_cnt > 1)
			{
				array_walk($this->source, function(&$src, $k) use ($search_key, $search_value, &$op) {
					if((isset($src[$search_key])) && !in_array( $src[$search_key], $search_value))
					{
						$op[$k] = array_intersect_key($src, $this->select_fields);
					}
				},ARRAY_FILTER_USE_BOTH);				
			}			
			$this->source = $preserve ? $op : array_values($this->source);
			unset($this->functions['select']);
		}
		else
		{			
			$op = array_filter($this->source, function($src) use ($search_key, $search_value) {
				return (isset($src[$search_key])) && !in_array( $src[$search_key], $search_value);
			},ARRAY_FILTER_USE_BOTH);			
			$this->source = $preserve ? $op : array_values($this->source);
		}		
	}

	/*
	* Like SQL WhereIN . Supports operators.
	*/
	public function resolve_whereNotIn_row()
	{
		extract($this->worker['whereNotIn']);
		$op = [];
		if(!empty($this->worker['select']))
		{			
			if($this->field_cnt == 1)
			{
				foreach ($this->source as $k => $src) {
					if((isset($src[$search_key])) && !in_array( $src[$search_key], $search_value))
					{
						$op[$k] = $src[$this->select_fields];
						break;
					}					
				}
			}
			else if($this->field_cnt > 1)
			{
				foreach ($this->source as $k => $src) {
					if((isset($src[$search_key])) && !in_array( $src[$search_key], $search_value))
					{
						$op[$k] = array_intersect_key($src, $this->select_fields);
						break;
					}					
				}
			}			
			unset($this->functions['select']);
		}
		else
		{			
			foreach ($this->source as $k => $src) {
				if((isset($src[$search_key])) && !in_array( $src[$search_key], $search_value))
				{
					$op[$k] = $value;
					break;
				}
			}		
		}	
		$this->source = $preserve ? $op : array_values($this->source);		
	}	

	public function group_by()
	{
		$args = func_get_args();		
		$this->worker['group_by'] = ['grp_by' => $args[0]];		
		$this->functions['group_by'] = 'resolve_group_by';
		return $this;		
	}	

	public function resolve_group_by()
	{
		$op = [];
		extract($this->worker['group_by']);		
		foreach ($this->source as $data) {
		  $grp_val = $data[$grp_by];
		  if (isset($op[$grp_val])) {
		     $op[$grp_val][] = $data;
		  } else {
		     $op[$grp_val] = array($data);
		  }
		}
		$this->source = $op;		
	}

	/*
	* Converting Two Dimensional Array with lImit offset 
	*/
	public function limit()
	{
		$args = func_get_args();
		$this->worker['limit'] = ['limit' => ($args[0]!=1 ? $args[0]+1 : $args[0]) , 'offset' => ($args[1] ?? 0 ), 'preserve' => ($args[2] ?? TRUE)];		
		$this->functions['limit'] = 'resolve_limit';
		return $this;
	}

	/*
	* Converting Two Dimensional Array with lImit offset 
	*/
	public function resolve_limit()
	{
		extract($this->worker['limit']);		
		$this->source = array_slice($this->source, $offset, $limit, $preserve);		
		$this->source = $limit == 1 ? array_values($this->source)[0] : $this->source;
	}

	public function count()
	{
		$this->get();
		return count($this->source);
	}

	/* Select the maximum value using the key */
	public function select_max()
	{
		$args = func_get_args();		
		$this->worker['select_max'] = ['key' => $args[0], 'preserve' => $args[1] ?? FALSE ];		
		$this->functions['select_max'] = 'resolve_select_max';
		return $this;
	}

	public function resolve_select_max()
	{
		extract($this->worker['select_max']);		
		$find_max_in = array_column($this->source, $key);
		$k = ($preserve) ? array_keys($find_max_in, max($find_max_in))[0] : '';		
		$this->source = ($preserve) ? [$k => array_values($this->source)[$k]] : max($find_max_in);
	}

	/* Select the minimum value using the key */
	public function select_min()
	{
		$args = func_get_args();		
		$this->worker['select_min'] = ['key' => $args[0], 'preserve' => $args[1] ?? FALSE ];		
		$this->functions['select_min'] = 'resolve_select_min';
		return $this;
	}

	public function resolve_select_min()
	{
		extract($this->worker['select_min']);		
		$find_min_in = array_column($this->source, $key);
		$k = ($preserve) ? array_keys($find_min_in, min($find_min_in))[0] : '';		
		$this->source = ($preserve) ? [$k => array_values($this->source)[$k]] : min($find_min_in);
	}

	/* Calculate avg value by key. @param2 is round off numeric */
	public function select_avg()
	{
		$args = func_get_args();		
		$this->worker['select_avg'] = ['key' => $args[0], 'rounf_off' => $args[1] ?? FALSE];
		$this->functions['select_avg'] = 'resolve_select_avg';
		return $this;
	}

	/* Calculate avg value by key. @param2 is round off numeric */
	public function resolve_select_avg()
	{
		extract($this->worker['select_avg']);
		$this->source = array_column($this->source, $key);		
		$this->source = (isset($round_off) && is_numeric($round_off)) ? round((array_sum($this->source)/count($this->source)), $round_off) : (array_sum($this->source)/count($this->source));		
	}
	public function pluck()
	{	
		$args = func_get_args();
		$search = $args[0];
		$this->worker['pluck'] = ['search' => $args[0]];
		$this->functions['pluck'] = 'resolve_pluck';	
		return $this;	
	}

	public function resolve_pluck()
	{
		extract($this->worker['pluck']);
		$op = [];		
		array_walk_recursive($this->source, function($value, $key) use($search, &$op){
			if( preg_match('/'.$search.'/', $key) )
			{
				$op[][$key] = $value;
			}
		});	
		$this->source = $op;
	}

	/* Select a key and sum its values. @param1: single key of array to sum */
	public function sum()
	{
		$args = func_get_args();		
		$this->source = array_column($this->source, $args[0]);
		$this->source = array_sum($this->source);
		return $this->source;
	}

	/*
	* Similar to Like query in SQL
	*/
	public function like()
	{	
		$args = func_get_args();
		$search_key = $args[0];
		$this->worker['like'] = ['search_key' => $args[0], 'search_value' => $args[1]];
		$this->functions['like'] = 'resolve_like';		
		return $this;
	}

	public function resolve_like(){
		extract($this->worker['like']);		
		$op = array_filter($this->source, function($src) use ($search_key, $search_value){
				return isset($src[$search_key]) && preg_match('/'.$search_value.'/', $src[$search_key]);
		},ARRAY_FILTER_USE_BOTH);
		$this->source = $op;		
	}

	/* Select a key and sum its values. @param1: single key of array to sum */
	public function select_sum()
	{
		$args = func_get_args();		
		$this->worker['select_sum'] = ['key' => $args[0]];
		$this->functions['select_sum'] = 'resolve_select_sum';		
		return $this;
	}

	public function resolve_select_sum()
	{
		extract($this->worker['select_sum']);
		$this->source = array_column($this->source, $key);
		$this->source = array_sum($this->source);		
	}

	/* Select Distinct values*/
	public function distinct()
	{
		$args = func_get_args();		
		$this->worker['distinct'] = ['key' => $args[0]];
		$this->functions['distinct'] = 'resolve_distinct';					
		return $this;
	}
	public function resolve_distinct()
	{
		extract($this->worker['distinct']);
		$source = $this->source;
		$this->source = array_column($this->source, $key);		
		$this->source = $s = array_values(array_flip($this->source));
		array_walk($this->source, function(&$value, &$key) use(&$op, &$source){
			$op[] = $source[$value];
		});
		$this->source = $op;			
	}

	/*
	* reverse the array
	*/
	public function reverse()
	{	
		$args = func_get_args();		
		$this->worker['reverse'] = ['preserve' => $args[0] ?? TRUE];
		$this->functions['reverse'] = 'resolve_reverse';		
		return $this;
	}

	/*
	* reverse the array
	*/
	public function resolve_reverse()
	{	
		extract($this->worker['reverse']);
		$this->source = array_reverse($this->source, $preserve);		
	}

	/*
	* Combine two arrays of each columns create 
	* @param1: first array, @param2: 2nd array
	*/
	public function join_each()
	{
		$args = func_get_args();
		$this->worker['join_each'] = ['join1' => $args[0], 'join2' => $args[1] ?? FALSE ];		
		$this->functions['join_each'] = 'resolve_join_each';
		return $this;
	}

	/*
	* Combine two arrays of each columns create 
	* @param1: first array, @param2: 2nd array
	*/
	public function resolve_join_each()
	{
		extract($this->worker['join_each']);				
		$i=0;
		if($join2==FALSE)
		{
			$join = array_values($join1);
			array_walk($this->source, function(&$value, &$key) use(&$join, &$op, &$i){
				$op[$key] = isset($join[$i]) ? $value + $join[$i] : $value;
				$i++;				
			});			
		}
		else
		{			
			$arr1 = array_values($join1);			
			$arr2 = array_values($join2);
			array_walk($this->source, function(&$value, &$key) use(&$op, &$i, &$arr1, &$arr2){
				$op[$key] = (isset($arr1[$i]) && isset($arr2[$i])) ? ($value + $arr1[$i] + $arr2[$i]) : $value;
				$i++;				
			});			
		}
		$this->source = $op;		
	}	

	/*
	* Join two arrays of similar to SQL. Left and Inner Join Currently Supported
	* @param1: first array, @param2: 2nd array
	*/
	public function join()
	{
		$arg = func_get_args();
		$this->worker['join'] = ['args' => $arg];
		$this->functions['join'] = 'resolve_join';
		return $this;
	}

	/*
	* Join two arrays of similar to SQL. Left and Inner Join Currently Supported
	* @param1: first array, @param2: 2nd array
	*/
	public function resolve_join()
	{
		extract($this->worker['join']);		
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
	* Assign the key from the array value
	* @param1: key, @param2: true, will return with the key value
	*/
	public function assign_key()
	{
		$args = func_get_args();
		$this->worker['assign_key'] = ['args' => $args];
		$this->functions['assign_key'] = 'resolve_assign_key';
		return $this;
	}
	
	/*	
	* Assign the key from the array value
	* @param1: key, @param2: true, will return with the key value
	*/
	public function resolve_assign_key()
	{
		extract($this->worker['assign_key']);
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
		}	
   		$this->source = $op;
	}

	public function select_where()
	{
		$args = func_get_args();
		$select = array_map('trim', explode(",", $args[0]));				
		$this->select_fields = (count($select) == 1) ? $select[0] : array_flip($select);			
		$this->field_cnt = (count($select) == 1) ? 1 : 2;		
		$this->worker['select'] = ['preserve' => (count($select) == 1)];
		$this->functions['select'] = 'resolve_select';
		
		$this->conditions = $this->format_conditions($args[1]);		
		$preserve = $args[2] ?? TRUE;
		$this->condition_cnt = count($this->conditions);
		$this->prior_functions['where'] = 'resolve_where';
		$this->worker['where'] = ['preserve' => $preserve];	
		return $this;
	}

	public function toJson()
	{
		$this->resolver();
 		unset($this->worker);
		return (empty($this->source)) ? NULL : json_encode($this->source);
	}

	public function __call($name, $arguments)
    {
	 	return NULL;	    
	}

	/*
	* search and return true. 
	
	public function contains()
	{
		$args = func_get_args();
		$this->worker['contains'] = ['args' => $args];
		$this->functions['contains'] = 'resolve_contains';
		return $this;
	}	

	public function resolve_contains()
	{
		extract($this->worker['contains']);
		$isValid = false;
		if ( func_num_args() == 2 ) 
		{			    
			$search_key = $args[0];$search_value = $args[1];
		}
		else
		{
			$search_key = '';$search_value = $args[1];			
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

	public function collapse()
	{
		$args = func_get_args();
		$this->worker['collapse'] = ['args' => $args];
		$this->functions['collapse'] = 'resolve_collapse';
		return $this;
	}

	public function resolve_collapse()
	{
		extract($this->worker['collapse']);
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
	}

	/*
	* search the key exists and return true if found.
	
	public function has()
	{
		$args = func_get_args();
	 	$this->worker['has'] = ['args' => $args];
	 	$this->functions['has'] = 'resolve_has';
	}

	/*
	* search the key exists and return true if found.
	
	public function resolve_has()
	{
		extract($this->worker['has']);
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
*/
}
/* End of the file Arrayz.php */
