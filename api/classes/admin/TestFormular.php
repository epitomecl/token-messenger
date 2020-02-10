<?php

namespace admin;

use \ReflectionClass as ReflectionClass;
use \ReflectionMethod as ReflectionMethod;

class TestFormular {
	public function __construct() {
	}
	
	public function execute($module) {
		$modules = $this->getModules($module);
		$methods = array("GET" => "GET", "POST" => "POST", "PUT" => "POST", "DELETE" => "POST");
		
		require_once("view/TestFormularView.php"); 
	}
	
	private function getModules($selected) {
		$files = array();
		$root = $_SERVER['DOCUMENT_ROOT'];
		$path = "/inssa/api/classes/modules/";
		
		if (is_dir($root.$path)) {
			if ($handle = opendir($root.$path)) {
				while (($file = readdir($handle)) !== false) {
					if (in_array($file, array(".", ".."))) {
						continue;
					}
					array_push($files, $file);
				}
				closedir($handle);
			}
		}
		
		sort($files);

		$modules = array();

		foreach ($files as $file) {
			$blocks = array();
			$name = pathinfo($root.$path.$file, PATHINFO_FILENAME);
			$tokens = explode("/", dirname($root.$path.$file));
			$namespace = end($tokens);
			$class = new ReflectionClass($namespace."\\".$name);
			$classComment = $class->getDocComment();
			$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
			foreach ($methods as $method) {
				$reflect = new ReflectionMethod($namespace."\\".$name, $method->name);
				if ($reflect->isConstructor() || !$this->startsWith($method->name, "do")) {
					continue;
				}
				$key = strtoupper(str_replace("do", "", $reflect->getName()));
				$docComment = $method->getDocComment();
				$params = $method->getParameters();
				$input = array();
				foreach ($params as $param) {
					$varName = $param->getName();
					$hint = $this->getHint($docComment, $varName);
					$obj = new \stdClass;
					$obj->name = sprintf("%s%s", $varName, ($this->getParamType($hint) == "array") ? "[]" : "");
					$obj->label = $varName;
					$obj->input = ($this->getParamType($hint) == "file") ? "file" : "text";
					$obj->type = $this->getParamType($hint);
					$obj->placeholder = $this->getPlaceholder($hint);
					$obj->visible = (strcmp(strtoupper($name), strtoupper($selected)) == 0);
					$obj->id = strtolower(sprintf("%s_%s_%s", $name, $key, $varName));
					array_push($input, $obj);
				}
				$blocks[$key] = $input;
			}
			$module = new \stdClass;
			$module->selected = (strcmp(strtoupper($name), strtoupper($selected)) == 0);
			$module->name = $name;
			$module->blocks = $blocks;
			$module->description = $this->getDescription($classComment);
			array_push($modules, $module);			
		}
		
		return $modules;
	}
	
	function startsWith($haystack, $needle){
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
	
	private function getParamType($hint) {
		if (isset($hint)) {
			return $hint->type;
		}
		
		return "";
	}
	
	private function getPlaceholder($hint) {
		if (isset($hint)) {
			return trim(sprintf("%s (%s)", $hint->comment, $hint->type));
		}
		
		return "";
	}
	
	// Regex
	private function getHint($docComment, $varName) {
		$matches = array();
		$count = preg_match_all('/@param[\t\s]*(?P<type>[^\t\s]*)[\t\s]*\$(?P<name>[^\t\s]*)[\t\s]*(?P<comment>[^\n]+)$/sim', $docComment, $matches);
		if( $count>0 ) {
			foreach( $matches['name'] as $n=>$name ) {
				if( $name == $varName ) {
					$obj = new \stdClass;
					$obj->type = trim($matches['type'][$n]);
					$obj->comment = trim($matches['comment'][$n]);
					return $obj;
				}
			}
		}
		return null;
	}
	
	private function getDescription($docComment) {
		$lines = array_slice(explode("\n", $docComment), 1, -1);
		$lines = array_map(function ($line) {
			return ltrim($line, ' *');
		}, $lines);
		$lines = array_filter($lines, function ($line) {
			return substr($line, 0, 1) != '@';
		});
		return trim(implode("\n", $lines));
	}
}
