<?php

	namespace Admin;

	class Category extends AdminController {

		public function index($f3) {
			$categories = $this->Model->Categories->fetchAll();
			$counts = array();
			foreach($categories as $category) {
				$counts[$category->id] = $this->Model->Post_Categories->fetchCount(array('category_id' => $category->id));
			}
			$f3->set('categories',$categories);
			$f3->set('counts',$counts);
		}

		public function add($f3) {
			if($this->request->is('post')) {
				$category = $this->Model->Categories;
				$category->title = $this->request->data['title'];
				
				// prevent aganist XSS
				$category->title = htmlspecialchars($category->title);
				$category->save();

				\StatusMessage::add('Category added succesfully','success');
				return $f3->reroute('/admin/category');
			}
		}

		public function delete($f3) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetchById($categoryid);
			$category->erase();

			//Delete links		
			$links = $this->Model->Post_Categories->fetchAll(array('category_id' => $categoryid));
			foreach($links as $link) { $link->erase(); } 
	
			\StatusMessage::add('Category deleted succesfully','success');
			return $f3->reroute('/admin/category');
		}

		public function edit($f3) {
			$categoryid = $f3->get('PARAMS.3');
			
			// get the ID of the category and if that empty return back to same page!
			$category = $this->Model->Categories->fetchById($categoryid);
			if(empty($category)){
                \StatusMessage::add('Invalid post','danger');
                return $f3->reroute('/admin/category');
            }
			if($this->request->is('post')) {
				$category->title = $this->request->data['title'];
				$category->save();
				\StatusMessage::add('Category updated succesfully','success');
				return $f3->reroute('/admin/category');
			}
			$f3->set('category',$category);
		}


	}

?>
