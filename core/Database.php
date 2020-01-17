<?php
namespace SakuraPanel;

class Database {
	
	public function __construct()
	{
		global $_config, $conn;
		
		$conn = mysqli_connect(
			$_config['db_host'],
			$_config['db_user'],
			$_config['db_pass'],
			$_config['db_name'],
			$_config['db_port']
		);
		if($conn) {
			mysqli_set_charset($conn, $_config['db_code']);
			mysqli_select_db($conn, $_config['db_name']);
		}
	}
	
	public static function query($table, $query, $mode = "AND", $raw = false)
	{
		global $conn;
		
		$mode = $mode == "" ? "AND" : $mode;
		
		if(isset($table) && $table !== "") {
			if(!$raw && is_array($query)) {
				$querySQL = "";
				$i = 0;
				$total = count($query);
				foreach($query as $key => $value) {
					$i++;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$querySQL .= "`{$key}`='{$value}'";
					if($i < $total) {
						$querySQL .= " {$mode} ";
					}
				}
				if($total > 0) {
					$querySQL = " WHERE {$querySQL}";
				}
				$table = mysqli_real_escape_string($conn, $table);
				$querySQL = "SELECT * FROM `{$table}`{$querySQL}";
				$result = mysqli_query($conn, $querySQL);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return $result;
				}
			} else {
				$result = mysqli_query($conn, $query);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return $result;
				}
			}
		} else {
			return false;
		}
	}
	
	public static function update($table, $data, $query, $mode = "AND", $raw = false)
	{
		global $conn;
		
		$mode = $mode == "" ? "AND" : $mode;
		
		if(isset($table) && $table !== "") {
			if(!$raw && is_array($query) && is_array($data)) {
				// 处理要更新的数据
				$updateSQL = "";
				$i = 0;
				$total = count($data);
				foreach($data as $key => $value) {
					$i++;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$updateSQL .= "`{$key}`='{$value}'";
					if($i < $total) {
						$updateSQL .= ", ";
					}
				}
				
				// 处理查询部分
				$querySQL = "";
				$i = 0;
				$total = count($query);
				foreach($query as $key => $value) {
					$i++;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$querySQL .= "`{$key}`='{$value}'";
					if($i < $total) {
						$querySQL .= " {$mode} ";
					}
				}
				
				if($total > 0) {
					$querySQL = " WHERE {$querySQL}";
				}
				
				$table = mysqli_real_escape_string($conn, $table);
				$querySQL = "UPDATE `{$table}` SET {$updateSQL}{$querySQL}";
				mysqli_query($conn, $querySQL);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			} else {
				mysqli_query($conn, $query);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}
	
	public static function delete($table, $query, $mode = "AND", $raw = false)
	{
		global $conn;
		
		$mode = $mode == "" ? "AND" : $mode;
		
		if(isset($table) && $table !== "") {
			if(!$raw && is_array($query)) {
				$querySQL = "";
				$i = 0;
				$total = count($query);
				foreach($query as $key => $value) {
					$i++;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$querySQL .= "`{$key}`='{$value}'";
					if($i < $total) {
						$querySQL .= " {$mode} ";
					}
				}
				if($total > 0) {
					$querySQL = " WHERE {$querySQL}";
				}
				$table = mysqli_real_escape_string($conn, $table);
				$querySQL = "DELETE FROM `{$table}`{$querySQL}";
				mysqli_query($conn, $querySQL);
				$error = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			} else {
				mysqli_query($conn, $query);
				$error = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}
	
	public static function search($table, $query, $mode = "AND", $raw = false)
	{
		global $conn;
		
		$mode = $mode == "" ? "AND" : $mode;
		
		if(isset($table) && $table !== "") {
			if(!$raw && is_array($query)) {
				$querySQL = "";
				$i = 0;
				$total = count($query);
				foreach($query as $key => $value) {
					$i++;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$querySQL .= "POSITION('{$value}' IN `{$key}`)";
					if($i < $total) {
						$querySQL .= " {$mode} ";
					}
				}
				if($total > 0) {
					$querySQL = " WHERE {$querySQL}";
				}
				$table = mysqli_real_escape_string($conn, $table);
				$querySQL = "SELECT * FROM `{$table}`{$querySQL}";
				$result = mysqli_query($conn, $querySQL);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return $result;
				}
			} else {
				$result = mysqli_query($conn, $query);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return $result;
				}
			}
		} else {
			return false;
		}
	}
	
	public static function insert($table, $query, $raw = false)
	{
		global $conn;
		
		if(isset($table) && $table !== "") {
			if(!$raw && is_array($query)) {
				$queryKey = "";
				$queryValue = "";
				$i = 0;
				$total = count($query);
				foreach($query as $key => $value) {
					$i++;
					$svalue = $value;
					$key = mysqli_real_escape_string($conn, $key);
					$value = mysqli_real_escape_string($conn, $value);
					$queryKey .= "`{$key}`";
					$queryValue .= $svalue === null ? "NULL" : "'{$value}'";
					if($i < $total) {
						$queryKey .= ", ";
						$queryValue .= ", ";
					}
				}
				$table = mysqli_real_escape_string($conn, $table);
				$querySQL = "INSERT INTO `{$table}` ({$queryKey}) VALUES ({$queryValue})";
				mysqli_query($conn, $querySQL);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			} else {
				mysqli_query($conn, $query);
				$error  = mysqli_error($conn);
				if(!empty($error)) {
					return $error;
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}
	
	public static function querySingleLine($table, $query, $raw = false)
	{
		global $conn;
		
		return mysqli_fetch_array(Database::query($table, $query, $raw));
	}
	
	public static function toArray($result)
	{
		$data = Array();
		while($rw = mysqli_fetch_row($result)) {
			$data[] = $rw;
		}
		return $data;
	}
	
	public static function fetchError()
	{
		global $conn;
		return mysqli_error($conn);
	}
	
	public static function escape($str)
	{
		global $conn;
		return mysqli_real_escape_string($conn, $str);
	}
}