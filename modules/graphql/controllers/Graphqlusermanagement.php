<?php

class Graphqlusermanagement extends AdminController
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('graphql_model');
        $this->load->library('app_modules');
    }
	
    // Change method name to 'index'
    public function index() {
        $data['title'] = _l('graphqlusermanagement');
        $this->load->view('graphqlusermanagement', $data);
		modules\graphql\core\Apiinit::the_da_vinci_code(GRAPHQL_MODULE);
		modules\graphql\core\Apiinit::ease_of_mind(GRAPHQL_MODULE);
    }
	
    public function regenerate_token() {
        // Call your token regeneration logic
        $this->regeneratetoken();

        // Optionally, you might want to set a flash message to inform the user
        set_alert('success', 'Token regenerated successfully!');

        // Redirect back to the user management page or wherever appropriate
        redirect(admin_url('graphql/graphqlusermanagement'));
    }
	
    private function regeneratetoken() {
        // Your logic for regenerating the token goes here
        $timestamp = time();
        update_option('graphqltoken', $timestamp);
    }
}