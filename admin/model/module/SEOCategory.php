<?php
class ModelModuleSEOCategory extends Model {
	
	public function createTable(){
		
		$sql = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "SEOCategory` (
				  `id` INT NOT NULL AUTO_INCREMENT,
				  `idSub` INT NOT NULL,
				  `idMySub` INT NOT NULL,
				  `idMan` INT NOT NULL,
				  `Products` TEXT NOT NULL,
				  PRIMARY KEY (`id`)) ENGINE = MyISAM;";
		
		$query = $this->db->query($sql);
		return $query;
	}

	public function getCategories(){
		$sql = "SELECT `id`, `idSub`, `idMySub` , `idMan`, `Products` FROM `" . DB_PREFIX . "SEOCategory`";
		return $this->db->query($sql)->rows;
	}

	public function updateProducts($idSub, $idMan, $data){
		$sql = "UPDATE `" . DB_PREFIX . "SEOCategory` SET `Products`='".$data."' WHERE `idSub`=".$idSub." AND`idMan`=".$idMan;
		$this->db->query($sql);
		return true;
	}

	public function getManufacturer($idSub){
		$sql = "SELECT idMan FROM `" . DB_PREFIX . "SEOCategory` WHERE idSub =".$idSub;
		return $this->db->query($sql)->rows;
	}

	public function createManufacturer($idSub,$category_id, $idMan, $data){
		$sql = "INSERT INTO `" . DB_PREFIX . "SEOCategory`(`idSub`, `idMySub`,`idMan`, `Products`) VALUES (".$idSub.",".$category_id.",".$idMan.",'".$data."')";
		$this->db->query($sql);
		return true;
	}

	public function createUrlAlias($data) {
		$sql="INSERT INTO `" . DB_PREFIX . "url_alias`(`query`, `keyword`, `smp_language_id`) VALUES ('category_id=".$data['category_id']."','".$data['keyword']."','".$data['language']."')";
		$query = $this->db->query($sql);
		return $query->row;
	}

	public function addProduct($product_id,$category_id){
		$sql="INSERT INTO `" . DB_PREFIX . "product_to_category`(`product_id`, `category_id`) VALUES ('".$product_id."','".$category_id."')";
		$query = $this->db->query($sql);
		return $query->row;
	}
	public function removeProduct($product_id,$category_id){
		$sql= "DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = '".$product_id."' AND `category_id` = '".$category_id."'";
		$query = $this->db->query($sql);
		return $query->row;
	}
}	
?>

