<?php
class ControllerModuleSEOCategory extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('module/SEOCategory');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('SEOCategory', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_header_step1'] = $this->language->get('text_header_step1');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('module/SEOCategory', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('module/SEOCategory', 'token=' . $this->session->data['token'], 'SSL');
		$data['refreshCat'] = $this->url->link('module/SEOCategory/regenerateCategories', 'token=' . $this->session->data['token'], 'SSL');
		$data['refreshProducts'] = $this->url->link('module/SEOCategory/regenerateProducts', 'token=' . $this->session->data['token'], 'SSL');


		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['SEOCategory_status'])) {
			$data['SEOCategory_status'] = $this->request->post['SEOCategory_status'];
		} else {
			$data['SEOCategory_status'] = $this->config->get('SEOCategory_status');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');


		$this->response->setOutput($this->load->view('module/SEOCategory.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/SEOCategory')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}




	private function createAllNow(){

		//Create Table
		$this->load->model('module/SEOCategory');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');



		$this->model_module_SEOCategory->createTable();
		$categories = $this->model_catalog_category->getCategories();	
		$IDs_of_category = [];
		//Set all IDs
		foreach ($categories as $value) {
			$IDs_of_category[$value['category_id']] .= $value['category_id'];
		}
		//Remove parents
		foreach ($categories as $value) {
			if( in_array($value['parent_id'], $IDs_of_category) )
			{
				unset($IDs_of_category[$value['parent_id']]);
			}			
		}		
		foreach ($categories as $category) {

			if(in_array($category['category_id'],$IDs_of_category)){
				$this->createCategory($category['category_id']);

			}
		}
	}

	public function regenerateProducts(){
		
		$this->load->model('module/SEOCategory');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');


		$categories = $this->model_module_SEOCategory->getCategories();	

		foreach ($categories as $category) {
			$category_id = $category['idMySub'];
			
			$productNow =  json_decode($category['Products']);
			$toAdd = [];
			$toFix = [];
			//Get products for category and start checking
			$products = $this->model_catalog_product->getProductsByCategoryId($category['idSub']);
			foreach ($products as $product) {
				if($product['manufacturer_id'] == $category['idMan']){
					if(!in_array($product['product_id'], $productNow)){
						$toAdd[] .= $product['product_id'];
						$this->model_module_SEOCategory->addProduct($product['product_id'], $category_id);
					}else{
						$toFix[] .= $product['product_id'];
					}
				}
			}

			$toRemove = array_diff($productNow,$toFix);
			if(!empty($toRemove)){
				$flipped_products =array_flip($productNow);
				foreach ($productNow as $value) {
					if(in_array($value, $toRemove)){
						$this->model_module_SEOCategory->removeProduct($value, $category_id);
						unset($flipped_products[$value]);
					}
				}
				$productNow = array_flip($flipped_products);
			}
			$productNow = json_encode(array_merge($productNow, $toAdd));
			$this->model_module_SEOCategory->updateProducts($category['idSub'], $category['idMan'], $productNow);	
		}

		
	}

	public function regenerateCategories(){
		
		$this->load->model('module/SEOCategory');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');
		echo "<pre>";

		$categories = $this->model_catalog_category->getCategories();	
		$getAllFromModule = $this->model_module_SEOCategory->getCategories();	
		$IDs_of_category = [];
		$parents = [];
		$IDs_of_categoryModule = [];
		$IDs_of_categoryModuleCategoriesAdded = [];

		//Set all IDs from module
		foreach ($getAllFromModule as $value) {
			$IDs_of_categoryModule[$value['idMySub']] .= $value['idMySub'];
			$IDs_of_categoryModuleCategoriesAdded[$value['idSub']][] .= $value['idMan'];
		}
		foreach ($categories as $category) {
			if(in_array($category['category_id'], $IDs_of_categoryModule) || $category['parent_id'] == 0){
				continue;
			}
			$parents[] .= $category['parent_id'];
			$IDs_of_category[$category['category_id']] .= $category['category_id'];
		}
        
        //Only for last categories
//		foreach ($IDs_of_category as $category) {
//			if(in_array($category, $parents)){
//				unset($IDs_of_category[$category]);
//			}
//		}
        
        
		foreach ($categories as $category) {

			if(in_array($category['category_id'],$IDs_of_category)){
				
				
				$manufacturerCheck = [];
				$toBeCreated = [];

				if(array_key_exists($category['category_id'], $IDs_of_categoryModuleCategoriesAdded)){
					$category_id = $category['category_id'];
					$manufacturers = $IDs_of_categoryModuleCategoriesAdded[$category['category_id']];
					$products = $this->model_catalog_product->getProductsByCategoryId($category_id);

					$manufactorsForCategory = [];

					//Get Category Manufacturer
					foreach ($products as $product) {
						if(!in_array($product['manufacturer_id'], $manufactorsForCategory)){
							if($product['manufacturer_id'] != 0){
								$manufacturerName = $this->model_catalog_manufacturer->getManufacturer($product['manufacturer_id'])['name'];
								$manufactorsForCategory[$manufacturerName] .=  $product['manufacturer_id'];
							}
						}
					}
					$difference = array_diff($manufactorsForCategory,$manufacturers);
					if($difference){
						foreach ($difference as $manufacturer) {
								$category_idNEW = $this->createOuterCategory($category_id, $manufacturer);
								$productsToAdd = [];
								foreach ($products as $product) {
									if($product['manufacturer_id'] == $manufacturer){
										$this->model_module_SEOCategory->addProduct($product['product_id'], $category_idNEW);
										$productsToAdd[] .= $product['product_id'];
									}
								}
								if(!empty($productsToAdd)){
									$productsToAdd = json_encode($productsToAdd);
									$this->model_module_SEOCategory->createManufacturer($category_id,$category_idNEW, $manufacturer, $productsToAdd);
								}
						}
					}

				}else{
					$products = $this->model_catalog_product->getProductsByCategoryId($category['category_id']);
					if(count($products) > 0){
						$toBeCreated[] .= $category['category_id'];
					}
				}


				if(!empty($toBeCreated)){
					//setFunctionCreate
					foreach ($toBeCreated as $value) {
						$this->createCategory($value);
					}
				}

			}
		}		
		
	}

	private function createManufacturer($idSub,$idMan){

		$this->load->model('module/SEOCategory');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');

		$products = $this->model_catalog_product->getProductsByCategoryId($idSub);
		$productsIds = [];
		foreach ($products as $product) {
			if($product['manufacturer_id'] == $idMan){
				$productsIds[] .= $product['product_id'];
			}			
		}

		if(!empty($productsIds)){
			$productsIds = json_encode($productsIds);
			$this->model_module_SEOCategory->createManufacturer($idSub, $idMan, $productsIds);
		}

	}

	private function createCategory($idSub){

		$this->load->model('module/SEOCategory');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');

		$products = $this->model_catalog_product->getProductsByCategoryId($idSub);
		$manufactorsForCategory = [];



		//Get Category Manufacturer
		foreach ($products as $product) {
			if(!in_array($product['manufacturer_id'], $manufactorsForCategory)){
				$manufacturerName = $this->model_catalog_manufacturer->getManufacturer($product['manufacturer_id'])['name'];
				$manufactorsForCategory[$manufacturerName] .=  $product['manufacturer_id'];
			}
		}

		foreach ($manufactorsForCategory as $value) {
			$category_id = $this->createOuterCategory($idSub, $value);
			$productsToAdd = [];
			foreach ($products as $product) {
				if($product['manufacturer_id'] == $value){
					$this->model_module_SEOCategory->addProduct($product['product_id'], $category_id);
					$productsToAdd[] .= $product['product_id'];
				}
			}
			if(!empty($productsToAdd)){
				$productsToAdd = json_encode($productsToAdd);
				$this->model_module_SEOCategory->createManufacturer($idSub,$category_id, $value, $productsToAdd);
			}

		}

		

	}


	private function createOuterCategory($idSub,$idMan){


		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('module/SEOCategory');

		$categoryInfo = $this->model_catalog_category->getCategory($idSub);
		$manufacturerInfo = $this->model_catalog_manufacturer->getManufacturer($idMan);
		$nameManufacturer = $manufacturerInfo['name'];
		//Name Format 
		if (strpos($categoryInfo['name'], '>') !== false) {
		    $nameCategory = $categoryInfo['name'] . '  >  ' . $nameManufacturer;
		    $meta_title =$categoryInfo['name'] . '  >  ' . $nameManufacturer.' | Lotus Sport';
		}else{
			$nameCategory = $categoryInfo['name'] . ' ' . $nameManufacturer;
			$meta_title = $categoryInfo['name'] . ' ' . $nameManufacturer.' | Lotus Sport';
		}
		$array_toAdd = array(
			'category_description' => array(
				'1' => array(
					'name' => $nameCategory,
					'description' => $categoryInfo['description'],
					'meta_title' => $meta_title),
				'3' => array(
					'name' => $nameCategory,
					'description' => $categoryInfo['description'],
					'meta_title' => $meta_title),
			),
			'path' => $categoryInfo['path'],
			'parent_id' => $categoryInfo['category_id'],
			'category_store' => array(
				'0' => 0, 
			),
			'keywords' => array(
				'1' => $categoryInfo['keyword'].'-'.strtolower($nameManufacturer),
				'3' => $categoryInfo['keyword'].'-'.strtolower($nameManufacturer).'-en', 
			),
			'image' => '',
			'column' => '',
			'sort_order' => '0',
			'status'	=> 1,
			'category_layout' => array(
				'0' => '', 
			),

		);
		$category_id = $this->model_catalog_category->addCategory($array_toAdd);	
		
		if(!empty($category_id)){
			$bg = array('category_id'=>$category_id,'keyword'=> $array_toAdd['keywords'][1],'language' => 1);
			$en = array('category_id'=>$category_id,'keyword'=> $array_toAdd['keywords'][3],'language' => 3);

			$this->model_module_SEOCategory->createUrlAlias($bg);
			$this->model_module_SEOCategory->createUrlAlias($en);

		}
		return $category_id;
	}


}