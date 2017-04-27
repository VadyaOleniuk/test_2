<?php


interface queryItems{
    public static function connectDB();
    public function getQuery($sql);    
}

class connectMySqli implements queryItems{
    private static $db = null;
    private $mysqli;
      
    public static function connectDB(){
        if (self::$db == NULL)
            self::$db = new connectMySqli();
        return self::$db;
    }
    
    private function __construct() {
        $config=parse_ini_file('config.ini');
        $this->mysqli = new mysqli($config['host'],$config['username'],$config['password'],$config['dbname']);
        $this->mysqli->query("SET NAMES 'utf8'");
    }

    public function getQuery($sql){
        $result_set = $this->mysqli->query($sql);
        if (!$result_set) return false;
        return $this->resultSetToArray($result_set);
    }
    
    private function resultSetToArray($result_set) {
        $array = array();
        while (($row = $result_set->fetch_assoc()) != false) {
          $array[] = $row;
        }
        return $array;
  }
}

class connectPDO implements queryItems{
     private static $db = null;
    private $pdo;
      
    public static function connectDB(){
        if (self::$db == NULL)
            self::$db = new connectPDO();
        return self::$db;
    }
    
    private function __construct() {
        $config=parse_ini_file('config.ini');
        $this->pdo = new PDO("mysql:host=".$config['host'].";dbname=".$config['dbname'], $config['username'], $config['password']);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("set names utf8");
    }

    public function getQuery($sql){
        $stmt = $this->pdo->query($sql);        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);        
    }    
}

class TreeElements{
     private $tree = array();
    

    public static function getTree($db){
        $class='connect'.$db;
        if(class_exists($class)){
            
            $db = $class::connectDB();
            $sql = 'SELECT id.*, i.parent_id FROM `items` i INNER JOIN items_description id on (i.item_id= id.item_id)';
            $result = $db->getQuery($sql);
            $result=self::buildTree($result);  
           return $result;
        }else{
            return array('Error database');
        }        
    }    

    private static function buildTree($result) {
	$tree = array();
	foreach ($result as $id => &$node) {
		if (!$node['parent_id']){
			$tree[$id] = &$node;
		}else{			
                    $result[$node['parent_id']]['childs'][$id] = &$node;
		}
	}
	return $tree;
    }
    
    private static function tplMenu($category){
	$menu = '<li>'.
		
		$category['item_name'];
		
		if(isset($category['childs'])){
                    usort($category['childs'], function($a, $b) {    
                        return strnatcmp($a["item_name"], $b["item_name"]);           
                    });
                    $menu .= '<ul>'. self::showCat($category['childs']) .'</ul>';
		}
	$menu .= '</li>';
	
	return $menu;
}


    public static function showCat($data){
	$string = '';
	foreach($data as $item){
		$string .= self::tplMenu($item);
	}
	return $string;
}
    

     
}



$tree=TreeElements::getTree("PDO");
$cat_menu = TreeElements::showCat($tree);
echo '<ul>'. $cat_menu .'</ul>';
echo '__________________________________________________________________________';
$tree=TreeElements::getTree("MySqli");
$cat_menu = TreeElements::showCat($tree);
echo '<ul>'. $cat_menu .'</ul>';
?>
