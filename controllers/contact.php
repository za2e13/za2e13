<?php

class Contact extends Controller {

	public function index($f3) {
		if($this->request->is('post')) {
			extract($this->request->data);
			$from = "From: $from";

			// email address update 
			mail("root@localhost",$subject,$message,$from);

			StatusMessage::add('Thank you for contacting us');
			return $f3->reroute('/');
		}	
	}

}

?>
