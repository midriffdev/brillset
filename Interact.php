<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
|=====================================================================================
|Interact controller all function here before final note (2.0).
|=====================================================================================
*/
class Interact extends CI_controller{ 
	function __construct(){
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->database();
		$this->load->model('InteractModal');
		$this->load->model('SchedulerModal');
		$this->load->library('pagination');
		$user_id = $this->session->userdata('id');
		if(!empty($user_id)){
			$date_timezone = $this->InteractModal->get_user_meta( $user_id, 'date_timezone' );
			date_default_timezone_set("America/New_York");	
			if(!empty($date_timezone)){
				date_default_timezone_set($date_timezone);
			} 			
		}else{ 
			date_default_timezone_set("America/New_York");
		}
	} 
	/*==================index================*/
	function index(){ 
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
			redirect( base_url( 'Interact/dashboard' ));
		else: 
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*==============login form goes here====================*/
	function login(){
		$role = $this->session->userdata('role');
		if(empty( $role )):
			$this->load->view('site/simple-header');
			$this->load->view('site/login');
			$this->load->view('site/simple-footer');
		else:
			redirect( base_url() );
		endif;
	}
	/*==============process login request authenticate====================*/
	function authenticate(){ 
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$login_url = $this->input->post('login_url');
		$table = 'users';		
		$check_data = array('user_email' => $email, 'password' => md5( $password ), 'status' => 1);
		$response =  $this->InteractModal->check_user_availability( $check_data, $table );
		if(!empty( $response )):
			$id = $response[0]['id'];
			$user_email = $response[0]['user_email'];
			$role = $response[0]['role'];
			$subscription = $response[0]['subscription'];
				$mylogin_url = base_url('Interact/login');
				$status = $response[0]['status'];
				$user_name = $this->InteractModal->get_user_meta( $id, 'concerned_person' );
				$screen_name = $this->InteractModal->get_user_meta( $id, 'screen_name' );
				if(empty($screen_name)){
					$screen_name='';
				}
				$session_data = array('id' => $id, 
								  'email'  => $user_email,
								  'role' => $role,
								  'status' => $status,
								  'name' => $user_name,
								  'screen_name' => $screen_name,
								  'login_url' => $login_url,
								  'subscription' => $subscription,
								);
				$this->InteractModal->update_user_meta( $id, 'last_login' ,date('Y-m-d'));
				$this->session->set_userdata($session_data);
				redirect( base_url('Interact/dashboard') );	
		else:
			$this->session->set_flashdata('msg', 'user login credentials invalid');
			redirect( $login_url );
		endif;
	}
	/*================= register a new user	===================*/
	function register(){ 	
		$table = 'users';
		$capability =$sessionrole='';		
		$type = $this->input->post('type');
		$redirect__url = base_url();
		if( $type == 'client' ):
			$role = 'client';
			$redirect__url = base_url('Interact/add_client');
		else:
			$role = $this->input->post('role');
			$redirect__url = base_url('Interact/add_user');
		endif; 
		$attorney_id = $this->input->post('attorney_id');
		$user_name = $this->input->post('personInput');
		$current_url = $this->input->post('current_url');
		$companyName = $this->input->post('nameInput');
		$email = $this->input->post('emailInput');
		$password = $this->input->post('passInput');
		$confirm_password = $this->input->post('confirmPassInput');
		$phoneInput = $this->input->post('phoneInput');
		$screen_name = $this->input->post('screen_name');		
		if( $role == 'admin' ):			
			$customer_address = $this->input->post('customer_address');
			$street_number = $this->input->post('street_number');
			$city = $this->input->post('city');
			$state = $this->input->post('state');
			$postal_code = $this->input->post('postal_code');
		endif;
		$check_data = array('user_email' => $email);
		$check_user_availability = $this->InteractModal->check_user_availability( $check_data, $table );
		if(!empty($check_user_availability )):
			$this->session->set_flashdata('msg', 'User already exist with similar email!');
			if(!empty($current_url)){
			redirect( $current_url );
			}else{
			redirect( $redirect__url );
			}
		else:
			$data = array('user_email' => $email ,
					  'password' => md5( $password ),
					  'role' => $role,
					  'status' => 1,
					  'create_date' => date('Y-m-d h:i:s')
			);			
			$response = $this->InteractModal->register_user($data, $table);			
			if( array_key_exists('id', $response )): // update user meta in database 
				$user_id = $response['id'];	
					
				$this->InteractModal->add_user_meta( $user_id, 'concerned_person' , $user_name );
				$this->InteractModal->add_user_meta( $user_id, 'company_name' , $companyName );
				$this->InteractModal->add_user_meta( $user_id, 'phone_number' , $phoneInput );				
				if( $role == 'admin' ):
					if(!empty($attorney_id)){
					$this->InteractModal->add_user_meta( $user_id, 'atterney_id' , $attorney_id );
					}
					$this->InteractModal->add_user_meta( $user_id, 'customer_address' , $customer_address );
					$this->InteractModal->add_user_meta( $user_id, 'street_number' , $street_number );
					//$this->InteractModal->add_user_meta( $user_id, 'route' , $route );
					$this->InteractModal->add_user_meta( $user_id, 'city' , $city );
					$this->InteractModal->add_user_meta( $user_id, 'state' , $state );
					$this->InteractModal->add_user_meta( $user_id, 'postal_code' , $postal_code );
					//$this->InteractModal->add_user_meta( $user_id, 'country' , $country );					
				endif;				
				if( $role =='client' ):
					$company_id = $this->input->post('company_id');
					$this->InteractModal->add_user_meta( $user_id, 'company_id' , $company_id );
					$this->InteractModal->add_user_meta( $user_id, 'screen_name' , $screen_name );
				endif;
				$company = preg_replace('/\s+/', '+', $companyName);
				//$login_url = base_url('Interact/userlogin/'.$company.'&'.$user_id);
				$login_url = base_url();
				$this->InteractModal->add_user_meta( $user_id, 'login_url' , $login_url );	
				$login_deatil = 'Your credentials for login in interactACM account is: <br /> username: '. $email .'<br /> Password: '. $password .'<br /> Login Url: '. $login_url. ' ';
		///get auto email content data 
			if($role=="client"){
				$status_template = $this->InteractModal->get_user_meta( $company_id, 'status_template_id_1' );
				if(($status_template ==1) ){
					$subject = $this->InteractModal->get_user_meta( $company_id, 'subject_template_id_1' );
					$content = $this->InteractModal->get_user_meta( $company_id, 'content_template_id_1' );
					$logo_content = '<img src="'.base_url().'/assets/img/profiles/logo_(2).png" height="auto" width="100" alt="Logo">';  
					$content=nl2br($content);
					$content = str_replace("_NAME_",$user_name,$content);
					$content = str_replace("_DATE_",date('d-F-Y'),$content);
					$content = str_replace("_LOGO_",$logo_content,$content);
					$content = str_replace("_EMAIL_",$email,$content);
					$content = str_replace("_PHONE_",$phoneInput,$content); 
					if (strpos($content, '_LOGIN_DETAILS_') !== false) {
						 $content = str_replace("_LOGIN_DETAILS_",$login_deatil,$content); 
					}else{
						 $content = $content.'<br>'.$login_deatil;
					}
				}else{
					$template_list = $this->InteractModal->get_email_template(1);
					foreach( $template_list as $template_lists ):
						$subject = $template_lists['subject']; 
						$content = $login_deatil;
					endforeach;				
				}			
				///get auto email content data 				
					$email_data = array('email' => $email,
										'content' => $content,
										'subject' => $subject
										);
					$this->send_email($email_data );
			}else{
				$email_content = 'Welcome to interactACM. Your credentials for login in interactACM account is: <br /> username: '. $email .'<br /> Password: '. $password .'<br /> Login Url: '. $login_url. ' ';				
				$email_data = array('email' => $email,
									'content' => $email_content,
									'subject' => 'Welcome to interactACM'
									);
				$this->send_email( $email_data ); 
				/*====Email send super admin =======*/
				$content_superadmin='Hi interactACM<br/> A new broker is register with us. The details of which are following<br>Broker Name: '.$user_name.'<br> Contact Number: '.$phoneInput.'<br> Contact Email: '.$email;     
					$email_data1 = array('email' => 'info@interactacm.com', 
										'content' => $content_superadmin, 
										'subject' => 'New Broker Registration'
										); 					
					$this->send_email($email_data1);
				/*======Email send super admin========*/
				if(!empty($current_url)):
                $this->session->set_flashdata('msg', 'Thank you for registering with us. Your login credentials has been sent in registered email. Please check your mailbox.');
			    redirect( $current_url ); 
				endif;
			}
				$regex = "/^(\(?\d{3}\)?)?[- .]?(\d{3})[- .]?(\d{4})$/"; 
				$mobile = preg_replace($regex, "\\1\\2\\3", $phoneInput); 
				//$this->send_text_message($mobile,$email);				
				if( $role =='client' ):
					redirect( base_url('Interact/afterAddClient/'.urlencode(base64_encode($user_id ))));  
				else:
				    redirect( base_url( 'Interact/all_users/'.$role ) );
				endif;
				
			else:
				echo $response['error'];
			endif; //==== error or id check ends
			//echo $response;
		endif;	//==== user not exist proceed ends
	}
	/*==============after add client first time redirect====================*/
	function afterAddClient( $user_id ){
		$page_data['user_id'] = $user_id;
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/after-add-client', $page_data );
		$this->load->view('dashboard/footer');
	}
	/*=============load more client in home page=====================*/
	function more_data(){	 	 
		$page = $this->input->post('page');
		if(empty($page)){ $page=0; }
		$offset = 10*$page;
		$limit = 10;
		$user__list = $this->InteractModal->all__active_users_limit('client',$limit, $offset);
		$count_record = $this->InteractModal->count_users('client');
		$page_data['user__list']= $user__list;
		$page_data['page']= $page;
		$this->load->view('common/client_load_more', $page_data);	
				
			
	}
/*=================dashboard for admin and super-admin=================*/
function dashboard(){   
		$session_data = $this->session->userdata();
		$role = $this->session->userdata('role');
		$user_id = $this->session->userdata('id');
		$subscription = $this->session->userdata('subscription');
		$user__list='';
		//if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
		if($role == 'admin'): 
		///====Pro Agent membership conditions===///
			/*if(empty($subscription)){				
				redirect( base_url('Interact/pro_agent'));
			} */
		///Pro Agent membership 
		$events = $this->InteractModal->get_All_events(  $user_id  );
		$display_testimonial='';		
		$upcoming_showings = $this->InteractModal->get_upcoming_showings( $user_id );
		$today_count_Showing = $this->InteractModal->get_todays_of_showing(  $user_id  );
		$get_upcoming_one_showing = $this->InteractModal->get_upcoming_one_showing(  $user_id  );
		//$count_Showing = $this->InteractModal->All_get_count_of_showing(  $user_id  );		
		$per_page = 10;
		$offset = 0;
		$user__list = $this->InteractModal->all__active_users_limit('client',$per_page, $offset);	
		$new_feedbacks = $this->InteractModal->get_new_feedback(  $user_id );
		$pagedata['all_events'] = $events;
		$pagedata['today_count_Showing'] = $today_count_Showing;
		$pagedata['get_upcoming_one_showing'] = $get_upcoming_one_showing;
		$pagedata['upcoming_showings'] = $upcoming_showings;
		if(!empty($user__list)){
			$pagedata['user_list'] = $user__list;
		}
		$pagedata['new_feedbacks'] = $new_feedbacks;
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/dashboard-admin', $pagedata);
		$this->load->view('dashboard/footer');		
		elseif( $role == 'super_admin' ):
			$user__list = $this->InteractModal->all__active_users('admin');
		if(!empty($user__list)){
			$pagedata['user_list'] = $user__list;}
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/super_admin_dashboard', $pagedata);
			$this->load->view('dashboard/footer');		
		elseif( $role == 'client' ): //   for client
			$latest_property = $this->InteractModal->get_latest_property( $user_id );
			if(!empty( $latest_property )){ //==== check if property exits
				$post_id = $latest_property['ID'];
				$post_type = $latest_property['post_type'];
				if($post_type =='for_sale'){
					$type = 'seller';
				}else{
					$type = 'buyer';
				}
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($type))) );
			}else{ 
			//=== property not else 
				$company_id = $this->InteractModal->get_user_meta( $user_id, 'company_id' );
				$display_testimonial = $this->InteractModal->get_user_meta( $company_id, 'display_testimonial' );
				$boker_deatails = $this->InteractModal->get_all_user_meta( $company_id );
				$broker_rating = $this->InteractModal->get_broker_rating( $company_id );
				$all_testimonals = $this->InteractModal->get_broker_testimonals($company_id, $display_testimonial);
				$pagedata['boker_deatails'] = $boker_deatails;
				$pagedata['broker_rating'] =  $broker_rating;
				$pagedata['all_testimonals'] =  $all_testimonals;
				$this->load->view('client/header');
				$this->load->view('broker-profile', $pagedata);
				$this->load->view('client/footer');	
			} //==== property check ends	
		else: // role check ends
			redirect( base_url( 'Interact/login' ));
		endif;
		
	}
	/*================add new user ==================*/
	function add_user(){
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
		$pagedata['mode'] = 'new';
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/add-user',  $pagedata );
			$this->load->view('dashboard/footer');
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*============= add new client=====================*/
	function add_client(){
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
			$pagedata['mode'] = 'new';
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/add-client', $pagedata);
			$this->load->view('dashboard/footer');
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*==================add listing================*/
	function add_listing( $ids ){ //=== crate post for users
	    $id = base64_decode(urldecode($ids));
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
		//$all_users = $this->InteractModal->get_all_users_for_listng( $id );
		//$pagedata['all_users'] = $all_users;
		$pagedata['user__id'] = $id;
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/add-listing', $pagedata);
		$this->load->view('dashboard/footer');
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*===============save listing===================*/
	function add__listing(){
		$session_data = $this->session->userdata();
		$post_author = $session_data['id']; 
		$user__id = $this->input->post('user__id'); //==== initiator id
		//$initiated_with = $this->input->post('initiated_with');
		$file_name = '';
		$post_type = $this->input->post('post_type');		
		if($post_type == 'for_sale'){
			$config['upload_path'] = './property-images/';
			$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc';
			$config['max_size']  = 20024;		
			$this->load->library('upload', $config);
			if ( ! $this->upload->do_upload('userfile'))
			{
				$error = array('error' => $this->upload->display_errors());
				$msg = $error['error'];
			}
			else{
				$data = array('upload_data' => $this->upload->data());
				$file_name = $data['upload_data']['file_name'];
			}
			$action_type = 'seller';
		}	
		if($post_type == 'for_buy'){
			$action_type = 'buyer';
		}		
		//$initiated_with_id = $initiated_with;		
		$property_name = $this->input->post('property_name');
		$proprty_type = $this->input->post('proprty_type');
		$reference_company = $this->input->post('reference_company');
		$company_id = $this->input->post('company_id');
		$description = $this->input->post('property_description');
		$data = array('post_author' => $post_author,
					  'post_date' => date('Y-m-d h:i:s'),
					  'post_content' => $description,
					  'post_title' => $property_name ,
					  'post_status' => 1 ,
					  'post_url' =>' ' ,
					  'post_type' => $post_type,
					  'featured_image' => $file_name
				);
		$response = $this->InteractModal->add_listing_in_db( $data );
		if( array_key_exists('id', $response ) ): // update post meta in database 
			$post_id = $response['id'];			
			if($post_type == 'for_sale'){
				$apartment_no = $this->input->post('apartment_no');
				$street_number = $this->input->post('street_number');
				$city = $this->input->post('city');
				$state = $this->input->post('state');
				$postal_code = $this->input->post('postal_code');		
				$this->InteractModal->update_post_meta( $post_id, 'apartment_no' , $apartment_no ); 
				if(!empty($street_number)):
					$this->InteractModal->update_post_meta( $post_id, 'street_number' , $street_number );	
				endif;				
				$this->InteractModal->update_post_meta( $post_id, 'city' , $city );
				$this->InteractModal->update_post_meta( $post_id, 'state' , $state );
				$this->InteractModal->update_post_meta( $post_id, 'postal_code' , $postal_code );
				//$this->InteractModal->update_post_meta( $post_id, 'country' , $country );	
				if(!empty($street_number)){$street_number = $street_number.', ';}
				if(!empty($apartment_no)){$apartment_no = $apartment_no.', ';}
				if(!empty($city)){$city = $city.', ';}
				if(!empty($state)){$state = $state.', ';}
				if(!empty($postal_code)){$postal_code = $postal_code.', ';}
				$property_address=$street_number.$apartment_no.$city.$state.$postal_code;
				$this->InteractModal->update_post_meta( $post_id, 'property_address' , $property_address );			
			}
			$this->InteractModal->update_post_meta( $post_id, 'proprty_type' , $proprty_type );
			$this->InteractModal->update_post_meta( $post_id, 'initiator_id', $user__id );
			$onesignal_id = $this->InteractModal->get_user_meta( $user__id, 'one_signal_id' );
			if(!empty($onesignal_id )){
				$notification_data = 'Hi, Listing is created for you on interactRE';
				$this->send_action_notification( $notification_data, $onesignal_id );
			}			
			$this->session->set_flashdata('msg', 'Listing Added Successfully'); 	
			redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user__id)). '/'.urlencode(base64_encode($action_type))));
		else:
			$msg = $response['error'];
			$this->session->set_flashdata('msg', $msg);
			redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user__id)). '/'.urlencode(base64_encode($action_type))));			
		endif;		
	}
	/*=============all user get by role=====================*/
	function all_users($rolecheck){
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
			if( $session_data['role'] != 'super_admin' && $rolecheck =='admin'  ):
				redirect( base_url() );
				//echo $rolecheck;
			else:
				$user__list = $this->InteractModal->all__users( $rolecheck );
				//print_r( $user__list );
				$pagedata['user_list'] = $user__list; 
				$pagedata['role'] = $rolecheck;
				$this->load->view('dashboard/header');
				 
				if($rolecheck == 'client'){
					$active_listings = $this->InteractModal->get_active_listings_count();
					$pagedata['active_listings'] = $active_listings;
					$this->load->view('dashboard/users-new', $pagedata);  
					 
				}else{
					$this->load->view('dashboard/users', $pagedata);
				}	
					$this->load->view('dashboard/footer');
			endif;
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*================logout==================*/
	function logout(){
		$login_url = $this->session->userdata('login_url');
		$this->session->sess_destroy();
		redirect( $login_url );
	}
	/*==============get user listing====================*/
	function  get_user_listing(){
		$user_id = $this->input->post('user_id');
		$all_listings = $this->InteractModal->get_user_listings( $user_id );
		$pagedata['all_listings'] = $all_listings;
		$pagedata['user_id'] = $user_id;
		$this->load->view( 'dashboard/all-listings', $pagedata );
	}
	/*===============edit user===================*/
	function edit_user( $ids ){ //=== edit user profile
	    $user_id= base64_decode(urldecode($ids));
		$id = $this->session->userdata('id');
		if(!empty($id)):	
		$data = array( 'id' => $user_id );
		$this->db->from('users');
		$this->db->where( $data );
		$query = $this->db->get();
		$role = '';
		$pagedata['user_id'] = $user_id;
		$pagedata['mode'] = 'edit';
		$array = '';
		if($query->num_rows() > 0){
			$array = $query->result_array(); 
			$role = $array[0]['role'];			
		}
		$pagedata['userdata'] = $array;
		$this->InteractModal->update_user_meta( $user_id, 'last_edit',date('Y-m-d'));
		if( $role =='admin' ):
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/add-user', $pagedata);
			$this->load->view('dashboard/footer');
		elseif($role == 'super_admin'):
			$this->load->view('dashboard/header');
			//$this->load->view('dashboard/users', $pagedata);
			$this->load->view('dashboard/footer');
		elseif( $role == 'client' ):
				$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
				$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
				$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
				$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
				$footer_data['user__id'] = $user_id;
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/add-client', $pagedata);
			$this->load->view('client/footer',$footer_data);			
		endif;		
		else:
			redirect( base_url('Interact/login'));
		endif;
	}
	/*==============save user details====================*/
	function save_user_details(){
		$session_id = $this->session->userdata('id');
		$datetime = $this->input->post('_for');
		//$datetime = timezone_name_from_abbr($timezone );
		$type = $this->input->post('type');
		$user_id = $this->input->post('userid');
		$user_name = $this->input->post('personInput');
		$phoneInput = $this->input->post('phoneInput');
		$userfile = $_FILES['userfile']['name'];	
		$bio = $this->input->post('bio');
		$role = '';
		$config['upload_path'] = './assets/img/profiles/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc';
		$config['max_size']  = 200024;		
		$this->load->library('upload', $config);
			if(!empty($userfile)){
				if ( ! $this->upload->do_upload('userfile')):
					$error = array('error' => $this->upload->display_errors());
					$msg = $error['error'];			
				else:			
					$data = array('upload_data' => $this->upload->data());
					$file_name = $data['upload_data']['file_name'];
				endif;
			$this->InteractModal->update_user_meta( $user_id, 'profile_picture' , $file_name );
			}	
			///image resize 
			if(!empty($file_name)){
				$this->image_auto_resize($file_name);				
			}			 
		if( $type =='admin' ):
			$company_logo = $_FILES['company_logo']['name'];
			$company_backgoround = $_FILES['company_backgoround']['name'];
			$company_name = $this->input->post('nameInput');
			$customer_address = $this->input->post('customer_address');
			$street_number = $this->input->post('street_number');
			$city = $this->input->post('city');
			$state = $this->input->post('state');
			$postal_code = $this->input->post('postal_code');
			$website = $this->input->post('website');
			$referr_button_text = $this->input->post('referr_button_text');
			$referr_button_sub = $this->input->post('referr_button_sub');
			$referr_button_content = $this->input->post('referr_button_content');
			$Zillow_link = $this->input->post('Zillow_link');
		elseif( $type =='client'):
			$screen_name = $this->input->post('screen_name');
			$this->InteractModal->update_user_meta( $user_id, 'screen_name' , $screen_name );
			if($session_id == $user_id){
			$this->session->set_userdata('screen_name', $screen_name);
			}
		endif;				
			$this->InteractModal->update_user_meta( $user_id, 'concerned_person' , $user_name );			
			$this->InteractModal->update_user_meta( $user_id, 'phone_number' , $phoneInput );
			if( $type =='admin' ):
				if(!empty($datetime)){
					$this->InteractModal->update_user_meta( $user_id, 'date_timezone' , $datetime );
				}
				$this->InteractModal->update_user_meta( $user_id, 'customer_address' , $customer_address );
				$this->InteractModal->update_user_meta( $user_id, 'street_number' , $street_number );
				$this->InteractModal->update_user_meta( $user_id, 'company_name' , $company_name );
				//$this->InteractModal->update_user_meta( $user_id, 'route' , $route );
				$this->InteractModal->update_user_meta( $user_id, 'city' , $city );
				$this->InteractModal->update_user_meta( $user_id, 'state' , $state );
				$this->InteractModal->update_user_meta( $user_id, 'postal_code' , $postal_code );
				//$this->InteractModal->update_user_meta( $user_id, 'country' , $country );
				$this->InteractModal->update_user_meta( $user_id, 'bio' , $bio );				
				$this->InteractModal->update_user_meta( $user_id, 'website' , $website );
				$this->InteractModal->update_user_meta( $user_id, 'referr_button_text' , $referr_button_text );
				$this->InteractModal->update_user_meta( $user_id, 'referr_button_sub' , $referr_button_sub );
				$this->InteractModal->update_user_meta( $user_id, 'referr_button_content' , $referr_button_content );
				$this->InteractModal->update_user_meta( $user_id, 'Zillow_link' , $Zillow_link );
				//$this->InteractModal->update_user_meta( $user_id, 'display_testimonial' , $display_testimonial );
				//$this->InteractModal->update_user_meta( $user_id, 'unit_no' , $unit_no );
				if(!empty($company_logo)){
					if ( ! $this->upload->do_upload('company_logo')):
						$error = array('error' => $this->upload->display_errors());
						$msg = $error['error'];			
					else:			
						$data = array('upload_data' => $this->upload->data());
						$file_name = $data['upload_data']['file_name'];
					endif;
						$this->InteractModal->update_user_meta( $user_id, 'company_logo' , $file_name );
					if(!empty($file_name)){
						$this->image_auto_resize($file_name);					
					}
				}
				if(!empty($company_backgoround)){
					if ( ! $this->upload->do_upload('company_backgoround')):
						$error = array('error' => $this->upload->display_errors());
						$msg = $error['error'];			
					else:			
						$data = array('upload_data' => $this->upload->data());
						$file_name = $data['upload_data']['file_name'];
					endif;
				$this->InteractModal->update_user_meta( $user_id, 'company_backgoround' , $file_name );
				}				
			endif;	
			if($session_id == $user_id){
			$this->session->set_userdata('name', $user_name);
			}
			$this->session->set_flashdata('msg', 'Updated Successfully');
			redirect( base_url('Interact/dashboard' ) );
	}
	/*================update user status==================*/
	function update_user_status(){ // change user status;
		echo $user_id = $this->input->post('user_id');
		echo $status = $this->input->post('status');
		$data = array('id' => $user_id );
		$this->db->set('status', $status);
		$this->db->where( $data );
		$this->db->update('users');
	}
	/*=============add task =====================*/
	function add_task(){
		$type =  $this->input->get('type');
		$post_id =  $this->input->get('p_id');
		$pagedata['type'] = $type;
		$pagedata['post_id'] = $post_id;
		$pagedata['user_id'] = '';
		$initiated_by = $this->InteractModal->get_post_meta($post_id, 'initiated_by');
		$initiated_id = $this->InteractModal->get_post_meta($post_id, 'initiated_id');
		$initiated_with_id = $this->InteractModal->get_post_meta($post_id, 'initiated_with_id');
		if( $initiated_by == 'seller' ){
			$seller_id =  $initiated_id;
			$buyer_id = $initiated_with_id;
			$pagedata['user_id'] = $seller_id;
			$user_id = $seller_id;
		}else{
			$buyer_id=  $initiated_id;
			$seller_id = $initiated_with_id;
			$pagedata['user_id'] = $buyer_id;
			$user_id = $buyer_id;
		}		
		$a_query = array( 'post_id' =>$post_id,
				  'user_id' => $user_id,
				  'task_type' => 'action_item'
			);			
		$m_query = array( 'post_id' =>$post_id,
				  'user_id' => $user_id,
				  'task_type' => 'milestone'
			);	
		$l_query = array( 'post_id' =>$post_id,
				  'user_id' => $user_id,
				  'task_type' => 'legal_details'
			);	
		$action_items = $this->InteractModal->get_All_task_by_post_id($a_query);
		$milestones = $this->InteractModal->get_All_task_by_post_id($m_query);
		$legal_details = $this->InteractModal->get_All_task_by_post_id($l_query);
		$pagedata['action_items'] = $action_items;
		$pagedata['milestones'] = $milestones;
		$pagedata['legal_details'] = $legal_details;
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/add-task', $pagedata);
		$this->load->view('dashboard/footer');
	}
	/*===============save_new_task===================*/
	function save_new_task(){
		$company_id = $this->session->userdata('id');
		$action_type = $this->input->post('action_type');
		$post_id = $this->input->post('post_id');
		$user_id = $this->input->post('user_id');
		$task_type = $this->input->post('task_type');
		$_priority = 0;
		$response = $apartment_no= $city=$street_number='';
		$property_address = $this->InteractModal->get_post_meta($post_id, 'property_address');
		$street_number = $this->InteractModal->get_post_meta($post_id, 'street_number');
		$city = $this->InteractModal->get_post_meta($post_id, 'city');
		$apartment_no = $this->InteractModal->get_post_meta($post_id, 'apartment_no');
		if(!empty($apartment_no))$apartment_no = $apartment_no.' @ ';
		if(!empty($street_number))$street_number = $street_number.', ';
		$property_address= $apartment_no.$street_number.$city;
		$this->InteractModal->update_user_meta($user_id, 'day_last_update',$user_id);
		if($task_type == 'showing'){
			$phone_number = $this->input->post('phone_number');
			$user_name = $this->input->post('details');
			$showing_email = $this->input->post('showing_email');
			$schedule_date = $this->input->post('schedule_date');
			$time = $this->input->post('time');
			$zone = $this->input->post('zone');
			$count = count( $user_name );
			for($i = 0; $i < $count; $i++  ){
				$__details = array('user_name' => $user_name[$i],
							   'contact_number' => $phone_number[$i],
							   'showing_email' => $showing_email[$i]
							);
				$details = serialize($__details);
				$timezone = $zone[$i];
				$_schedule_date = '';
				$_time = '';
				$final_date = $schedule_date[$i].' '. $time[$i]. ':00';
				$data = array('post_id' => $post_id,
					  'user_id' => $user_id,
					  'schedule_date' => $final_date,
					  'details' => $details,
					  'date' => date('Y-m-d h:i:s'),
					  'task_type' => $task_type,
					  'company_id' => $company_id,
					  'priority' => $_priority
				);				
				$response = $this->InteractModal->save_new_task_db( $data );						
				if( array_key_exists('id', $response ) ): // update user meta in database 
					$showing_id = $response['id'];
					$decode_id = urlencode(base64_encode( $showing_id ));
					$link = base_url('Interact/feedback/'. $decode_id );					
					$__data = array('property_id' => $post_id ,
									'link' => $link,
									'status' => 0 ,
									'showing_id' => $showing_id ,
									'broker_id' => $company_id,
									'details' => $details,
									'schedule_date' => $final_date,
									'property_address'=> $property_address
								);
					$this->db->insert( 'feedback_links',  $__data);	
				endif;
				/*==========notification send Showing Scheduler Harendra Singh(3-4-2019)======*/
				///$this->ShowingNotify($post_id);
				$onesignal_id = $this->InteractModal->get_user_meta( $user_id, 'one_signal_id' );
				if(!empty( $onesignal_id )){
					$notification_data = 'Hi, New Showing added successfully on InteractACM';
					$this->send_action_notification( $notification_data, $onesignal_id );
				}
				/*===end notification send===*/
			}			
		}else{
			$tem_data='';
			$details = $this->input->post('details');
			$temp_name = $this->input->post('temp_name');
			$schedule_date = $this->input->post('schedule_date');
			$priority = $this->input->post('priority'); 
			$count = count( $details );
			for($i = 0; $i < $count; $i++  ){
				///user want to create template 
				if(!empty($temp_name)){
					$tem_data[] = array( 'post_id' => $post_id,
					  'user_id' => $user_id,
					  'schedule_date' => $schedule_date[$i],
					  'details' => $details[$i],
					  'date' => date('Y-m-d h:i:s'),
					  'task_type' => $task_type,
					  'company_id' => $company_id,
					  'priority' => $priority[$i] );
				}
				$data = array( 'post_id' => $post_id,
					  'user_id' => $user_id,
					  'schedule_date' => $schedule_date[$i],
					  'details' => $details[$i],
					  'date' => date('Y-m-d h:i:s'),
					  'task_type' => $task_type,
					  'company_id' => $company_id,
					  'priority' => $priority[$i] );					  
					$response = $this->InteractModal->save_new_task_db( $data );									
				}
				//create template task template
				if(!empty($temp_name) && (!empty($tem_data))){
					$data = array( 'post_id' => $post_id,
					  'user_id' => $user_id,
					  'name' => $temp_name,
					  'details' => json_encode($tem_data),
					  'date' => date('Y-m-d h:i:s'),
					  'task_type' => $task_type,
					  'company_id' => $company_id
					  );
				 $this->InteractModal->save_temp_task_db( $data ); 
					  
				}
				
			}
			
		 	if( array_key_exists('id', $response ) && isset($response['id'])):
				if($task_type == 'action_item'){
				$this->session->set_flashdata('msg', 'Action Item Added');
				}
				else{
					$this->session->set_flashdata('msg',ucwords($task_type).' Added');
				}
				$onesignal_id = $this->InteractModal->get_user_meta( $user_id, 'one_signal_id' );
				if(!empty( $onesignal_id )){
					$notification_data = 'Hi, New Task is Assigned to your listing on InteractRE';
					$this->send_action_notification( $notification_data, $onesignal_id );
				}				
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
			else:
				$msg = $response['error'];
				$this->session->set_flashdata('msg', $msg );
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
			endif;
			
		}	
	/*==========notification send Showing Scheduler Harendra Singh(3-4-2019)======*/
  /*  public function ShowingNotify(){     
    	$time = '10:00:00';
		$post_id=120;
    		$weekday = $this->getWeekday('2012-10-14'); 
			$data = $this->SchedulerModal->get_confirm_daytime($post_id, $weekday, $time);
			$auto_confirm = '';

				$where=array('post_id'=>$post_id);				
				$showing_assist = $this->SchedulerModal->getdata($where,'showing_assist');
				$start_time=$end_time=$lockbox =$lockbox_type =$lockbox_code =$instructions = $notify_by =$auto_confirm = '';
				$weekday=array();
				if(!empty($showing_assist)):
					foreach($showing_assist as $showing_assistdata):
						$weekday = json_decode($showing_assistdata['weekday']);
						$start_time = $showing_assistdata['start_time'];
						$end_time = $showing_assistdata['end_time'];
						$lockbox = $showing_assistdata['lockbox'];
						$lockbox_type = $showing_assistdata['lockbox_type'];
						$lockbox_code = $showing_assistdata['lockbox_code'];
						$instructions = $showing_assistdata['instructions'];
						$notify_by = $showing_assistdata['notify_by'];
						$auto_confirm = $showing_assistdata['auto_confirm'];
					endforeach;
				endif;
				if($auto_confirm=='on'){					


				}else{
					echo 'No auto confirm';

				}
				
				echo "<pre>";
				print_r($data);
				echo "</pre>";
    }*/
    /***=========grt weekday =====***/
    function getWeekday($date) {
    return date('w', strtotime($date));
	}
	/*==============copy  for multi image upload====================*/		
		function task_file_upload(){ 
				$this->load->helper(array('form', 'url'));
				$action_type = $this->input->post('action_type'); 
				$count = count($_FILES['userfile']['size']);
				$action_type = $this->input->post('action_type');
				$config['upload_path'] = './documents/';
				$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc|csv|xls|xlsx|PDF';
				$config['max_size']  = 200024;
				$this->load->library('upload', $config);
				foreach($_FILES as $key=>$value)
				for($s=0; $s<=$count-1; $s++) {
				 	$_FILES['userfile']['name']=$value['name'][$s];
					$_FILES['userfile']['type'] = $value['type'][$s];
					$_FILES['userfile']['tmp_name'] = $value['tmp_name'][$s];
					$_FILES['userfile']['error'] = $value['error'][$s];
					$_FILES['userfile']['size'] = $value['size'][$s]; 
					$this->upload->do_upload();
					$data = $this->upload->data();
					$file_name = $data['file_name']; 
					$post_id = $this->input->post('post_id');
					$user_id = $this->input->post('user_id');
					$details = $this->input->post('details');
					$task_type = $this->input->post('task_type');
					$_deatils = array('heading' => $details[$s], 'file_name' => $file_name);
					$serialized_array = serialize($_deatils);					
					$__data = array('post_id' => $post_id,
							'user_id' => $user_id,
							'schedule_date' => '',
							'details' => $serialized_array,
							'date' => date('Y-m-d h:i:s'),
							'task_type' => $task_type, 
						);				
					$response = $this->InteractModal->save_new_task_db( $__data );
				
				}			
			if( array_key_exists('id', $response ) && isset($response['id'])):				
				$this->session->set_flashdata('msg', 'Documents Uploaded');
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
			else:
				$msg = $response['error'];
				$this->session->set_flashdata('msg', $msg );
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));				
			endif;
        }
/*================property details==================*/
		function property_details( $post_id, $user_id, $type ){
			$role  = $this->session->userdata('role');
			$id  = $this->session->userdata('id');
			if(!empty($role)){
			$post_id =  $this->uri->segment(3);
		    $u_id =  $this->uri->segment(4);
			$type =  $this->uri->segment(5);
			$p_id = base64_decode( urldecode( $post_id )); 
			$user_id = base64_decode( urldecode( $u_id )); 
		 	$_type = base64_decode( urldecode( $type ) );			
			$education_details ='';
			$action_item_temp_data ='';
			$last_showingdays=$most_requests_date=$most_showings_date=$first_showingdays=""; 	
			if($role == "admin"){
				$education_details = $this->InteractModal->get_all_education_details($id);	
				$company_id=$this->session->userdata('id');
				$new_feedbacks = $this->InteractModal->get_recent_feedback(  $id,$p_id );				
			}
			elseif($role== "client"){
				$new_feedbacks="";
				$company_id = $this->InteractModal->get_user_meta($id, 'company_id');
				$education_details = $this->InteractModal->get_all_education_details($company_id);
			}
			$property_details = $this->InteractModal->get_propert_details( $p_id );
           	$property_all_meta = $this->InteractModal->gel_all_post_meta( $p_id );
			$post_author = $property_details[0]['post_author'];
			$broker_details = $this->InteractModal->get_all_user_meta( $post_author );
			$broker_rating = $this->InteractModal->get_broker_rating( $post_author );
			$_property_rating = $this->InteractModal->get_property_rating( $p_id );			
			$user_name = $this->InteractModal->get_user_meta($user_id, 'screen_name');
			$task_query = array( 'post_id' =>$p_id,
				  'user_id' => $user_id,
				);
			$task_details = $this->InteractModal->get_All_task_by_post_id($task_query);
			$attorney_id = $this->InteractModal->get_post_meta($p_id, 'attorney_id');
			$inspection_person = $this->InteractModal->get_post_meta($p_id, 'inspection_person');
			$Lender_id = $this->InteractModal->get_post_meta($p_id, 'Lender_id');			
			$attorney_data = '';
			$attorney_email='';
				if(!empty( $attorney_id )){
					$attorney_list = ''; 
					$own_attorney_list = ''; 					 
					$attorney_data = $this->InteractModal->get_all_user_meta( $attorney_id );
					$attorney_email = $this->InteractModal->get_user_data($attorney_id);
				}
				$attorney_list = $this->InteractModal->get_user_from_network( $post_author, 'Attorney' );
				$own_attorney_list = $this->InteractModal->get_user_from_own_network( $user_id, 'Attorney' );
				$inspection_person_data = '';
				$inspection_person_email ='';
				$Lender_data = '';
				$Lender_email = '';
				if(!empty( $Lender_id )){
					$Lender_list='';
					$own_Lender_list='';
					$Lender_data = $this->InteractModal->get_all_user_meta( $Lender_id );
					$Lender_email = $this->InteractModal->get_user_data($Lender_id);
				}
				$Lender_list = $this->InteractModal->get_user_from_network( $post_author, 'Lender' );
				$own_Lender_list = $this->InteractModal->get_user_from_own_network( $user_id, 'Lender' );
				if(!empty($inspection_person )){
					$Inspector_list = ''; 					
					$own_Inspector_list = ''; 					
					$inspection_person_data = $this->InteractModal->get_all_user_meta( $inspection_person );
					$inspection_person_email = $this->InteractModal->get_user_data($inspection_person);	
				}
				$Inspector_list = $this->InteractModal->get_user_from_network( $post_author,'Inspector' );
				$own_Inspector_list = $this->InteractModal->get_user_from_own_network( $user_id,'Inspector' );
				//load_template			 	
				$action_item_temp_data= $this->InteractModal->load_template($company_id,'action_item',$user_id,$p_id);
				$milestone_temp_data= $this->InteractModal->load_template($company_id,'milestone',$user_id,$p_id);
				/*--- How many days since your last showing ---*/
				$current_date=date('Y-m-d');
				$lastshowing_data = $this->InteractModal->day_last_showing($p_id); 
				if(!empty($lastshowing_data)){
				$last_showing=$lastshowing_data[0]['schedule_date']; 
				$last_showing_date = date("Y-m-d", strtotime($last_showing));
				$diff = abs(strtotime($current_date) - strtotime($last_showing_date));
				$years = floor($diff / (365*60*60*24));
				$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
				$last_showingdays = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));  
				}
				/*--- What day had the most requests ---*/
				$most_requests_data=$this->InteractModal->day_most_requests($p_id);
				if(!empty($most_requests_data)){
				$most_requests=$most_requests_data[0]['date']; 
				$most_requests_date = date("Y-m-d", strtotime($most_requests));
                }
				/*--- What day had the most showings ---*/
				$day_most_showing=$this->InteractModal->day_most_showing($p_id);
				if(!empty($day_most_showing)){
				$most_showings_requests=$day_most_showing[0]['schedule_date'];
				$most_showings_date = date("Y-m-d", strtotime($most_showings_requests));
				}
				/*--- How many days it took to receive your first showing ---*/
				$showing_data = $this->InteractModal->day_first_showing($p_id); 
				if(!empty($showing_data)){ 
					$first_showing_date=$showing_data[0]['schedule_date'];
					$first_showing = date("Y-m-d", strtotime($first_showing_date));
				    $mls_date = $this->InteractModal->get_post_meta($p_id,'mls_date');
					if(!empty($mls_date && $mls_date < $first_showing )){
						$diff = abs(strtotime($mls_date) - strtotime($first_showing));
					    $years = floor($diff / (365*60*60*24));
						$months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
						$first_showingdays = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 
					} 
				}
				 $showing_assist='';
				/*$where=array('post_id'=>$p_id);				
				$showing_assist = $this->SchedulerModal->getdata($where,'showing_assist');	*/						
				$pagedata['last_showingdays'] = $last_showingdays;
				$pagedata['most_requests_date'] = $most_requests_date;
				$pagedata['most_showings_date'] = $most_showings_date;
				$pagedata['first_showingdays'] = $first_showingdays;
				$pagedata['task_details'] = $task_details;
				$pagedata['user_name'] = $user_name;
				$pagedata['property_details'] = $property_details;
				$pagedata['education_details'] = $education_details;
				$pagedata['broker_details'] = $broker_details;
				$pagedata['broker_rating'] = $broker_rating;
				$pagedata['property_all_meta'] = $property_all_meta;
				$pagedata['_property_rating'] = $_property_rating;
				$pagedata['attorney_list'] = $attorney_list;
				$pagedata['attorney_data'] = $attorney_data;
				$pagedata['Inspector_list'] = $Inspector_list;
				$pagedata['lender_data'] = $Lender_data;
				$pagedata['lender_list'] = $Lender_list;
				$pagedata['own_Lender_list'] = $own_Lender_list;
				$pagedata['own_attorney_list'] = $own_attorney_list;
				$pagedata['own_Inspector_list'] = $own_Inspector_list;
				$pagedata['inspection_person_data'] = $inspection_person_data;
				$pagedata['inspection_person_email'] = $inspection_person_email;
				$pagedata['type'] = $_type;
				$pagedata['post_id'] = $p_id;
				$pagedata['user_id'] = $user_id;
				$pagedata['attorney_email'] = $attorney_email;
				$pagedata['lender_email'] = $Lender_email;
				$pagedata['action_item_temp_data'] = $action_item_temp_data;
				$pagedata['milestone_temp_data'] = $milestone_temp_data;
				$pagedata['new_feedbacks'] = $new_feedbacks;
				$pagedata['showing_assist'] = $showing_assist;				
				//=========== client ===========//				
				$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
				$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
				$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
				$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
				$footer_data['user__id'] = $user_id;
				//============ client section ends ===========//				
				$chart_value='';				
				for($i=1;$i<=12;$i++){
					$count_showing = $this->InteractModal->count_showing($i,$p_id);
					$chart_value= $chart_value.$count_showing.',';
				}
				$pagedata['count_showings']= $chart_value;
				$header_data['property_details'] = $property_details;
				$pagedata['property_details'] = $property_details;
				$this->load->view('client/header', $header_data);
				$this->load->view('client/property-page', $pagedata);
				$this->load->view('client/footer', $footer_data); 
				
			}else{
				redirect(base_url() );
			}
		}
	/*=============update_task_status=====================*/
	function update_task_status(){
			$task__id = $this->input->post('task__id');
			$status = $this->input->post('status');
			$data = array('task_id' => $task__id );
			$currant_date = date('Y-m-d h:i:s');
			if($status == 'complete'){
				$update_data = array('status' => $status, 'completion_date' => $currant_date );
			}else{
				$update_data = array('status' => $status, 'completion_date' => '' );
			}
			$this->db->set( $update_data );
			$this->db->where( $data );
			$this->db->update('tasks');
		}	
	/*==============update_details_ajax====================*/
	function update_details_ajax(){
			$name = $this->input->post('name');
			$value = $this->input->post('value');
			$pk = $this->input->post('pk');
			$u_id = $this->input->get('u_id');
			$data = array('details' => $name, 'user_id' => $u_id, 'post_id' => $pk );
			$response = $this->InteractModal->check_if_field_exist( $data );
			if(!empty( $response )){
				$this->db->set('additional', $value);
				$this->db->where( 'task_id = '. $response );
				$this->db->update( 'tasks');
			}else{
				$data_A = array('details' => $name,
								'user_id' => $u_id,
								'post_id' => $pk,
								'additional' => $value,
								'task_type' => 'financial',
								'date' => date('Y-m-d h:i:s')
							 );
				$this->db->insert( 'tasks', $data_A );			
				
			}
		}
	/*============all_networks======================*/
	function all_networks(){			
			$role = $this->session->userdata('role');
			if($role == 'admin'){
				$id = $this->session->userdata('id');
				$user__list = $this->InteractModal->all__users( 'network' );
			    $user_list = $this->InteractModal->get_all_client('client',$id);
					if(!empty($user_list)){
				$client_ids = array();
				foreach($user_list as $users_list){
					$client_ids[] = $users_list['id'];
				}
			$client_id = implode(',',$client_ids);
		    $approve_network_list = $this->InteractModal->get_approve_netword_by_clint_id($client_id);	
			 }
			 if(!empty($approve_network_list)){
			       $all_user_list=array_merge($user__list,$approve_network_list);
				   $pagedata['user_list'] = $all_user_list;
				}else{
					 $pagedata['user_list'] = $user__list;
				}
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/all-network', $pagedata );
				$this->load->view('dashboard/footer');
			}else{
				redirect( base_url() );
			}
		}
	/*===========add__network=======================*/
	function add__network(){
			$role_ = $this->session->userdata('role');
			$role = $this->input->post('role');
			$personInput = $this->input->post('personInput');
			$company_name = $this->input->post('company_name');
			$company_id = $this->input->post('company_id');
			$emailInput = $this->input->post('emailInput');
			$phoneInput = $this->input->post('phoneInput');
			$firm = $this->input->post('firm');
			$website = $this->input->post('website');
			$bio = $this->input->post('bio');
			$userfile = $_FILES['userfile']['name'];
			$password =  md5('InteractRE');
			$check_data = array('user_email' => $emailInput);
			$table = 'users';
			$config['upload_path'] = './assets/img/profiles/';
			$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc';
			$config['max_size']  = 20024;		
			$this->load->library('upload', $config);
			$check_user_availability = $this->InteractModal->check_user_availability( $check_data, $table );
			if(!empty( $check_user_availability )){	
				/*========Network person for multiple admin========= */
					$user_id = $check_user_availability[0]['id'];
					if($role_=='client'){
					$company_id = $this->InteractModal->get_user_meta( $this->session->userdata('id'), 'company_id' );
					}
					if($user_id && $company_id){
						$company_ids = $this->InteractModal->get_user_meta( $user_id, 'company_id');
						$company_ids = json_decode($company_ids);
						if(is_array($company_ids)){
							array_push($company_ids,$company_id);							
							$this->InteractModal->update_user_meta( $user_id, 'company_id' , json_encode($company_ids) );
							$this->session->set_flashdata('msg', 'User added your network.');	
						}else{ 
							if(empty($company_ids)){
								$this->InteractModal->update_user_meta( $user_id, 'company_id' , $company_id );
							}else{
								$company_ids = array($company_ids);
								array_push($company_ids,$company_id);							
								$this->InteractModal->update_user_meta( $user_id, 'company_id' , json_encode($company_ids) );
							}
							$this->session->set_flashdata('msg', 'User added your network.');	
						} 
					} 
					if($role_=='client'){
						redirect( base_url('Interact/add_new_professional'));
					}else{
					    redirect( base_url( 'Interact/all_networks' ));
					}
					/*========Network person for multiple admin========= */		
			}else{
				$data = array('user_email' => $emailInput , 
					  'password' =>  $password,
					  'role' => 'network' ,
					  'status' => 1,
				      'create_date' => date('Y-m-d h:i:s')
					); 
				$response = $this->InteractModal->register_user($data, $table);
				if( array_key_exists('id', $response ) ): // update user meta in database 
					$user_id = $response['id'];
					$this->InteractModal->add_user_meta( $user_id, 'concerned_person' , $personInput );
					$this->InteractModal->add_user_meta( $user_id, 'capability' , $role );
					$this->InteractModal->add_user_meta( $user_id, 'company_id' , $company_id );
					$this->InteractModal->add_user_meta( $user_id, 'company_name' , $company_name );
					$this->InteractModal->add_user_meta( $user_id, 'phone_number' , $phoneInput );
					$this->InteractModal->add_user_meta( $user_id, 'firm' , $firm );
					$this->InteractModal->add_user_meta( $user_id, 'website' , $website );
					$this->InteractModal->add_user_meta( $user_id, 'bio' , $bio );
					$this->InteractModal->add_user_meta( $user_id, 'approve_status' , '' );
					if(!empty($userfile)){
						if ( ! $this->upload->do_upload('userfile')):
							$error = array('error' => $this->upload->display_errors());
							$msg = $error['error'];			
						else:			
							$data = array('upload_data' => $this->upload->data());
							$file_name = $data['upload_data']['file_name'];
						endif;
					$this->InteractModal->update_user_meta( $user_id, 'profile_picture' , $file_name );
					}
					
					$email_content = 'Welcome to interactRE. Thank you for joining the brokers network.';
					$email_data = array('email' => $emailInput,
										'content' => $email_content,
										'subject' => 'Welcome to interactRE'
										);
					$this->send_email( $email_data );
					if($role_=='client'){
						$client_id=$this->session->userdata('id');
						$client_name = $this->InteractModal->get_user_meta( $client_id, 'concerned_person');
						$company_id = $this->InteractModal->get_user_meta( $client_id, 'company_id');
						$admin_name = $this->InteractModal->get_user_meta( $company_id, 'concerned_person');
						$admin_data = $this->InteractModal->get_user_data($company_id);
				        $admin_email = $admin_data[0]['user_email'];
						$admin_content='Hello '.$admin_name.', 
				Your client, '.$client_name.' has added '.$personInput.' as their '.$role.'.';
					$email_ = array('email' =>$admin_email ,
										'content' => $admin_content,
										'subject' => 'Welcome to interactRE'
										);
					$this->send_email( $email_ );
					}
					 $this->session->set_flashdata('msg', 'User added successfully in your network.');
					if($role_=='client'){
						redirect( base_url('Interact/add_new_professional'));
					}else{
					    redirect( base_url( 'Interact/all_networks' ));
					}
				  else:
				  	$msg = $response['error'];
				  	$this->session->set_flashdata('msg', $msg);
				  	redirect( base_url( 'Interact/all_networks' ));
				  endif;
			}		
			
		}
	/*=============update_network_person_in_property=====================*/
		function update_network_person_in_property(){
			    $client_id  = $this->session->userdata('id');
			    $role  = $this->session->userdata('role');
			    $post_id = $this->input->post('post_id');
				$attorney_id = $this->input->post('attorney_id');
				$field_to_update = $this->input->post('field_to_update');
				$network_person_email = $this->input->post('network_person_email');
				$this->InteractModal->update_post_meta($post_id, $field_to_update , $attorney_id);
				if($role=='admin'){
					 
					$company_id = $this->InteractModal->get_user_meta( $attorney_id, 'company_id');	
				}else{
					$company_id = $this->InteractModal->get_user_meta( $client_id, 'company_id');
				}
				$professional_name = $this->InteractModal->get_user_meta( $attorney_id, 'concerned_person');
				$profession_n = $this->InteractModal->get_user_meta( $attorney_id, 'capability');
				$client_name = $this->InteractModal->get_user_meta( $client_id, 'concerned_person');
				$client_phone_number = $this->InteractModal->get_user_meta( $client_id, 'phone_number');
				$client_all_data= $this->InteractModal->get_user_data( $client_id);
				$client_email=$client_all_data[0]['user_email'];
				 $admin_name = $this->InteractModal->get_user_meta( $company_id, 'concerned_person');
				$attorney_emails = $this->InteractModal->get_user_data($company_id);
				$attorney_email = $attorney_emails[0]['user_email'];
			    $subject='Client chooses network person ';
			    $content=' Hello '.$admin_name.',<br />Your client '.$client_name.' has choosen '.$professional_name.' to be their '.$profession_n.'.';
                $content_choose_person=$admin_name.' has a new client he would like to introduce you too,'.$client_name.' Please contact them right away to get started at '.$client_phone_number.' or '.$client_email.'.';	
			   $data = array('email' => $attorney_email,
						'content' => $content,
						'subject' => $subject,
						);	
                $data_client = array('email' => $network_person_email,
						'content' => $content_choose_person,
						'subject' => $subject,
						);	
			$this->send_email($data);
			$this->send_email($data_client); 
		}
		/*==============update_closing_date====================*/
		function update_closing_date(){
			$field__name = $this->input->post('field__name');
			$task_type = $this->input->post('task_type');
			$post_id = $this->input->post('post_id');
			$user_id = $this->input->post('user_id');
			$schedule_date = $this->input->post('schedule_date');
			$estimated_time = $this->input->post('estimated_time');
			
			if( $field__name =='mls_date' ){
				$_schedule_date = $schedule_date;
			}
			else{
				$_schedule_date = $schedule_date.' '. $estimated_time. ':00';				
			}			
			$action_type = $this->input->post('action_type');
			$this->InteractModal->update_post_meta($post_id, $field__name, $_schedule_date);
			$this->session->set_flashdata('msg', 'Closing Date Added' );			
			redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
		}
		/*=============buy_property=====================*/
		function buy_property( $ids ){
			$id = base64_decode(urldecode($ids));
			$role = $this->session->userdata('role');
			$pagedata['user__id'] = $id;
			if(!empty( $role )){
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/buy-property', $pagedata);
				$this->load->view('dashboard/footer');
			}else{
				redirect( base_url());
			}
		}
		/*===========send_email=======================*/
		function send_email( $data ){
			 $email = $data['email'];
			$subject = $data['subject'];
			$msg = $data['content'];
			  $config = array(
				'smtp_user' => 'info@interactacm.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",				
				); 			
			$this->load->library('email', $config);			
			$this->email->to( $email );     
			$this->email->from('info@interactacm.com', 'InteractACM');
			$this->email->subject( $subject );
			$this->email->message( $msg ); 
			$this->email->send();
			return 1 ;
			
		}
	/*=============update_financial_details=====================*/
		function update_financial_details(){
			$date= date('Y-m-d');
			$action_type = $this->input->post('action_type');
			$post_id = $this->input->post('post_id');
			$user_id = $this->input->post('user_id');
			if( $action_type == 'seller' ){
				$bank_name = $this->input->post('bank_name');
				$list_price = $this->input->post('list_price');
				$contract_price = $this->input->post('contract_price');
				$earnest = $this->input->post('earnest');
				$commission = $this->input->post('commission');
				$est_closing = $this->input->post('est_closing');
				$closing_credits = $this->input->post('closing_credits');
				$inspection_credits = $this->input->post('inspection_credits');
				$est_payoff = $this->input->post('est_payoff1');
				$transaction = $this->input->post('transaction');
				//$credit_issued = $this->input->post('credit_issued1');
				$fee = $this->input->post('fee');
				$list_percantage = 0;
				$Comm_in_dollar = 0;
				$Comm_in_dollar1 = 0;
				$est_net = 0;
				if((!empty( $list_price )) && (!empty( $contract_price ))){  
					$list_percantage = $contract_price / $list_price;
					$list_percantage = $list_percantage * 100;
					$list_percantage = round($list_percantage, 2);  
			
				}
				if((!empty( $contract_price )) && (!empty( $commission )) ):
					$Comm_in_dollar1 = $contract_price * $commission / 100;
					$Comm_in_dollar = $Comm_in_dollar1 + $fee;
				endif;
				/*	if((!empty( $est_closing )) &&  (!empty( $est_payoff )) ):
					//$est_net = $est_closing + $closing_credits + $inspection_credits + $est_payoff ;
					$est_net = $contract_price - ($Comm_in_dollar + $est_closing + $inspection_credits + $est_payoff ) ;
				endif;*/ 
				/*----- add est_net calculate Harendra (11-03-2019)-------*/
				$est_net = $contract_price - ($Comm_in_dollar + $est_closing + $inspection_credits + $est_payoff ) ; 
				/*----- end est_net calculate Harendra (11-03-2019)-------*/
					$this->InteractModal->update_post_meta($post_id, 'list_price', $list_price );
					$this->InteractModal->update_post_meta($post_id, 'contract_price', $contract_price );
					$this->InteractModal->update_post_meta($post_id, 'list_percantage', $list_percantage );
					$this->InteractModal->update_post_meta($post_id, 'earnest', $earnest );
					$this->InteractModal->update_post_meta($post_id, 'commission', $commission );
					$this->InteractModal->update_post_meta($post_id, 'Comm_in_dollar', $Comm_in_dollar );
					$this->InteractModal->update_post_meta($post_id, 'est_closing', $est_closing );
					$this->InteractModal->update_post_meta($post_id, 'closing_credits', $closing_credits );
					$this->InteractModal->update_post_meta($post_id, 'inspection_credits', $inspection_credits );
					$this->InteractModal->update_post_meta($post_id, 'est_payoff', $est_payoff );
					$this->InteractModal->update_post_meta($post_id, 'transaction', $transaction );
					//$this->InteractModal->update_post_meta($post_id, 'credit_issued', $credit_issued );
					$this->InteractModal->update_post_meta($post_id, 'fee', $fee );
					$this->InteractModal->update_post_meta($post_id, 'bank_name', $bank_name );
					$this->InteractModal->update_post_meta($post_id, 'est_net', $est_net );
					$this->InteractModal->update_post_meta($post_id, 'update_financial_data', $date );				
					$this->session->set_flashdata('msg', 'Financial Information Saved' ); 				
					redirect( base_url('Interact/property_details/'.urlencode( base64_encode($post_id)).'/'.urlencode( base64_encode($user_id)). '/'.urlencode( base64_encode( $action_type )  ) ) );
			}else{
					$loan_type = $this->input->post('loan_type');
					$initial_earnest = $this->input->post('initial_earnest');
					$additional_earnest = $this->input->post('additional_earnest');
					$estimated_closing = $this->input->post('estimated_closing');
					$purchase_price = $this->input->post('purchase_price');
					$closing_credits = $this->input->post('closing_credits');
					$inspection_credits = $this->input->post('inspection_credits');					
					$this->InteractModal->update_post_meta($post_id, 'loan_type', $loan_type );
					$this->InteractModal->update_post_meta($post_id, 'initial_earnest', $initial_earnest );
					$this->InteractModal->update_post_meta($post_id, 'additional_earnest', $additional_earnest );
					$this->InteractModal->update_post_meta($post_id, 'estimated_closing', $estimated_closing );
					$this->InteractModal->update_post_meta($post_id, 'purchase_price', $purchase_price );
					$this->InteractModal->update_post_meta($post_id, 'closing_credits', $closing_credits );
					$this->InteractModal->update_post_meta($post_id, 'inspection_credits', $inspection_credits );
					$this->session->set_flashdata('msg', 'Financial Information Saved' );
					$user_emails = $this->InteractModal->get_user_data($user_id);
					$user_email = $user_emails[0]['user_email']; 
					$data = array('email' => $user_email,
											'content' => 'Update financial details successfully. <a href="'.base_url('Interact/').'">Please click here to login</a> ',
											'subject' => 'Update financial details !',
											);
					$this->send_email($data);
					redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
			}					
					
		}
		/*=============update_task_details=====================*/
		function update_task_details(){
			$value = $this->input->post('value');
			$pk = $this->input->post('pk');
				$this->db->set('details', $value);
				$this->db->where( 'task_id = '. $pk );
				$this->db->update( 'tasks');
		}
		/*=============delete_Action_item=====================*/
		function delete_Action_item(){
			$task_id = $this->input->post('task_id');
			$this->db->where('task_id', $task_id);
			$this->db->delete('tasks');
		}
		/*=============update_task_dates=====================*/
		function update_task_dates(){
			$value = $this->input->post('value');
			$pk = $this->input->post('pk');
				$this->db->set('schedule_date', $value);
				$this->db->where( 'task_id = '. $pk );
				$this->db->update( 'tasks');
		}
		/*==============change_password====================*/
		public function change_password(){	
			$user_id = $this->session->userdata('id');
			if( !empty($user_id)):
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/change-password');
				$this->load->view('dashboard/footer');
			else:
				redirect( base_url( 'Interact/login' ));
			endif;
		}
		/*=============change_password_confirm=====================*/
		public function change_password_confirm(){		
					$id = $this->input->post('id');	
        			$n_password = $this->input->post('new_password');	
        			$c_password = $this->input->post('confirm_password');

			if($n_password == $c_password){
					$user_info = $this->InteractModal->get_user_info( $id );	
					foreach ($user_info as $my_info):					
						$db_password = $my_info['password'];
					endforeach; 				
					if(md5($this->input->post('old_password')) == $db_password){					
					$new_password = md5($this->input->post('new_password'));
					$response = $this->InteractModal->change_password( $id, $new_password );					
					}
					else{
						$this->session->set_flashdata('message_name', 'Old password doesn\'t match!');
						redirect('Welcome/change_password');						
					}
					if(!empty($response)){
						$this->session->set_flashdata('message_name', 'Successfully Update');
						redirect('Welcome/change_password');
					}
					else{
						$this->session->set_flashdata('message_name', 'Update Failed ');
						redirect('Welcome/change_password');
						
					}
			}	
			else{
				$this->session->set_flashdata('message_name', 'New and Confirm password does not match!');
				redirect('Welcome/change_password');
				}
        }
	/*=============update_onesignal_user_id=====================*/	
	function update_onesignal_user_id(){
		$one_signal_id = $this->input->post('user_id');
		$user_id = $this->session->userdata('id');
		$this->InteractModal->update_user_meta( $user_id, 'one_signal_id', $one_signal_id );
	}
	/*==============send_action_notification====================*/
	function send_action_notification( $data, $onesignal_id ){
		$content = array(
			"en" => $data
			);
		$fields = array(
			'app_id' => "01048e48-3534-4d9c-b4f4-59bfbde7c1df",
			'include_player_ids' => array( $onesignal_id ),
			'data' => array("InteractRE" => "New Notification"), 
			'contents' => $content
		);
		$fields = json_encode($fields);
    	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
												   'Authorization: Basic YzMxMjM0ZTgtN2JmMC00ZmE2LTgwNjctZTRlMWU0Y2ZiNmUx'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		$response = curl_exec($ch);
		curl_close($ch);
	}
	/*==============send_text_message====================*/
	function send_text_message($mobile,$email,$subject,$msg){ 
	 $data['customer_data'] = array( 
								'customer_phonenumber' => $mobile,
								'customer_email' => $email,
								'subject' => $subject,
								'content' => $msg,
								); 									
	$respose=$this->load->view('text_on_add',$data);		
	}
	/*=============test=====================*/
	function text_msg_notification(){
		echo phpinfo();
	}
	/*=============add_rating_in_db=====================*/
	function add_rating_in_db(){
		$user_id = $this->session->userdata('id');
		$user_name = $this->session->userdata('name');
		$broker_id = $this->input->post('broker_id');
		$rating = $this->input->post('rating');
		$broker_name = $this->input->post('broker_name');
		
		$data = array('stars' => $rating ,
					  'user_id' => $user_id,
					  'user_name' => $user_name,
					  'broker_name' => $broker_name,
					  'broker_id' => $broker_id
				  );
		$table = 'broker_rating';	  
		$response = $this->InteractModal->add_rating_db( $data, $table );
		if( array_key_exists('id', $response ) ):
			echo 'success';
		else:
			echo 'error';
		endif;
	}
	/*============feedback======================*/
	function feedback( $id ){
		$id =  $this->uri->segment(3);
		$url = base_url('Interact/feedback/'.$id );
		$array = array( 'link' => $url, 'status' => 0 );
		$feedback_data = $this->InteractModal->get_feedback_data( $array );
		$array1 = array( 'link' => $url, 'status' => 1 );
		$feedback_data1 = $this->InteractModal->get_feedback_data( $array1 );
		$broker_id = $feedback_data1[0]['broker_id'];
		$response = $this->InteractModal->signature_user_email($broker_id);
		$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person');
		$phone = $this->InteractModal->get_user_meta($broker_id,'phone_number');
		$website = $this->InteractModal->get_user_meta($broker_id,'website');
		$pagedata['feedback_data'] = $feedback_data;
		$pagedata1['feedback_data'] = $feedback_data1;
		$pagedata1['response'] = $response;
		$pagedata1['phone'] = $phone;
		$pagedata1['concerned_person'] = $concerned_person;
		$pagedata1['website'] = $website;
		$id = base64_decode(urldecode( $id ));
		$this->load->view('client/header');
		if(!empty( $feedback_data )): 
			$this->load->view('feedback-form', $pagedata );
		else:
			$this->load->view('expired-link', $pagedata1 );
		endif;
		$this->load->view('client/footer');
	}  
/*==============submit_feedback====================*/
	function submit_feedback(){
		$id = $this->input->post('id');
		$property_id = $this->input->post('property_id');
		$broker_id = $this->input->post('broker_id');
		$showing_id = $this->input->post('showing_id');
		$query = $this->InteractModal->get_showing_log($showing_id );
			if(!empty($query)){ 
				foreach($query as $queries){
					$details=	$queries['details'];
					$detailss = @unserialize($details);
					$user_name = $detailss['user_name'];
					$showing_email = $detailss['showing_email'];
				}
			}
		$details = $this->input->post('details');
		$price_opinion = $this->input->post('price_opinion');
		$price_notes = $this->input->post('price_notes');
		$opinion_interest = $this->input->post('opinion_interest');
		$interest_notes = $this->input->post('interest_notes');
		$property_quality = $this->input->post('property_quality');
		$quality_notes = $this->input->post('quality_notes');		
		$opinion_array = array('details'=> $details,
									 'showing_id' => $showing_id ,
									 'broker_id' => $broker_id ,
									 'property_id' => $property_id ,
									 'price_opinion' => $price_opinion ,
									 'interest_opinion' => $opinion_interest,
									 'property_quality' => $property_quality ,
									 /* 'price_opinion_notes' => $price_notes ,
									 'interest_opinion_notes' => $interest_notes ,
									 'property_quality_notes' => $quality_notes , */
									 'feedbackdate' => date('Y-m-d h:i:s'),
									);
			if(  $this->db->insert( 'property_rating' , $opinion_array ) ){
			$decode_id = urlencode(base64_encode( $showing_id ));
			$data = array('id' => $id );
			$this->db->set('status', 1 );
			$this->db->where( $data );
			$this->db->update('feedback_links');			
			$where_array = array('task_id' =>  $showing_id);		
			$this->db->where( $where_array );
			$this->db->set('status', 'complete');
			$this->db->update( 'tasks' );
			$broker_emails = $this->InteractModal->get_user_data($broker_id);
			$property_address = $this->InteractModal->get_post_meta($property_id, 'property_address');
			$response = $apartment_no= $city=$street_number='';
		    $property_address = $this->InteractModal->get_post_meta($property_id, 'property_address');
		    $street_number = $this->InteractModal->get_post_meta($property_id, 'street_number');
		    $city = $this->InteractModal->get_post_meta($property_id, 'city');
		    $apartment_no = $this->InteractModal->get_post_meta($property_id, 'apartment_no');
		    if(!empty($apartment_no))$apartment_no = $apartment_no.' @ ';
		    if(!empty($street_number))$street_number = $street_number.', ';
		    $property_address= $apartment_no.$street_number.$city;
			$broker_email = $broker_emails[0]['user_email'];
			$email_data = array('email' => $broker_email,
									'content' => 'New  property feedback  received. property address: '.$property_address.'',
									'subject' => 'New feedback  received.',
									);
			$this->send_email( $email_data );
			////for client mail/////
			$user_id = $this->InteractModal->get_post_meta($property_id, 'initiator_id');
				if(!empty($user_id)){
				///=====send push notification======///
					$onesignal_id = $this->InteractModal->get_user_meta( $user_id, 'one_signal_id' );
					if(!empty( $onesignal_id )){
						$notification_data = 'New  property feedback  received. property address: '.$property_address.'';
						$this->send_action_notification( $notification_data, $onesignal_id );
					}
				///=====send push notification======///
					$temp_id=5;
					$where = array( 'id' =>$user_id);
					$user_data=$this->InteractModal->single_field_value('users',$where);
					$email= $user_data[0]['user_email'];					
					$company_id = $this->InteractModal->get_user_meta( $user_id, 'company_id');
					///get auto email content data 
					$status_template = $this->InteractModal->get_user_meta( $company_id, 'status_template_id_'.$temp_id );
				if(($status_template ==1)){
					$subject = $this->InteractModal->get_user_meta( $company_id, 'subject_template_id_'.$temp_id );
					$content = $this->InteractModal->get_user_meta( $company_id, 'content_template_id_'.$temp_id );
					$user_name = $this->InteractModal->get_user_meta( $user_id, 'concerned_person');
					$phone_number = $this->InteractModal->get_user_meta( $user_id, 'phone_number');
					$logo_content = '<img src="'.base_url().'/assets/img/profiles/logo_(2).png" height="auto" width="100" alt="Logo">'; 					
					$content=nl2br($content);
					$content = str_replace("_NAME_",$user_name,$content);
					$content = str_replace("_PHONE_",$phone_number,$content);
					$content = str_replace("_LOGO_",$logo_content,$content);
					$content = str_replace("_EMAIL_",$email,$content);
					$content = str_replace("_DATE_",date('d-F-Y'),$content);
				}else{
					$template_list = $this->InteractModal->get_email_template($temp_id);
					foreach( $template_list as $template_lists ):
						$subject = $template_lists['subject']; 
						$content = $template_lists['content']; 
					endforeach;				
				}			
				///get auto email content data 				
				$email_data = array('email' => $email,
									'content' => $content,
									'subject' => $subject
									);	
					if($this->send_email($email_data)){						
						$this->session->set_flashdata('msg', 'New feedback  received client');
					} 
					
				}			
			////for client mail/////			
			//$this->session->set_flashdata('msg', 'sucess');
			$redirect_url = base_url('Interact/feedback/'.$decode_id);
			redirect( $redirect_url );			
		}else{
			$this->session->set_flashdata('msg', 'error');
			$redirect_url = base_url('Interact/feedback/'.$decode_id); 
			redirect( $redirect_url );
		}  							
		
	}
	/*============add_new_professional======================*/
	function add_new_professional(){
		$role = $this->session->userdata('role');
		$user_id = $this->session->userdata('id');
		 if(!empty($role)){
                $pagedata['mode'] = '';
		        $all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
				$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
		        $footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
				$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
				$footer_data['user__id'] = $user_id;
		if($role=='client'){
			$this->load->view('dashboard/header');
		    $this->load->view('dashboard/add_new_professional', $pagedata);
			$this->load->view('client/footer',$footer_data); 
		}else{
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/add_new_professional', $pagedata);
		$this->load->view('dashboard/footer'); 
		}
		 } else {
          $redirect_url = base_url('Interact/login');
          redirect( $redirect_url );
         }
	}
	/*============edit_network======================*/
	function edit_network( $ids ){
		$id = base64_decode(urldecode($ids));
		$role = $this->session->userdata('role');
		if(!empty($role)){
		$pagedata['mode'] = 'edit';
		if($role=='client'){
			
			$this->load->view('dashboard/header');
		    $this->load->view('dashboard/add_new_professional', $pagedata);
			$this->load->view('client/footer'); 
		}else{
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/add_new_professional', $pagedata);
		$this->load->view('dashboard/footer');
		
		} 
		}else {
          $redirect_url = base_url('Interact/login');
          redirect( $redirect_url );
         }
	}
	/*==============update_network_details====================*/
	function update_network_details(){
		$user__id = $this->input->post('user__id');
		$capability = $this->input->post('role');
		$phone_number = $this->input->post('phoneInput');
		$concerned_person = $this->input->post('personInput');
		$firm = $this->input->post('firm');
		$bio = $this->input->post('bio');
		$website = $this->input->post('website');
		$userfile = $_FILES['userfile']['name'];
		$config['upload_path'] = './assets/img/profiles/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc';
		$config['max_size']  = 20024;		
		$this->load->library('upload', $config);
		$this->InteractModal->update_user_meta( $user__id, 'concerned_person', $concerned_person );
		$this->InteractModal->update_user_meta( $user__id, 'phone_number', $phone_number );
		$this->InteractModal->update_user_meta( $user__id, 'firm', $firm );
		$this->InteractModal->update_user_meta( $user__id, 'capability', $capability );
		$this->InteractModal->update_user_meta( $user__id, 'website', $website );
		$this->InteractModal->update_user_meta( $user__id, 'bio', $bio );
		if(!empty($userfile)){
				if ( ! $this->upload->do_upload('userfile')):
					$error = array('error' => $this->upload->display_errors());
					$msg = $error['error'];			
				else:			
					$data = array('upload_data' => $this->upload->data());
					$file_name = $data['upload_data']['file_name'];
				endif;
			$this->InteractModal->update_user_meta( $user__id, 'profile_picture' , $file_name );
		}
		$this->session->set_flashdata('msg', 'Updated Successfully');
		$redirect_url = base_url('Interact/edit_network/'.urlencode(base64_encode($user__id)));
		redirect( $redirect_url );
		
	}	
/*=============edit__listing=====================*/
	function edit__listing(){ 
		$file_name = '';
		$post_id = $this->input->post('post_id');
		$post_type = $this->input->post('post_type');		
		$property_name = $this->input->post('property_name');
		$proprty_type = $this->input->post('proprty_type');
		$description = $this->input->post('property_description');
		$user_id = $this->input->post('user_id');
		$userfile = $_FILES['userfile']['name'];
		$street_number = $this->input->post('street_number');
		$city = $this->input->post('city');
		$state = $this->input->post('state');
		$postal_code = $this->input->post('postal_code');
		$decode_post_id = urlencode(base64_encode($post_id)); 
		$decode_user_id = urlencode(base64_encode($user_id)); 
		$decode_type = urlencode(base64_encode($post_type)); 
		$decode_action = urlencode(base64_encode('edit')); 				
			if(!empty($userfile)){
				$config['upload_path'] = './property-images/';
				$config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc';
				$config['max_size']  = 20024;		
				$this->load->library('upload', $config);
				if ( ! $this->upload->do_upload('userfile'))
				{
					$error = array('error' => $this->upload->display_errors());
					$msg = $error['error'];
				}
				else
				{							
					$data = array('upload_data' => $this->upload->data());
					$file_name = $data['upload_data']['file_name'];
				}
				$data = array(
						'post_content' => $description,
						'post_title' => $property_name ,
						'featured_image' => $file_name
					);
			} 
			else{
				$data = array(
						'post_content' => $description,
						'post_title' => $property_name 
					);			
			}	
		if($post_type == 'seller'){	
				$apartment_no = $this->input->post('apartment_no');
				$this->InteractModal->update_listing_in_db( $data, $post_id );			
				$this->InteractModal->update_post_meta( $post_id, 'apartment_no' , $apartment_no );
				$this->InteractModal->update_post_meta( $post_id, 'street_number' , $street_number );
				//$this->InteractModal->update_post_meta( $post_id, 'route' , $route );
				$this->InteractModal->update_post_meta( $post_id, 'city' , $city );
				$this->InteractModal->update_post_meta( $post_id, 'state' , $state );
				$this->InteractModal->update_post_meta( $post_id, 'postal_code' , $postal_code );
				//$this->InteractModal->update_post_meta( $post_id, 'country' , $country );
				if(!empty($street_number)){$street_number = $street_number.', ';}
				if(!empty($apartment_no)){$apartment_no = $apartment_no.', ';}
				if(!empty($city)){$city = $city.', ';}
				if(!empty($state)){$state = $state.', ';}
				if(!empty($postal_code)){$postal_code = $postal_code.', ';}
				$property_address=$street_number.$apartment_no.$city.$state.$postal_code;
				$this->InteractModal->update_post_meta( $post_id, 'property_address' , $property_address );
				$this->InteractModal->update_post_meta( $post_id, 'proprty_type' , $proprty_type );
				$this->session->set_flashdata('msg','Listing Edited Successfully'); 
				redirect( base_url( 'Interact/dashboard' ));
				//redirect( base_url( 'Interact/add_listing/'.$decode_post_id.'/'.$decode_user_id.'/'.$decode_type.'/'.$decode_action));
		} 
		else{	
			$apartment_no = $this->input->post('apartment_no');		
			$this->InteractModal->update_listing_in_db( $data, $post_id );			
			$this->InteractModal->update_post_meta( $post_id, 'proprty_type' , $proprty_type );
			$this->InteractModal->update_post_meta( $post_id, 'apartment_no' , $apartment_no );			
			$this->InteractModal->update_post_meta( $post_id, 'street_number' , $street_number );
			//$this->InteractModal->update_post_meta( $post_id, 'route' , $route );
			$this->InteractModal->update_post_meta( $post_id, 'city' , $city );
			$this->InteractModal->update_post_meta( $post_id, 'state' , $state );
			$this->InteractModal->update_post_meta( $post_id, 'postal_code' , $postal_code );
			//$this->InteractModal->update_post_meta( $post_id, 'country' , $country );
			if(!empty($street_number)){$street_number = $street_number.', ';}
			if(!empty($apartment_no)){$apartment_no = $apartment_no.', ';}
			if(!empty($city)){$city = $city.', ';}
			if(!empty($state)){$state = $state.', ';} 
			if(!empty($postal_code)){$postal_code = $postal_code.', ';}
			 $property_address=$street_number.$apartment_no.$city.$state.$postal_code;
			$this->InteractModal->update_post_meta( $post_id, 'property_address' , $property_address );			
			$this->session->set_flashdata('msg','Listing Edited Successfully');
				redirect( base_url( 'Interact/dashboard' ));					
		}	
		
	}
	/*==========cancel__showing========================*/
	function cancel__showing(){
		$showing_id = $this->input->post('showing_id');
		$link_id = $this->input->post('link_id');
		$response = $this->InteractModal->cancel_AND_DELETE_showing( $showing_id, $link_id );
		echo $response;		 
	}	
	/*===========mark_As_complete_showing=======================*/
	function mark_As_complete_showing(){
	 	$showing_id = $this->input->post('showing_id');
	 	$link_id = $this->input->post('link_id');
		 $email_id = $this->input->post('email_id');
		 $contact = $this->input->post('contact');
		 $address = $this->input->post('address');
		 $time = $this->input->post('time');
		 $feedback_link = $this->input->post('feedback_link');
		 $broker_id = $this->input->post('broker_id');
		$response = $this->InteractModal->signature_user_email($broker_id);
		 $email_broker = $response[0]['user_email'];
		$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person');  
		$response = $this->InteractModal->mark_As_complete_showing( $showing_id, $link_id );
		$data= array(
			'request_feedback' => 1,
			);
			$response = $this->InteractModal->update_task_details($data,$showing_id);$config = array(
				'smtp_user' => 'info@interactre.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",				
			);
			$subject = 'Please provide feedback for our recent showing';
			$msg = 'Hi,<br /><br />We would really appreciate your feedback, please tell us what you thought about your recent showing at "'. $address .'" on "'. $time .'". <br /><a href="'. $feedback_link .'">Please click here to submit your feedback </a> <br /><br /> Thank you,<br /> <br /> '. $concerned_person .' <br /> '. $contact .' <br /> '. $email_broker .'';
			$this->load->library('email', $config);
			$this->email->from('info@interactacm.com', 'InteractACM');
			$this->email->set_header('InteractACM', 'Interact Notifications');
			$this->email->to( $email_id );
			$this->email->subject( $subject );
			$this->email->message( $msg ); 
			$email_response = $this->email->send();	
			if( $email_response ){
				echo'Feedback Link sent';
			}else{
				echo'Unable to send feedback link at this time. Please try again';
		}    
	}
/*==============delete_feedback_link====================*/
	function delete_feedback_link(){
		$showing_id = $this->input->post('showing_id');
		$link_id = $this->input->post('link_id');
		$response = $this->InteractModal->del_feedback_link($showing_id,$link_id);
		return true;
	}
/*============send_feedback_link======================*/	
	function send_feedback_link()
	{
		$email_id = $this->input->post('email_id');
		$feedback_link = $this->input->post('feedback_link');
		//echo $feedback_link;
		$address = $this->input->post('address');
		$time = $this->input->post('time');
		$broker_id = $this->input->post('broker_id');
		$response = $this->InteractModal->signature_user_email($broker_id);
		$email_broker = $response[0]['user_email'];
		$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person');
		$phone = $this->InteractModal->get_user_meta($broker_id,'phone_number');
		$email_content = 'Hi, Please click on this link to provide the feedback for the showing, <br /> <a href="'. $feedback_link .'">Click here</a>'; 		
		$email = $email_id;
		$subject = 'Please provide feedback for our recent showing';
		$msg = 'Hi,<br /><br />We would really appreciate your feedback, please tell us what you thought about your recent showing at "'. $address .'" on "'. $time .'". <br /><a href="'. $feedback_link .'">Please click here to submit your feedback </a> <br /><br /> Thank you,<br /> <br /> '. $concerned_person .' <br /> '. $phone .' <br /> '. $email_broker .'';			
		$config = array(
			'smtp_user' => 'info@interactre.com',
			'mailtype'  => 'html',
			'charset'  => 'utf-8',
			'starttls'  => true,
			'newline'   => "\r\n",				
		); 
		$this->load->library('email', $config);
		$this->email->from('info@interactacm.com', 'InteractRE');
		$this->email->set_header('InteractRE', 'Interact Notifications');
		$this->email->to( $email );
		$this->email->subject( $subject );
		$this->email->message( $msg ); 
		$response = $this->email->send();
		
		if( $response ){
			echo 'Feedback Link sent';
		}else{
			echo 'Unable to send feedback link at this time. Please try again';
			//echo $this->email->print_debugger();
		}
	}
/*===============text_send_feedback_link===================*/
	function text_send_feedback_link(){
		$mobiles = $this->input->post('phone');
		//$email = $this->input->post('email');
		$feedback_link = $this->input->post('feedback_link');		
		$subject='';
		$email='';
		$msg = 'Hi, Please click on this link to provide the feedback for the showing, Click here '. $feedback_link .'';		
		$regex = "/^(\(?\d{3}\)?)?[- .]?(\d{3})[- .]?(\d{4})$/"; 
		$mobile = preg_replace($regex, "\\1\\2\\3", $mobiles);
		$data['customer_data'] = array( 
								'customer_phonenumber' => $mobile,
								'customer_email' => $email,
								'subject' => $subject,
								'content' => $msg,
								);	
			$response=$this->load->view('text_on_add',$data);
	}
	/*=============add_education=====================*/
	function add_education(){ 
		$user_id = $this->session->userdata('id');
		if(!empty($user_id)):
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/add_education');
			$this->load->view('dashboard/footer');
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*==========add__education========================*/	
	function add__education(){ 
		$id = $this->session->userdata('id');
		$task_type = $this->input->post('_for');
		$education_type = $this->input->post('education_type');
		$document_link = $this->input->post('document_link');
		$link_title = $this->input->post('link_title');
		$config['upload_path'] = './documents/';
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc|csv|xls|xlsx|PDF';
        $config['max_size']  = 150000;
        $this->load->library('upload', $config);
		if(!empty($document_link)){	
			$count = count( $task_type );
			for($i = 0; $i < $count; $i++  ){
			$_deatils = array('education_type'=> $education_type,'link_title'=> $link_title[$i],'document_link' => $document_link[$i]);
			$serialized_array = serialize($_deatils); 
			$__data = array(
				'details' => $serialized_array,
				'date' => date('Y-m-d h:i:s'),
				'task_type' => $task_type[$i],
				'company_id' => $id,				
				);
			$response = $this->InteractModal->save_new_task_db( $__data );
			}
			$this->session->set_flashdata('msg', 'Task added successfully');
			redirect( base_url('Interact/add_education/'));
		}else
		{	$count = count($_FILES['userfile']['size']);
			foreach($_FILES as $key=>$value)
				for($s=0; $s<=$count-1; $s++) {
				 	$_FILES['userfile']['name']=$value['name'][$s];
					$_FILES['userfile']['type'] = $value['type'][$s];
					$_FILES['userfile']['tmp_name'] = $value['tmp_name'][$s];
					$_FILES['userfile']['error'] = $value['error'][$s];
					$_FILES['userfile']['size'] = $value['size'][$s]; 
					$this->upload->do_upload();
					$data = $this->upload->data();
					$file_name = $data['file_name']; 
					$_deatils = array('education_type'=> $education_type,'link_title'=> $link_title[$s],'document_file' => $file_name);
					$serialized_array = serialize($_deatils);					
					$__data = array(
							'details' => $serialized_array,
							'date' => date('Y-m-d h:i:s'),
							'task_type' => $task_type[$s],
							'company_id' => $id,
						);				
					$response = $this->InteractModal->save_new_task_db( $__data );
				}			
			if( array_key_exists('id', $response ) && isset($response['id'])):
				$this->session->set_flashdata('msg', 'Document added successfully');
				redirect( base_url('Interact/add_education/'));
			else:
				$msg = $response['error'];
				$this->session->set_flashdata('msg', $msg );
				redirect( base_url('Interact/add_education/'));				
			endif;
        }
    }
    /*============view_all_education======================*/
	function view_all_education(){ 
		$id = $this->session->userdata('id');
		if(!empty($id)):
		$all_education = $this->InteractModal->get_all_education_details($id);		
		$pagedata['all_education'] = $all_education;
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/view_all_education',$pagedata);
		$this->load->view('dashboard/footer');
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*===========delete_document=======================*/
	function delete_document(){
		$task_id = $this->input->post('task_id');
		$file_name = $this->input->post('file_name');
		$file_directory = './documents/';
		$file_path = $file_directory.''.$file_name;
		if (is_readable( $file_path ) && unlink( $file_path )) {
			$this->db->delete('tasks', array('task_id' => $task_id) );
			echo "success";
		} else {
			echo "error";
		}
	}
	/*============add_testimonial======================*/
	function add_testimonial(){ 
		$user_id = $this->session->userdata('id');
		if(!empty($user_id)):
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/add_testimonial');
		$this->load->view('dashboard/footer');			
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*=============add_testimonals=====================*/
	function add_testimonals(){ 
		$broker_id = $this->input->post('user__id');
		$review = $this->input->post('review');
		$client_name = $this->input->post('client_name');
			$data = array('broker_id' => $broker_id ,
					  'review' => $review,
					  'create_date' => date('Y-m-d h:i:s'),
					  'client_name' =>$client_name
			);			
			$response = $this->InteractModal->save_new_testimonial_db( $data );
			if( array_key_exists('id', $response ) && isset($response['id'])):
				$this->session->set_flashdata('msg', 'Task testimonial successfully');
				redirect( base_url('Interact/add_testimonial/'));
			else:
				$msg = $response['error'];
				$this->session->set_flashdata('msg', $msg );
				redirect( base_url('Interact/add_testimonial/'));				
			endif;
    }
    /*==============all_testimonals====================*/
	function all_testimonals(){
		$id = $this->session->userdata('id');
		if(!empty($id)):
			$all_testimonals = $this->InteractModal->get_all_testimonals($id);		
			$pagedata['all_testimonals'] = $all_testimonals;
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/all_testimonals',$pagedata);
			$this->load->view('dashboard/footer');			
		else:
			redirect( base_url( 'Interact/login' ));
		endif;
	}
	/*=========change_post_status=========================*/
	function change_post_status(){
		$post_status = $this->input->post('post_status');
		$post_id = $this->input->post('post_id');  
		$type = $this->input->post('post_type');
		$this->db->set('post_status', $post_status );
		$this->db->where( 'ID = '. $post_id  );
		if( $this->db->update('post')){
			echo 'success';
			 if($type == 'seller'){
				if($post_status==2 || $post_status==0 ){
				$this->InteractModal->update_post_meta($post_id,"DOM_close_date",date("Y-m-d H:i:s"));
			   }
		    }
		}else{
			echo 'error';
		}
	}
	/*============delete_test_item======================*/
	function delete_test_item(){
		$test_id = $this->input->post('test_id');
		$this->db->where('id', $test_id);
		$this->db->delete('testimonial');
	}
	/*==============update_financial_additional_fields====================*/
	function update_financial_additional_fields(){
		$action_type = $this->input->post('action_type');
		$post_id = $this->input->post('post_id');
		$user_id = $this->input->post('user_id');
		$hoa_dues = $this->input->post('hoa_dues');
		$special_assessment = $this->input->post('special_assessment');
		$move_in_fee = $this->input->post('move_in_fee');
		$move_out_fee = $this->input->post('move_out_fee');
		$more_policy = $this->input->post('more_policy');
	    $refundable_deposits = $this->input->post('refundable_deposits');
	 	$pet_policy = $this->input->post('pet_policy');
		$this->InteractModal->update_post_meta($post_id, 'hoa_dues', $hoa_dues);
		$this->InteractModal->update_post_meta($post_id, 'pet_policy', $pet_policy);
		$this->InteractModal->update_post_meta($post_id, 'refundable_deposits', $refundable_deposits);
		$this->InteractModal->update_post_meta($post_id, 'special_assessment', $special_assessment);
		$this->InteractModal->update_post_meta($post_id, 'move_in_fee', $move_in_fee);
		$this->InteractModal->update_post_meta($post_id, 'move_out_fee', $move_out_fee);
		$this->InteractModal->update_post_meta($post_id, 'more_policy', $more_policy);
		$this->session->set_flashdata('msg', 'Financial fields added' );
		redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
	}
	/*==============update_closing_details====================*/
	function update_closing_details(){
		$date= date('Y-m-d');
		$action_type = $this->input->post('action_type');
		$post_id = $this->input->post('post_id');
		$user_id = $this->input->post('user_id');
		$schedule_date = $this->input->post('schedule_date');
		$estimated_time = $this->input->post('estimated_time');
		$_closing_date = $schedule_date.' '. $estimated_time. ':00';
		$closing_company = $this->input->post('closing_company');
		$closing_location = $this->input->post('closing_location');
		if($closing_location):
			$latitude = $longitude="";
			$prepAddr = str_replace(', ',' ',$closing_location);
			$final_address = str_replace(' ','+',$prepAddr);
			$geocode=file_get_contents('https://maps.google.com/maps/api/geocode/json?address='.$final_address.'&sensor=false&key=AIzaSyAtVchOE56ZJyqA-K9hM1WAevCeOhnsi30');
			$output= json_decode($geocode);
			$latitude = $output->results[0]->geometry->location->lat;
			$longitude = $output->results[0]->geometry->location->lng;
			$this->InteractModal->update_post_meta($post_id, 'closing_latitude', $latitude);
			$this->InteractModal->update_post_meta($post_id, 'closing_longitude', $longitude);		
		endif;
		$this->InteractModal->update_post_meta($post_id, 'closing_date', $_closing_date);
		$this->InteractModal->update_post_meta($post_id, 'closing_company', $closing_company);
		$this->InteractModal->update_post_meta($post_id, 'closing_location', $closing_location);
		$this->InteractModal->update_post_meta($post_id, 'update_closing_data', $date );
		$this->session->set_flashdata('msg', 'Closing Details Added' ); 
		$user_emails = $this->InteractModal->get_user_data($user_id);	
		redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
	}
	/*==============refer_to_friend_email====================*/
	function refer_to_friend_email(){
		$action_type = $this->input->post('action_type');
		$post_id = $this->input->post('post_id');
		$user_id = $this->input->post('user_id');
			$subject = $this->input->post('subject');
			$email = $this->input->post('email');
			$msg = nl2br($this->input->post('msg'));
			//$msg = str_replace(array("\r\n", "\r", "\n"), "<br />", $this->input->post('msg')); 
			$config = array(
				'smtp_user' => 'info@interactre.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",
				
			);
			$this->load->library('email', $config);
			$this->email->from('info@interactacm.com', 'InteractRE');
			$this->email->set_header('InteractRE', 'Interact Notifications');
			$this->email->to( $email );
			$this->email->subject( $subject );
			$this->email->message( $msg ); 
			//$response = $this->email->send();
			 if($this->email->send()){
				$this->session->set_flashdata('msg', 'Mail Send successfully' ); 
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));				
			}
			else{
				$this->session->set_flashdata('msg', 'Mail Sending Error! Try Again' ); 
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
			} 		
		}
	/*============delete_post======================*/	
	function delete_post(){
		$post_id = $this->input->post('post_id');
		if(!empty($post_id )){
		$this->db->delete('post', array('ID' => $post_id)); 
		$this->db->delete('post_meta', array('post_id' => $post_id));
		$this->db->delete('tasks', array('post_id' => $post_id));
		echo 'success';	
		//$this->session->set_flashdata('msg', 'Listing Delete Successfully.' ); 
		}else{
			echo 'error';
			//$this->session->set_flashdata('msg', 'Error while processing your request.' ); 
		}
	}
	/*============showing_log======================*/	
	function showing_log(){
		$user_id  = $this->session->userdata('id');	
		$role = $this->session->userdata('role');
		$post_id =  $this->uri->segment(3);
		$post_ids = base64_decode( urldecode( $post_id )); 	
		if($role == 'client'){
		$all_showings = $this->InteractModal->get_showing_by_post( $post_ids );
		$pagedata['all_showings'] = $all_showings;
		$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
		$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
		$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
		$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
		$footer_data['user__id'] = $user_id; 
		$this->load->view('client/header');
		$this->load->view('client/showing_log', $pagedata );
		$this->load->view('client/footer',$footer_data); 
			}else{
				redirect( base_url() );
			} 
	} 
	/*===============inspection_data_===================*/	
	function inspection_data_(){ 
				$this->load->helper(array('form', 'url'));
				$post_id = $this->input->post('post_id');	
				$user_id = $this->input->post('user_id');	
				$action_type = $this->input->post('action_type');	
				$inspection_date = $this->input->post('inspection_date');	
				$inspection_time = $this->input->post('inspection_time');	
				$this->InteractModal->update_post_meta($post_id, 'inspection_date', $inspection_date);
				$this->InteractModal->update_post_meta($post_id, 'inspection_time', $inspection_time);
				$this->InteractModal->update_user_meta($user_id, 'day_last_update',$user_id);				
				$this->session->set_flashdata('msg', 'Task added successfully');
				redirect( base_url('Interact/property_details/'.urlencode(base64_encode($post_id)).'/'.urlencode(base64_encode($user_id)). '/'.urlencode(base64_encode($action_type))));
        }	
	////=== image auto resize function======//
	function image_auto_resize($file_name){	
		$config['image_library'] = 'gd2';  
		$config['source_image'] = './assets/img/profiles/'.$file_name;  
        $config['create_thumb'] = FALSE;  
        $config['maintain_ratio'] = FALSE;  
        $config['quality'] = '60%';  
        $config['width'] = 250;  
        $config['height'] = 250;  
        $config['new_image'] = './assets/img/profiles/'.$file_name; 
        $this->load->library('image_lib', $config); 
		$this->image_lib->resize();
		$this->image_lib->clear();
	}
	////===== email configurations setup====///
	public function email_configurations(){
			$template_list='';		
			$user_id = $this->session->userdata('id');
			$role = $this->session->userdata('role');
			$template_id =  $this->uri->segment(3);
			if( ($role=='super_admin') || ($role=='admin')):
				if(!empty($template_id)){
					$template_list = $this->InteractModal->get_email_template($template_id);
				}
				$pagedata['template_list'] = $template_list;
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/email-configurations',$pagedata);
				$this->load->view('dashboard/footer');
			
			else:
				redirect( base_url( 'Interact/login' ));
			endif;
	}
	/*============Add New email Template======================*/
	public function add_email_template(){	
			$user_id = $this->session->userdata('id');
			$role = $this->session->userdata('role');
			$name = $this->input->post('template_name');
			$subject = $this->input->post('template_subject');
			$content = htmlentities($this->input->post('content'));
			if( !empty($user_id) && ($role=='super_admin')):			
			$data=array('name'=>$name,
						'subject'=>$subject,
						'content'=>htmlentities($this->input->post('content')),
						'user_id'=>$user_id,
						);						
			$response = $this->InteractModal->register_user($data, 'templates' );
			redirect(base_url('Interact/user_all_email_template' ));
			else:
				redirect(base_url('Interact/login' ));
			endif;
	}
	/*=====Get Email Templates======*/
	function user_all_email_template(){
		$user_id = $this->session->userdata('id');
		$role = $this->session->userdata('role');		
		if( ($role=='admin')):    
			$template_list = $this->InteractModal->all_active_email_templates();			
			$pagedata['template_list'] = $template_list;
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/all_email_template', $pagedata );
			$this->load->view('dashboard/footer'); 
		elseif(($role=='super_admin')): 
			$template_list = $this->InteractModal->all_email_template();			
			$pagedata['template_list'] = $template_list;
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/all_email_template', $pagedata );
			$this->load->view('dashboard/footer'); 	
		else:
				redirect( base_url( 'Interact/login' ));
		endif;
		
	}
	/*===========listing of all networks according to company id  client dashbord view network===================*/	
	function all_client_networks(){
		$user_id  = $this->session->userdata('id');	
		$role = $this->session->userdata('role');
		if($role == 'client'){			
			$company_id = $this->InteractModal->get_user_meta($user_id, 'company_id');
			$network__list = $this->InteractModal->all_client_networks($company_id);
			$approve_network_list = $this->InteractModal->get_approve_netword_by_clint_id($user_id);	
		    $own_network__list = $this->InteractModal->all_client_own_networks($user_id);		
			/*=====all network merge=====*/
			if(!empty($approve_network_list) && !empty($network__list)){
			       $all_user_list=array_merge($network__list,$approve_network_list,$own_network__list);				   
				}elseif(!empty($own_network__list) && !empty($network__list)){
					$all_user_list=array_merge($network__list,$own_network__list);
				}else{
					$all_user_list=$own_network__list;					 
				}
			$uniqueArray = array();
			if(!empty($all_user_list)):
			  foreach($all_user_list as $subArray){
				if(!in_array($subArray, $uniqueArray)){
				  $uniqueArray[] = $subArray;
				}
			  }
				$pagedata['network__list'] = $uniqueArray;
			 else:
				$pagedata['network__list'] = $network__list;
			endif; 
			/*=====all network merge=====*/
			$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
			$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
			$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
			$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
			$footer_data['user__id'] = $user_id;
			$this->load->view('client/header');
			$this->load->view('client/client_all_network', $pagedata );
			$this->load->view('client/footer',$footer_data); 
			}else{
				redirect( base_url() );
			} 
	}
	/*============all_client_networks_test======================*/
	function all_client_networks_test(){
		$user_id  = $this->session->userdata('id');	
		$role = $this->session->userdata('role');
		if($role == 'client'){
			
			$company_id = $this->InteractModal->get_user_meta($user_id, 'company_id');
			$network__list = $this->InteractModal->all_client_networks($company_id);
			$approve_network_list = $this->InteractModal->get_approve_netword_by_clint_id($user_id);	
		    $own_network__list = $this->InteractModal->all_client_own_networks($user_id);
			/*=====all network merge=====*/
			if(!empty($approve_network_list) && !empty($network__list)){
			       $all_user_list=array_merge($network__list,$approve_network_list,$own_network__list);
				}elseif(!empty($own_network__list) && !empty($network__list)){
					$all_user_list=array_merge($network__list,$own_network__list);					
			}
			$uniqueArray = array();
			if(!empty($all_user_list)):
			  foreach($all_user_list as $subArray){
				if(!in_array($subArray, $uniqueArray)){
				  $uniqueArray[] = $subArray;
				}
			  }
				$pagedata['network__list'] = $uniqueArray;
			 else:
				$pagedata['network__list'] = $network__list;
			endif;
			/*=====all network merge=====*/
			$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_buy' );
			$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $user_id, 'for_sale' );
			$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
			$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
			$footer_data['user__id'] = $user_id;
			$this->load->view('client/header');
			$this->load->view('client/client_all_network', $pagedata );
			$this->load->view('client/footer',$footer_data); 
			}else{
				redirect( base_url() );
			} 
	}
	/*============update_showing_details======================*/
	function update_showing_details(){
		$id=$this->uri->segment(3);
		$schedule_date = $this->input->post('schedule_date');
		$task_id = $this->input->post('task_id');
		$time = $this->input->post('time');
		$buyer_name = $this->input->post('buyer_name');
		$__details = array('user_name' => $buyer_name,
			);
		$final_date = $schedule_date.' '. $time. ':00';
		$details = serialize($__details);
		$data = array(
			'schedule_date'=>$final_date,
			'date' => date('Y-m-d h:i:s'),
			'details' => $details,	
		); 
		$response = $this->InteractModal->update_task_details( $data,$task_id );
		if(!empty($response)){
			//redirect( base_url( 'Interact/showing_log/MzM%3D'));
		}		
				
	}
	/*===========Add New email Template=======================*/
	public function save_email_template(){	
			$user_id = $this->session->userdata('id');
			$role = $this->session->userdata('role');
			$id = $this->input->post('template_id');
			$name = $this->input->post('template_name');
			$subject = $this->input->post('template_subject');
			$attachment = $this->input->post('attachment');
			$content = htmlentities($this->input->post('content'));
			if( !empty($id) && ($role=='admin')):
		$this->InteractModal->add_user_meta( $user_id, 'subject_template_id_'.$id , $subject );
		if(!empty($attachment)){
			$this->InteractModal->add_user_meta( $user_id, 'attach_template_id_'.$id , $attachment );
		}
		$response = $this->InteractModal->add_user_meta( $user_id, 'content_template_id_'.$id,$content );
				if(!empty($response)):
					$this->session->set_flashdata('msg', 'Update successfully');
				else:
					$this->session->set_flashdata('msg', 'Template Update Error! Try Again');
				endif;
				redirect( base_url( 'Interact/user_all_email_template'));				
			else:
				redirect( base_url( 'Interact/login' ));
			endif;
	}
	/*=============update user template status change=====================*/
	function update_template_statuss(){ 
		$user_id = $this->input->post('user__id');
		$id = $this->input->post('temp_id');
		 $status = $this->input->post('status');
		 echo $this->InteractModal->add_user_meta( $user_id, 'status_template_id_'.$id , $status);		
	}
	/*===========for Test only=======================*/
	function update_template_status(){ 
		$role = $this->session->userdata('role');
		$user_id = $this->input->post('user__id');
		$id = $this->input->post('temp_id');
		$status = $this->input->post('status');
		if(!empty($role) &&($role=="super_admin") ){
			$data=array('status'=>$status);
			echo $this->InteractModal->update_email_template($data, $id); 		
		}else{
			echo $this->InteractModal->add_user_meta( $user_id, 'status_template_id_'.$id , $status);	
		}
			
	}
	/*=============corn job update daily email=====================*/
	function daily_update_property_email(){
		$meta_post = $this->InteractModal->get_update_post_meta();
		if(!empty($meta_post)){
			$data = array();
			foreach($meta_post as $meta_details){
				$user_id = $this->InteractModal->get_post_meta($meta_details['post_id'],'initiator_id');
				$data[$user_id][]= $meta_details['meta_key'];
			}
		}		
		$get_data=$this->InteractModal->get_today_data();
		$id=2;
		if(!empty($get_data)){
			$result = array();
			foreach ($get_data as $element) {
				$result[$element['user_id']][]= $element['task_type'];
			}
			if(!empty($data) && (!empty($result))){
				$results = array_merge($data,$result);
			}
			$user_id= '';
			if(!empty($results)){
				foreach($result as $user_id=>$value):
					$update_details='';
					$value = array_unique($value);
					foreach($value as $values):						
						$update_details=$update_details.'Today '.ucwords($values).' Successfully.<br>';
					endforeach;
					if(!empty($user_id)){
						$where = array( 'id' =>$user_id);
						$user_data=$this->InteractModal->single_field_value('users',$where);
						echo $email= $user_data[0]['user_email'];
						echo "<br>";
						$company_id = $this->InteractModal->get_user_meta( $user_id, 'company_id');
						///get auto email content data 
						$status_template = $this->InteractModal->get_user_meta( $company_id, 'status_template_id_'.$id );
						if(($status_template ==1)){
							$subject = $this->InteractModal->get_user_meta( $company_id, 'subject_template_id_'.$id );
							$content = $this->InteractModal->get_user_meta( $company_id, 'content_template_id_'.$id );
							$user_name = $this->InteractModal->get_user_meta( $user_id, 'concerned_person');
							$phone_number = $this->InteractModal->get_user_meta( $user_id, 'phone_number');
							$logo_content = '<img src="'.base_url().'/assets/img/profiles/logo_(2).png" height="auto" width="100" alt="Logo">'; 						
							$content=nl2br($content);
							$content = str_replace("_NAME_",$user_name,$content);
							$content = str_replace("_PHONE_",$phone_number,$content);
							$content = str_replace("_EMAIL_",$email,$content);
							$content = str_replace("_LOGO_",$logo_content,$content);
							$content = str_replace("_DATE_",date('d-F-Y'),$content);
							$content = $content.'<br>'.$update_details;
						}else{
							$template_list = $this->InteractModal->get_email_template($id);
							foreach( $template_list as $template_lists ):
								$subject = $template_lists['subject']; 
								$content = $update_details; 
							endforeach;				
						}				
						///get auto email content data 				
						$email_data = array('email' => $email,
							'content' => $content,
							'subject' => $subject
						);
						$this->send_email($email_data);
					} 
				endforeach;
			}
		}
	}
/*===========Closing Reminder=======================*/
	function closing_remainder(){		
		$company_id=$email=$attach=""; 		
		$id=3;
		$where = array('meta_key' =>"closing_date");		
		$all_update=$this->InteractModal->single_field_value('post_meta',$where);		
		$morning_date= date('Y-m-d');
		foreach($all_update as $user_list){
			$closing_date= $user_list['meta_value'];
			$post_id= $user_list['post_id'];
			$user_id = $this->InteractModal->get_post_meta( $post_id, 'initiator_id');			
			$today= date('Y-m-d', strtotime(' + 5 days'));			
			if(strtotime(date('Y-m-d', strtotime($closing_date)))== strtotime($today)){
				if(!empty($user_id)){
					$where = array( 'id' =>$user_id);
					$user_data=$this->InteractModal->single_field_value('users',$where);
					$email= $user_data[0]['user_email'];
					$company_id = $this->InteractModal->get_user_meta( $user_id, 'company_id');
					///get auto email content data 
					$status_template = $this->InteractModal->get_user_meta( $company_id, 'status_template_id_'.$id );
					if(($status_template ==1)){
						$subject = $this->InteractModal->get_user_meta( $company_id, 'subject_template_id_'.$id );
						$content = $this->InteractModal->get_user_meta( $company_id, 'content_template_id_'.$id );
						$attach = $this->InteractModal->get_user_meta( $company_id, 'attach_template_id_'.$id );
						$user_name = $this->InteractModal->get_user_meta( $user_id, 'concerned_person');
						$phone_number = $this->InteractModal->get_user_meta( $user_id, 'phone_number');
						$logo_content = '<img src="'.base_url().'/assets/img/profiles/logo_(2).png" height="auto" width="100" alt="Logo">';				
						$content=nl2br($content);
						$content = str_replace("_NAME_",$user_name,$content);
						$content = str_replace("_PHONE_",$phone_number,$content);
						$content = str_replace("_EMAIL_",$email,$content);
						$content = str_replace("_LOGO_",$logo_content,$content);
						$content = str_replace("_DATE_",date('d-F-Y'),$content);
					}else{ 
						$template_list = $this->InteractModal->get_email_template($id);
						foreach( $template_list as $template_lists ):
							$subject = $template_lists['subject']; 
							$content = $template_lists['content']; 
						endforeach;				
					}			
					///get auto email content data 				
					$email_data = array('email' => $email,
						'content' => $content,
						'subject' => $subject,
						'attach' => $attach,
					);	
					if($this->send_email_attch($email_data)){
						echo " 7 day mail send";
					} 
				}
			}else if(strtotime(date('Y-m-d', strtotime($closing_date)))== strtotime($morning_date)){
				/////========Morning of Closing mail========///
				if(!empty($user_id)){
					$id=4;
					$where = array( 'id' =>$user_id);
					$user_data=$this->InteractModal->single_field_value('users',$where);
					$email= $user_data[0]['user_email'];					
					$company_id = $this->InteractModal->get_user_meta( $user_id, 'company_id');
					///get auto email content data 
					$status_template = $this->InteractModal->get_user_meta( $company_id, 'status_template_id_'.$id );
					if(($status_template ==1)){
						$subject = $this->InteractModal->get_user_meta( $company_id, 'subject_template_id_'.$id );
						$content = $this->InteractModal->get_user_meta( $company_id, 'content_template_id_'.$id );
						$user_name = $this->InteractModal->get_user_meta( $user_id, 'concerned_person');
						$phone_number = $this->InteractModal->get_user_meta( $user_id, 'phone_number');	
						$logo_content = '<img src="'.base_url().'/assets/img/profiles/logo_(2).png" height="auto" width="100" alt="Logo">';					
						$content=nl2br($content);
						$content = str_replace("_NAME_",$user_name,$content);
						$content = str_replace("_PHONE_",$phone_number,$content);
						$content = str_replace("_EMAIL_",$email,$content);
						$content = str_replace("_LOGO_",$logo_content,$content);
						$content = str_replace("_DATE_",date('d-F-Y'),$content);
					}else{
						$template_list = $this->InteractModal->get_email_template($id);
						foreach( $template_list as $template_lists ):
							$subject = $template_lists['subject']; 
							$content = $template_lists['content']; 
						endforeach;				
					}			
					///get auto email content data 				
					$email_data = array('email' => $email,
						'content' => $content,
						'subject' => $subject
					);	
					if($this->send_email($email_data)){
						echo "morning mail";
					} 
				}				
				/////========Morning of Closing end mail========///				
			}
		}
	} 
	/*============send_email_attch======================*/			
		function send_email_attch( $data ){
			$email = $data['email'];
			$subject = $data['subject'];
			$msg = $data['content'];
			$attach = $data['attach'];
			$config = array(
				'smtp_user' => 'info@interactre.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",
				
			);
			$this->load->library('email', $config);
			$this->email->from('info@interactacm.com', 'InteractRE');
			$this->email->set_header('InteractRE', 'Interact Notifications');
			$this->email->to( $email );
			$this->email->subject( $subject );
			$this->email->message( $msg ); 			
			if(!empty($attach)){
			 $this->email->attach($attach);
			}
			$response = $this->email->send();
			return $response;
		
		}
/*===========Webhook_Subscription=======================*/
    function Webhook_Subscription(){
		$body = file_get_contents('php://input');	
		 $config = array(
				'smtp_user' => 'info@interactre.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",
				
			);
			$subject = 'Webhook_Subscription';			
			$this->load->library('email', $config);
			$this->email->from('info@interactacm.com', 'InteractRE');
			$this->email->set_header('InteractRE', 'Interact Notifications');
			$this->email->to('midriff.dev8@gmail.com');
			$this->email->subject( $subject );
			$this->email->message( $body ); 
			$email_response = $this->email->send();	
			echo "send";		
	}
  
  //=============cron job mail function=============//  
  function request_feedback(){
		$response = $this->InteractModal->get_task();
		if(!empty($response)){
			foreach($response as $response_data){
				$task_id = $response_data['task_id'];					
				$schedule_date = $response_data['schedule_date'];
				$listingdate = date_create($schedule_date);
				$time= date_format($listingdate,"l d - M, Y h:i A");
				$_details = $response_data['details'];
				$details = unserialize( $_details );
				$username = $details['user_name'];
				$contact = $details['contact_number'];
				$email_id = $details['showing_email'];
				$upcoming_all_showings = $this->InteractModal->get_fedback_links( $task_id );
			$config = array(
				'smtp_user' => 'info@interactre.com',
				'mailtype'  => 'html',
				'charset'  => 'utf-8',
				'starttls'  => true,
				'newline'   => "\r\n",
				
			);
				if(!empty($upcoming_all_showings)){
				foreach($upcoming_all_showings as $upcoming_showing){
					$feedback_link= $upcoming_showing['link'];
					$address= $upcoming_showing['property_address'];
					$broker_id= $upcoming_showing['broker_id'];
					$response = $this->InteractModal->signature_user_email($broker_id);
					$email_broker = $response[0]['user_email'];
					$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person'); 
				
			$subject = 'Please provide feedback for our recent showing';
			$msg = 'Hi,<br /><br />We would really appreciate your feedback, please tell us what you thought about your recent showing at "'. $address .'" on "'. $time .'". <br /><a href="'. $feedback_link .'">Please click here to submit your feedback </a> <br /><br /> Thank you,<br /> <br /> '. $concerned_person .' <br /> '. $contact .' <br /> '. $email_broker .'';
			$this->load->library('email', $config);
			$this->email->from('info@interactacm.com', 'InteractRE');
			$this->email->set_header('InteractACM', 'InteractACM Notifications');
			$this->email->to( $email_id );
			$this->email->subject( $subject );
			$this->email->message( $msg ); 
		
			if( $this->email->send() ){
				$data= array(
			'request_feedback' => 0,
			);
			$response = $this->InteractModal->update_task_details($data,$task_id);
			}else{
			echo'Unable to send feedback link at this time. Please try again';
		} 
			}}
			}
		}
  }	
  /*===============create subcription  stripe===================*/
  function stripe_payment(){
	?>
	 <!-- Row Starts -->
	<div class="row">
		<div class="col-sm-12">
			<div class=" pricing-table-outer" >
			<div class="card-block" style="padding-top: 0">
					<div class="cd-pricing-container">
						<div class="row cd-pricing-list cd-bounce-invert pricing-table-b2b">
							<div class="col-md-12 col-sm-12">
								<ul class="cd-pricing-wrapper card_main m-0">
									<li data-type="monthly" class="is-visible">
										<header class="cd-pricing-header">
											<h2>Premium Membership Pro</h2>
	
											<div class="cd-price">
												<span class="cd-currency">$</span>
												<span class="cd-price-month">15</span>
												<span class="cd-duration">month</span>
											</div>
										</header> <!-- .cd-pricing-header -->
	
										<div class="cd-pricing-body">
											<ul class="cd-pricing-features paid-options">
												<li><span>30-Day Free Trial </span> </li>
												<!--<li>Option 1</li>
												<li>Option 2</li>-->
												<li><span>Only $15/mo</span> *After free trial</li>
												<li class="p-b-0"><span>24/7</span>  Support</li>
											</ul>
										</div> <!-- .cd-pricing-body -->
	
										<footer class="cd-pricing-footer">
  										<?php 
          		$stripe = array(
          	"secret_key"      => "sk_test_NSDNftNd6HhWAxCJczahG70h",
          	"publishable_key" => "pk_test_osDQv1znENgYcrVVy2cPggkz"
          	); ?>														
          	<form action="payment_proceed_stripe" method="post">
          	<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
          			data-key="<?php echo $stripe['publishable_key']; ?>"
          			data-image= '<?php echo base_url('assets/img/profiles/intract-logo.png');?>'
          			data-description="Pro Agent"
          			data-amount="1000"
          			data-locale="auto"></script>
          	</form>
			</footer> <!-- .cd-pricing-footer -->
									</li>
								</ul> <!-- .cd-pricing-wrapper card_main -->
							</div>
						</div> <!-- .cd-pricing-list -->
					</div> <!-- .cd-pricing-container -->
				</div> 
			</div>
		</div>
	</div>
	<!-- Row end -->
	<?php 
	}
/*===========Stripe payment function=======================*/
	function payment_proceed_stripe(){
		\Stripe\Stripe::setApiKey("sk_test_NSDNftNd6HhWAxCJczahG70h");	
		/* $product = \Stripe\Product::create(array(
		  "name" => 'Monthly Agent Pro',
		  "type" => "service",
		)); 
		$plan = \Stripe\Plan::create([
			'currency' => 'usd',
			'interval' => 'day',
			'product' => 'prod_Dl9Cxwa53NtQu2',
			'nickname' => 'Pro Plan',
			'amount' => 1000,
			"trial_period_days"=> 1,
		]);*/
			$token  = $_POST['stripeToken'];
			$email  = $_POST['stripeEmail'];
			$company_id = $this->session->userdata('id');
			
		if(!empty($company_id)):
		$atterney_id='';		
			$customer = \Stripe\Customer::create(array(
				'email' => $email,
				'source'  => $token,
				'metadata' => array("company_id" => $company_id),
			));
		$atterney_id = $this->InteractModal->get_user_meta($company_id,'atterney_id');
		if(!empty($atterney_id)){
			$subscription = \Stripe\Subscription::create([
				'customer' =>  $customer->id,
				'items' => [['plan' => 'plan_Dl9MaCYe3qepNP']],
				'metadata' => array("company_id" => $company_id),				
				'coupon' => '100_OFF_14MONTH',						
			]);
		}else{
			$subscription = \Stripe\Subscription::create([
				'customer' =>  $customer->id,
				'items' => [['plan' => 'plan_Dl9MaCYe3qepNP']],
				'metadata' => array("company_id" => $company_id),
				'trial_period_days'=> 1,				
			]);	
		}		
		if(!empty($subscription)){
				$subscription_id = $subscription->id;
				/* $discount =$subscription->discount->coupon->id;
				$discount_end =$subscription->discount->end;
				$discount_start =$subscription->discount->start; */
				$status =$subscription->status;
				$customer_id =$subscription->customer;
				$period_end =$subscription->current_period_end;
				$period_start =$subscription->current_period_start;
				$SubscriptionItem  =$subscription->items->data[0]->id;
				$plan_id  =$subscription->items->data[0]->plan->id;
				$amount  =$subscription->items->data[0]->plan->amount;
				$interval_count  =$subscription->items->data[0]->plan->interval_count;
				$product  =$subscription->items->data[0]->plan->product;
				$amount= $amount/100;
				if($subscription->discount){     
					$discount_coupon =$subscription->discount->coupon->id;
					$discount_end =$subscription->discount->end;
					$discount_start =$subscription->discount->start;
					$discount_start = strftime("%Y-%m-%d", $discount_start);
					$discount_end = strftime("%Y-%m-%d", $discount_end);
				}else{
					$discount_coupon="";
					$discount=$discount_end=$discount_start='';					
				}
				if(!empty($customer_id) && !empty($subscription_id)){
					$update_data = array(
										'customer_id' =>$customer_id,
										'subscription_id' =>$subscription_id,
										'subscription_item_id' =>$SubscriptionItem,
										'transaction_id' =>$SubscriptionItem,
										'company_id' =>$company_id,
										'customer_email' =>$email,
										'amount' =>$amount,
										'status' =>$status,
										'date' =>date('Y-m-d H:i:s'),
										);
		//=======update profile===//
		$candition=array('id'=>$company_id);
		$data=array('subscription'=>1); 
		$this->InteractModal->update_table_data($data,$candition,'users');
		//adding data to session 
        $this->session->set_userdata('subscription',1);
		$update_payment_data=$this->InteractModal->update_payment( $update_data );
		/* echo "<pre>"; print_r($update_data); echo "</pre>"; */
		$this->InteractModal->update_user_meta($company_id,'subscription_id',$subscription_id);
		$this->InteractModal->update_user_meta($company_id,'stripe_customer_id',$customer_id);
		$subcripition_data = array('subscription_id' =>$subscription_id, 
									'subscription_item_id' =>$SubscriptionItem,
									'transaction_id' =>'praent',
									'customer_id' =>$customer_id,
									'company_id' =>$company_id,
									'amount' =>$amount,		
									'period_start' =>strftime("%Y-%m-%d", $period_start),
									'period_end' =>strftime("%Y-%m-%d", $period_end),
									'discount_start' =>$discount_start,
									'discount_end' =>$discount_end,
									'discount_coupon' =>$discount_coupon,
									'status' =>$status,
									'date' =>date('Y-m-d H:i:s'), 
									'all_response' =>$subscription, 
									);
			$update_payment = $this->InteractModal->webhook_order_update($subcripition_data);
			if(!empty($update_payment)){  
				$response = $this->InteractModal->get_order( $update_payment);
				if(!empty($response)){
						$subscription_id = $response[0]['subscription_id'];
						$id = $response[0]['id'];
						$subscription_item_id = $response[0]['subscription_item_id'];
						$company_id = $response[0]['company_id'];
						$period_start = $response[0]['period_start'];
						$period_end = $response[0]['period_end'];
						$status = $response[0]['status'];
						$date = $response[0]['date'];
						$amount = $response[0]['amount'];
						if($status=="trialing"){
							$amount = money_format(" %i", 00);	
							$description="Trial period for Monthly Agent Pro";
						}else{
							$amount = money_format(" %i", $amount);	
							$description="Subscription Monthly Agent Pro";
						}
			$discount_coupon = $response[0]['discount_coupon'];
			$discount_end = $response[0]['discount_end'];
			if(($discount_coupon=="100_OFF_14MONTH") && (date('d-m-Y', strtotime($discount_end))>date('d-m-Y'))){  
			$discound= '<tr>
					<th> 14 Month Free (100% off)</th>
					<td>-$'.$amount.'</td>
				</tr>
				<tr>
				<th>Total</th>
				<td>$0.00</td>
				</tr>';
			}else{ $discound=  '<tr>
				<th>Total</th>
				<td>$'.$amount.'</td>
				</tr>';				
            }
			$concerned_person=$phone=$customer_address 	="";				
			$concerned_person = $this->InteractModal->get_user_meta($company_id,'concerned_person');
			$phone = $this->InteractModal->get_user_meta($company_id,'phone_number');
			$customer_address = $this->InteractModal->get_user_meta($company_id,'customer_address');			
			$subject = 'InteractACM Pro Membership invoice';
			$this->load->library('parser');
			$data['id']= $id;
			$data['date']= $date;
			$data['period_start']= $period_start;
			$data['period_end']= $period_end;
			$data['concerned_person']= $concerned_person;
			$data['email']= $email;
			$data['phone']= $phone;
			$data['customer_address']= $customer_address;
			$data['description']= $description;
			$data['amount']= $amount;
			$data['discound']= $discound;
			$data['status']= $status;
			$content=$this->parser->parse('common/stripe_payment_email', $data, TRUE);  
			$email_data = array('email' => $email,
								'content' => $content,
								'subject' => $subject,
								);
			$this->send_email($email_data);		
			redirect(base_url('Interact/invoice/'.urlencode(base64_encode($update_payment))));
			echo base_url('Interact/invoice/'.urlencode(base64_encode($update_payment)));
					}			
				} 
			} 
		}
	endif;	
	}	
///====Stripe payment function end====///	
	///===== single invoice=====///
	function invoice(){ 
	$order_id=$this->uri->segment(3);  
	$order = base64_decode(urldecode($order_id));
	if(empty($order)){$order=4;}
		$company_id = $this->session->userdata('id');
		$role = $this->session->userdata('role');
		if(!empty($company_id) &&($role=='admin') || ($role == 'super_admin')){	
			if(!empty($order)){  
			$response = $this->InteractModal->get_order( $order);
			$where = array( 'company_id' =>$company_id);
			$user_data=$this->InteractModal->single_field_value('subscription',$where);
			$user_email= $user_data[0]['customer_email'];
			$pagedata['response'] = $response;			
			$pagedata['user_email'] = $user_email;			
			}
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/invoice',$pagedata);
				$this->load->view('dashboard/footer');
		}else{
			redirect(base_url()); 
		}
	}
	//=========update showing log date and time  detail======//	
	function update_showing_detail(){
		$url_value = $this->input->post('url_value');
		$current_url = $this->input->post('current_url');
		$field_name = $this->input->post('field_name');
		$task_id = $this->input->post('task_id');
		$date = $this->input->post('date');
		$showing_time = $this->input->post('time');
		$final_date = $date.' '. $showing_time. ':00';
		$data = array(
			'schedule_date'=>$final_date,
			'date' => date('Y-m-d h:i:s'),
			);
		$response = $this->InteractModal->update_task_details( $data,$task_id );
			if(!empty($response)){
				$array = array(
				'status'=>'reschedule',
			);
			$query = $this->InteractModal->update_task_details($array,$task_id );
			redirect($current_url);
			/* if($field_name == 'showing_log'){
			redirect( base_url( 'Interact/dashboard/'));}else{
				redirect( base_url( 'Interact/all_showing_log/'));
				} */
			} 
	}
/*==================================*/
	//=========get Agent details for showing log on admin dashboard======//	
	function get_agent_date(){
		$field_id = $this->input->post('field_id');
		$contact_number = $this->input->post('contact_number');
		$agent_name = $this->input->post('agent_name');
		$email_id = $this->input->post('email_id');
		$data['contact_number']= $contact_number;
		$data['agent_name']= $agent_name;
		$data['email_id']= $email_id;
		$this->load->view('common/agent_details_content',$data);  
		}
/*==================================*/		
	//=========update showing log status =====//	
	function update_log_status(){		
		$field_name = $this->input->post('field_name');
		$url_value = $this->input->post('url_value');
		$current_url = $this->input->post('current_url');
		$status_update = $this->input->post('status_update');
		$status_task_id = $this->input->post('status_task_id');
		$agent_name = $this->input->post('agent_name');
		$contact_number = $this->input->post('contact_number');	
		//$email_id = $this->input->post('email_id');	
		$link = $this->input->post('link');
		$date_value = $this->input->post('date_value');
		$time_value = $this->input->post('time_value');
		$property_address = $this->input->post('property_address');
		$broker_id = $this->input->post('broker_id');	
		$response = $this->InteractModal->signature_user_email($broker_id);
		$email_broker = $response[0]['user_email'];
		$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person');
		$phone = $this->InteractModal->get_user_meta($broker_id,'phone_number');
		$data = array(
			'status'=>$status_update,
			);
		$query = $this->InteractModal->update_task_details($data,$status_task_id );
		if(!empty($query)){
			$query = $this->InteractModal->get_showing_log($status_task_id );
			if(!empty($query)){
				foreach($query as $queries){
					$status = $queries['status'];
					if($status == 'complete'){
						$email_content = 'Hi, Please click on this link to provide the feedback for the showing, <br /> <a href="'. $link .'">Click here</a>'; 
						$email = 'midriff.dev9@gmail.com';
						$subject = 'Please provide feedback for our recent showing';
						$msg = 'Hi,<br /><br />We would really appreciate your feedback, please tell us what you thought about your recent showing at "'. $property_address .'" on "'. $time_value .'". <br /><a href="'. $link .'">Please click here to submit your feedback </a> <br /><br /> Thank you,<br /> <br /> '. $concerned_person .' <br /> '. $phone .' <br /> '. $email_broker .'';
						$config = array(
						'smtp_user' => 'info@interactre.com',
						'mailtype'  => 'html',
						'charset'  => 'utf-8',
						'starttls'  => true,
						'newline'   => "\r\n",
						);
						$this->load->library('email', $config);
						$this->email->from('info@interactacm.com', 'InteractRE');
						$this->email->set_header('InteractRE', 'Interact Notifications');
						$this->email->to( $email );
						$this->email->subject( $subject );
						$this->email->message( $msg ); 
						$response = $this->email->send();					
						if( $response ){
							redirect( $current_url);
							/* if($field_name == 'showing_log'){
							redirect( base_url( 'Interact/dashboard/'));}else{
									redirect( base_url( 'Interact/all_showing_log/'));
							} */
						}else{
							echo'Unable to send feedback link at this time. Please try again';
						}
					}
					elseif($status  == 'cancelled'){
						$data = array(
						'status'=>$status_update,
						);
					$query = $this->InteractModal->update_task_details($data,$status_task_id );
						if(!empty($query)){
							redirect( $current_url);
							/* if($field_name == 'showing_log'){
								redirect( base_url( 'Interact/dashboard/'));}else{
								redirect( base_url( 'Interact/all_showing_log/'));
							} */
						} 
					}
				}
			}
			redirect( $current_url);
			/* if($field_name == 'showing_log'){
				redirect( base_url( 'Interact/dashboard/'));}else{
					redirect( base_url( 'Interact/all_showing_log/'));
				} */
			}  
		}	
/*==================================*/
	//========get all showing logs=======//	
	function all_showing_log(){
		$user_id = $this->session->userdata('id');
		if($user_id):
			$pagedata['all_showing_details'] = $this->InteractModal->get_all_showing_logs($user_id);
			$this->load->view('dashboard/header');
			$this->load->view('dashboard/all_showing_logs',$pagedata);
			$this->load->view('dashboard/footer');
		else:
			redirect( base_url());
		endif;
		
	}
/*==================================*/
	//========get feedback data==========//	
	function get_feedback_data(){
		$task_id = $this->input->post('field_id');
		$query = $this->InteractModal->feedback_status($task_id );
		if(!empty($query)){
			foreach($query as $queries){
				$price_opinion = $queries['price_opinion'];
				$interest_opinion = $queries['interest_opinion'];
				$property_quality = $queries['property_quality'];
				$showing_id = $queries['showing_id'];
				$details = $queries['details'];
				$feedback_detail = @unserialize( $details );
				$agent_name=$feedback_detail['user_name'];
			}
			$data['agent_name']= $agent_name;
			$data['price_opinion']= $price_opinion;
			$data['interest_opinion']= $interest_opinion;
			$data['property_quality']= $property_quality;
		    $this->load->view('common/feedback_content',$data); 
		}
	}
/*===============broker registration===================*/
	function registration(){ 
		$this->load->view('site/simple-header');
		$this->load->view('broker-registration');
		$this->load->view('site/simple-footer'); 
	}
/*=============pro agent plan create=====================*/
	function pro_agent(){
		$this->load->view('site/simple-header');
		$this->load->view('dashboard/pro-agent');
		$this->load->view('site/simple-footer'); 
	}
/*=============webhook update subcripition=====================*/	
	function update_subcripition_webhook(){
		$company_id= '';
		$body = file_get_contents('php://input');
		$subscription = json_decode($body);
		$subscription_id = $subscription->data->object->id;
		$period_start = $subscription->data->object->current_period_start;
		$period_end = $subscription->data->object->current_period_end;
		$customer_id = $subscription->data->object->customer;
		$status = $subscription->data->object->status;
		$subscription_item_id = $subscription->data->object->items->data[0]->id;
		$amounts = $subscription->data->object->items->data[0]->plan->amount;
		$company_id = $subscription->data->object->metadata->company_id;
		//$company_id = '98';
		$amount = sprintf('%0.2f', $amounts / 100.0);
		if($subscription->data->object->discount){     
			$discount_coupon =$subscription->data->object->discount->coupon->id;
			$discount_end =$subscription->data->object->discount->end;
			$discount_end=strftime("%Y-%m-%d", $discount_end);
			$discount_start =$subscription->data->object->discount->start;
			$discount_start=strftime("%Y-%m-%d", $discount_start);
		}else{
			$discount_coupon=0;
			$discount_end=$discount_start='';					
		}
		$subcripition_data = array(
			'subscription_id' =>$subscription_id,
			'subscription_item_id' =>$subscription_item_id,
			'transaction_id' =>'child', 
			'customer_id' =>$customer_id,
			'company_id' =>$company_id,
			'amount' =>$amount,								
			'period_start' =>strftime("%Y-%m-%d", $period_start),
			'period_end' =>strftime("%Y-%m-%d", $period_end),
			'discount_start' =>$discount_start,
			'discount_end' => $discount_end,
			'discount_coupon' =>$discount_coupon,
			'status' =>$status,
			'date' =>date('Y-m-d H:i:s'), 
			'all_response' =>$body, 
			); 				
		$update_payment = $this->InteractModal->webhook_order_update($subcripition_data);
		if(!empty($update_payment)){  
			$response = $this->InteractModal->get_order($update_payment);
			if(!empty($response)){
				$concerned_person=$phone=$customer_address =$discount_coupon=$discount_start=$discount_end="";
				$subscription_id = $response[0]['subscription_id'];
				$id = $response[0]['id'];
				$subscription_item_id = $response[0]['subscription_item_id'];
				$company_id = $response[0]['company_id'];
				$period_start = $response[0]['period_start'];
				$period_end = $response[0]['period_end'];
				$status = $response[0]['status'];
				$date = $response[0]['date'];
				$amount = $response[0]['amount'];
				$discount_coupon = $response[0]['discount_coupon'];
				$discount_end = $response[0]['discount_end'];
			if(($discount_coupon=="100_OFF_14MONTH") && (date('d-m-Y', strtotime($discount_end))>date('d-m-Y'))){ 
			$discound= '<tr>
					<th> 14 Month Free (100% off)</th>
					<td>-$'.$amount.'</td>
				</tr>
				<tr>
				<th>Total</th>
				<td>$0.00</td>
				</tr>';
			}else{ $discound=  '<tr>
				<th>Total</th>
				<td>$'.$amount.'</td>
				</tr>';
				
                 }				
				//$email = 'midriff.dev8@gmail.com';
				if($status=="trialing"){
					$amount = money_format(" %i", 00);	
					$description="Trial period for Monthly Agent Pro";
				}else{
					$amount = money_format(" %i", $amount);	
					$description="Subscription Monthly Agent Pro";
				}
				if(!empty($company_id)){
				$userdata=$this->InteractModal->single_field_value('users',array('id'=>$company_id));
				if(!empty($userdata)){$email=$userdata[0]['user_email'];}
				}
				$concerned_person = $this->InteractModal->get_user_meta($company_id,'concerned_person');
				$phone = $this->InteractModal->get_user_meta($company_id,'phone_number');
				$customer_address = $this->InteractModal->get_user_meta($company_id,'customer_address');			
				$subject = 'InteractACM Pro Membership invoice';
				$this->load->library('parser');
				$data['id']= $id;
				$data['date']= $date;
				$data['period_start']= $period_start;
				$data['period_end']= $period_end;
				$data['concerned_person']= $concerned_person;
				$data['email']= $email;
				$data['phone']= $phone;
				$data['customer_address']= $customer_address;
				$data['description']= $description;
				$data['amount']= $amount;
				$data['discound']= $discound;
				$data['status']= $status;
				$content=$this->parser->parse('common/stripe_payment_email', $data, TRUE);				
							$email_data = array('email' => $email,
									'content' => $content,
									'subject' => $subject
									);
							$this->send_email($email_data);								
							}			
						}
					} 
/*===========get all orders=======================*/
	public function all_orders(){
		$this->load->view('dashboard/header');
		$pagedata['all_orders'] = $this->InteractModal->get_all_order();
		$this->load->view('dashboard/all_orders',$pagedata);
		$this->load->view('dashboard/footer');		
	}
/*==================================*/
//====get all subscription order===//
	public function get_all_subscription_order(){
		$companyid= $this->uri->segment(3);
		$company_id = base64_decode(urldecode($companyid));
		$pagedata['all_subscription_orders'] = $this->InteractModal->get_all_subscription_order($company_id);
		//$pagedata['all_subscription_orders'] = $this->InteractModal->get_all_order();//
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/all_subscription_orders',$pagedata);
		$this->load->view('dashboard/footer');
	}
/*=============discount coupons=====================*/
	function discount_coupons(){
		\Stripe\Stripe::setApiKey("sk_test_NSDNftNd6HhWAxCJczahG70h");				
		$coupon = \Stripe\Coupon::create([
			"percent_off" => 100,
			"duration" => "repeating",
			"duration_in_months" => 14,
			"id" => "100_OFF_14MONTH"
		]);
		echo "<pre>";
		print_r($coupon);
		echo "</pre>"; 
	}
/*===============get coupen===================*/
	function get_coupen(){ 
		\Stripe\Stripe::setApiKey("sk_test_NSDNftNd6HhWAxCJczahG70h");
		$coupon = \Stripe\Coupon::retrieve("100_OFF_14MONTH");		
		$coupon->duration_in_months = 3;
		$coupon->save(); 
	echo "<pre>";
	print_r($coupon);
	echo "</pre>";
	}
/*=============cancel subscription on super admin dashboard==================*/
	function cancel_subscription(){
		$sub_id = $this->input->post('sub_id');
		if(!empty($sub_id)){
		\Stripe\Stripe::setApiKey("sk_test_NSDNftNd6HhWAxCJczahG70h");
		$subscription = \Stripe\Subscription::retrieve($sub_id);
		  //return $subscription->cancel();
		 if($subscription->cancel()){
			$this->InteractModal->subscription_suspend($sub_id);		
		 }		 
		}else{
			echo "subcripition id not found";
		}
	}
/*==============delete_img====================*/
	function delete_img(){
		$filename="property-images/D3-Tailoring-Small1.jpg";
		unlink($filename);
	}
/*=============///Acion action load tempload=====================*/	
	function load_template(){  
		$company_id = $this->input->post('company_id');
		$type = $this->input->post('task_type');
		$user_id = $this->input->post('user_id');
		$post_id = $this->input->post('post_id');
		if(!empty($company_id) && !empty($type) && !empty($user_id) && !empty($post_id) ){
		$data= $this->InteractModal->load_template($company_id,$type,$user_id,$post_id);
			
		?>		
		 <table class="table table-striped">
		 <?php if(!empty($data)){
			 ?>
          	<tr>
          		<th>#/Name</th>
          		<th>Action</th>
          	</tr>
		 <?php } else{
			 echo "<h3> No Data Found</h3>";
		 }?> 
          </table>
		  <?php
		}
	}
/*==============forgot_password====================*/
	function forgot_password(){  
	       $this->load->view('site/simple-header');
			$this->load->view('site/forgot_password');
			$this->load->view('site/simple-footer');
	}
/*===============ForgotPassword===================*/
	 function ForgotPassword(){
       $email = $this->input->post('email');      
       $findemail = $this->InteractModal->ForgotPassword($email);  
	   $match_email= $findemail['user_email'];
	   $id= $findemail['id'];
           if(!empty($match_email)){
			$password = $this->random_password(); 
			$config = Array(
            'protocol'  => 'SMTP',
            'smtp_host' => 'mail.nextgendeveloper.com',
            'smtp_port' => 25,
            'smtp_user' => 'info@nextgendeveloper.com',
            'smtp_pass' => 'Livefor2018@#',
            'mailtype'  => 'html',
            'starttls'  => true,
            'newline'   => "\r\n"
        );
        $this->load->library('email', $config);		
		$new_password=md5($password);
		$response = $this->InteractModal->Update_Password($id,$new_password); 
		 if(!empty($response)){
			        $this->email->from('info@nextgendeveloper.com', 'intract');
					$this->email->to($email);
					$this->email->subject('password changed');
					$this->email->message('password update successfully and your password is:  '.$password.'');
					$this->email->send();
				    $this->session->set_flashdata('msg', 'your password updated successfully please check your mail');
					redirect( base_url('/Interact/forgot_password'));
				}else{
					$this->session->set_flashdata('msg', 'your password not updated');
					redirect( base_url('/Interact/forgot_password'));
				}    
		}
		else{
			$this->session->set_flashdata('msg', 'Email Not Match');
			redirect( base_url('/Interact/forgot_password') );
		}   
	}
/*===========random_password=================*/
    function random_password(){
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$password = array();
		$alpha_length = strlen($alphabet) - 1;
		for ($i = 0; $i < 8; $i++){
			$n = rand(0, $alpha_length);
			$password[] = $alphabet[$n];
		}
		return implode($password);
	}
/*==============send_re_feedback_link====================*/
	function send_re_feedback_link(){
		$email_id = $this->input->post('email_id');
		$feedback_link = $this->input->post('feedback_link');
		$address = $this->input->post('address');
		$time = $this->input->post('time');
		$broker_id = $this->input->post('broker_id');
		$response = $this->InteractModal->signature_user_email($broker_id);
		$email_broker = $response[0]['user_email'];
		$concerned_person = $this->InteractModal->get_user_meta($broker_id,'concerned_person');
		$phone = $this->InteractModal->get_user_meta($broker_id,'phone_number');
		$email_content = 'Hi, Please click on this link to provide the feedback for the showing, <br /> <a href="'. $feedback_link .'">Click here</a>'; 		
		$email = $email_id;
		$subject = 'Please provide feedback for our recent showing';
		$msg = 'Hi,<br /><br />We would really appreciate your feedback, please tell us what you thought about your recent showing at "'. $address .'" on "'. $time .'". <br /><a href="'. $feedback_link .'">Please click here to submit your feedback </a> <br /><br /> Thank you,<br /> <br /> '. $concerned_person .' <br /> '. $phone .' <br /> '. $email_broker .'';		
		$config = array(
			'smtp_user' => 'info@interactre.com',
			'mailtype'  => 'html',
			'charset'  => 'utf-8',
			'starttls'  => true,			
		);
		$this->load->library('email', $config);			
		$this->email->set_header('InteractRE', 'Interact Notifications');
		$this->email->to( $email );
		$this->email->from('info@interactacm.com', 'InteractRE');
		$this->email->subject( $subject );
		$this->email->message( $msg ); 
		$response = $this->email->send();					
		if( $response ){
				echo'Feedback Link sent';
		}else{
				echo'Unable to send feedback link at this time. Please try again';
		}
	}
/*==========delete selected network===================*/	
	function remove_network(){
		$post_network = $this->input->post('post_network');  
		 $network_id = $this->input->post('network_id');  
		 $post_type = $this->input->post('post_type');  
		 $user_id = $this->input->post('user_id');  
		 $post_id = $this->input->post('post_id'); 
		 if($network_id){
			$this->InteractModal->remove_network_post_meta($post_network,$post_id);
			$response_user = $this->InteractModal->remove_network_user_meta($network_id); 
			$response_user = $this->InteractModal->remove_network_user($network_id);
			echo "Remove Network Successfully";				
		 }
	}
/*===========Get And Save data select template data=======================*/
		function select_templete(){
			 $company_id = $this->session->userdata('id');			
			 $temp_id = $this->input->post('temp_id');
			 $task_type=$this->input->post('task_type');
			 $user_id=$this->input->post('user_id');
			 $post_id=$this->input->post('post_id');
			 $_priority = 0;
			$template_data = $this->InteractModal->select_templete($temp_id);
			if(!empty($template_data)):
					$detail_ = $template_data[0]->details;
					$details = json_decode($detail_);
					  $count = count($details); 
				foreach($details as $old_temp){
				$data = array( 'post_id' => $post_id,
						  'user_id' => $user_id,
						  'schedule_date' => $old_temp->schedule_date,
						  'details' => $old_temp->details,
						  'date' => date('Y-m-d h:i:s'),
						  'task_type' => $task_type,
						  'company_id' => $company_id,
						  'priority' => $old_temp->priority
						  );					  
				$response = $this->InteractModal->save_new_task_db( $data );
			}
			///======Send mail for client start======//// 
			if($task_type == 'milestone'){
				$user_emails = $this->InteractModal->get_user_data($user_id);
				$user_email = $user_emails[0]['user_email']; 
				$data = array('email' => $user_email,
									'content' => 'New milestones added successfully.<a href="'.base_url('Interact/').'">Please click here to login</a> ',
									'subject' => 'New milestone added',
									);
				$this->send_email($data);

			}
			if($task_type == 'action_item'){
				$user_emails = $this->InteractModal->get_user_data($user_id);
				$user_email = $user_emails[0]['user_email']; 
				$data = array('email' => $user_email,
									'content' => 'New action item added successfully. <a href="'.base_url('Interact/').'">Please click here to login</a> ',
									'subject' => 'New action item added!',
									);
				$this->send_email($data);
			}
			///======Send mail for client end======//// 
				if( array_key_exists('id', $response ) && isset($response['id'])):
					if($task_type == 'action_item'){
						$this->session->set_flashdata('msg', 'Action Item Added');
					}
					else{
						$this->session->set_flashdata('msg',ucwords($task_type).' Added');
					}
					$onesignal_id = $this->InteractModal->get_user_meta( $user_id, 'one_signal_id' );
					if(!empty( $onesignal_id )):
						$notification_data = 'Hi, New Task is Assigned to your listing on InteractACM';
						$this->send_action_notification( $notification_data, $onesignal_id );
					endif;	
				else:
					$msg = $response['error'];
					$this->session->set_flashdata('msg', $msg );
					
				endif;			
			endif;
		}
	/*==================================*/	
	/*======== Update single post data============*/
		function update_single_data(){
			$name = $this->input->post('name');
			$value = $this->input->post('value');			
			$pk = $this->input->post('pk');
			return $this->InteractModal->update_post_meta( $pk, $name, $value );
			
			
		} 
		/*======== Update single post data============*/
		/*-------- view more properties---------*/
	function property_showings(){
               $post_id= $this->uri->segment(3);		
			   $p_id = base64_decode( urldecode( $post_id ));
			   $all_showings = $this->InteractModal->count_showing_logs( $p_id );
			   $pagedata['all_showings']= $all_showings ;
			   $this->load->view('dashboard/header');
			   $this->load->view('dashboard/view_more_properties',$pagedata);
			   $this->load->view('dashboard/footer'); 
			
		}
		/* ---- delete templates   ----- */
	function delete_template_by_id(){
			   $template_id =$this->input->post('template_id');
			   return $this->InteractModal->delete_template($template_id);  
	    } 
/*=============edit template by id=====================*/
	function edit_template_by_id(){
				$user_id  = $this->session->userdata('id');	
				$template_id =  $this->uri->segment(3);
				$temp_id = base64_decode( urldecode($template_id)); 
				$pagedata['template_data'] = $this->InteractModal->select_templete($temp_id );
				$this->load->view('dashboard/header');
				$this->load->view('dashboard/edit_template_by_id',$pagedata);
				$this->load->view('dashboard/footer'); 
	    }
/*=============update_action_template=====================*/	
	function update_action_template(){
				$tem_data='';
				$template_id = $this->input->post('tem_id');
				$temp_id = base64_encode( urlencode($template_id)); 
				$details = $this->input->post('details');
				$schedule_date = $this->input->post('schedule_date');
				$temp_name = $this->input->post('template_name');
				$priority = $this->input->post('priority'); 
				$user_id = $this->input->post('user_id'); 
				$company_id = $this->input->post('company_id'); 
				$post_id = $this->input->post('post_id'); 
				$task_type = $this->input->post('task_type'); 
				$count = count( $details );
				if($task_type=='action_item'){
				 for($i = 0; $i < $count; $i++  ){
							$tem_data[] = array( 'post_id' => $post_id,
							  'user_id' => $user_id,
							  'schedule_date' => '',
							  'details' => $details[$i],
							  'date' => date('Y-m-d h:i:s'),
							  'task_type' => $task_type,
							  'company_id' => $company_id,
							  'priority' => $priority[$i],
							  );
				} }else{
					 for($i = 0; $i < $count; $i++  ){
							$tem_data[] = array( 'post_id' => $post_id,
							  'user_id' => $user_id,
							  'schedule_date' => $schedule_date[$i],
							  'details' => $details[$i],
							  'date' => date('Y-m-d h:i:s'),
							  'task_type' => $task_type,
							  'company_id' => $company_id,
							  'priority' => $priority[$i],
							  );
							  
				}
				}
				if(!empty($temp_name) && (!empty($tem_data))){
						$data = array( 
							'name'=>$temp_name,
							 'details' => json_encode($tem_data),
						  );
						
				 $response = $this->InteractModal->update_temp_task_by_id($template_id,$data ); 
				if(!empty($response)){
					$this->session->set_flashdata('msg', 'Template Data Update successfully');
					redirect( base_url('Interact/edit_template_by_id/'.$temp_id));
				}else{
					$this->session->set_flashdata('msg', 'Template Data Not Updated successfully');
					redirect(base_url('Interact/edit_template_by_id/'.$temp_id));
				}	 	  
				}
	     }
/*==============client network====================*/
	 function own_user_list(){ 	      
	        $id = $this->session->userdata('id');
	        $role='client';
			$user_list = $this->InteractModal->get_all_client($role,$id);
			$network_list="";
			if(!empty($user_list)){
				$client_ids = array();
				foreach($user_list as $users_list){
					$client_ids[] = $users_list['id'];
				}
			$client_id = implode(',',$client_ids);
		   $network_list = $this->InteractModal->get_netword_by_clint_id($client_id);
			
		    }
			$pagedata['network_list'] = $network_list;
		 	$this->load->view('dashboard/header');
		    $this->load->view('dashboard/all_own_network',$pagedata );
		    $this->load->view('dashboard/footer');  
			 
		}
/*===============export===================*/
      function export(){
		 $user_id = $this->session->userdata('id');
		 $role= 'client' ; 
		 $user__list = $this->InteractModal->allusers( $user_id,$role);
		 $data=array();
		 foreach($user__list as $user){
			 $id=$user['id'];
			 $email=$user['user_email'];
			 $create_date=$user['create_date'];
			 $name=$this->InteractModal->get_user_meta($id, 'concerned_person' );
			 $phone_number=$this->InteractModal->get_user_meta($id, 'phone_number' );
			 
		$data[]=array(
	            "Name"=>$name,
	            "Email"=>$email,
	            "Contact "=>$phone_number,
	            "Created Date"=> $create_date,
				);
				
		 }
/*===============cleanData===================*/
		 function cleanData(&$str)
			  {
				if($str == 't') $str = 'TRUE';
				if($str == 'f') $str = 'FALSE';
				if(preg_match("/^0/", $str) || preg_match("/^\+?\d{8,}$/", $str) || preg_match("/^\d{4}.\d{1,2}.\d{1,2}/", $str)) {
				  $str = "$str";
				}
				if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
			  }
			  // filename for download
			  $filename = "Active_Client.csv";
			  header("Content-Disposition: attachment; filename=\"$filename\"");
			  header("Content-Type: text/csv");
			  $out = fopen("php://output", 'w');
			  $flag = false;
			  foreach($data as $row) {
				if(!$flag) {
				  // display field/column names as first row
				  fputcsv($out, array_keys($row), ',', '"');
				  $flag = true;
				}
				array_walk($row, __NAMESPACE__ . '\cleanData');
				fputcsv($out, array_values($row), ',', '"');
			  }

			  fclose($out);
			  exit; 
			   }
/*==============update education link====================*/
	function update_education_link(){
		$id = $this->session->userdata('id');
		$task_type = $this->input->post('_for');
		$task_id = $this->input->post('task_id');
		$previous_path_doc = $this->input->post('path');
	    $link_title = $this->input->post('link_title');
		$education_type = $this->input->post('education_type');
		$document_link = $this->input->post('document_link');	
		    $_deatils = array('education_type'=> $education_type,'link_title'=> $link_title,'document_link' => $document_link);
			$serialized_array = serialize($_deatils); 
			$__data = array(
				'details' => $serialized_array,
				'task_type' => $task_type,
				'company_id' => $id,				
				);				
			$response = $this->InteractModal->update_new_task_db($task_id,$__data );
			if(!empty($response)){
				$this->session->set_flashdata('msg', 'Task Update successfully');
			    redirect( base_url('Interact/view_all_education/'));
			}else{
				$this->session->set_flashdata('msg', 'Task Not Updated successfully');
			    redirect( base_url('Interact/view_all_education/'));
			}
	}	
/*==============update education doc====================*/
    function update_education_doc(){
		$id = $this->session->userdata('id');
		$task_type = $this->input->post('_for');
		$task_id = $this->input->post('task_id');		
	 	$previous_path_doc = $this->input->post('path_doc');
	    $link_title = $this->input->post('link_title');
		$education_type = $this->input->post('education_type');
		$userfile = $this->input->post('userfile'); 
	    $pre_doc_name = str_replace(base_url()."/documents/", "", $previous_path_doc);		
        $config['upload_path'] = './documents/';
        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf|docx|doc|csv|xls|xlsx|PDF';
        $config['max_size']  = 150000;
        $this->load->library('upload', $config);		
		if(!empty($_FILES['userfile'])){
			    //unlink($previous_path_doc);
				$this->upload->do_upload();
				$data = $this->upload->data();
				$file_name = $data['file_name']; 
				$_deatils = array('education_type'=> $education_type,'link_title'=> $link_title,'document_file' => $file_name);
				$serialized_array = serialize($_deatils);					
				$__data = array(
						'details' => $serialized_array,
						//'date' => date('Y-m-d h:i:s'),
						'task_type' => $task_type,
						'company_id' => $id,
					);
               $response = $this->InteractModal->update_new_task_db($task_id,$__data );
				 if(!empty($response)){
				$this->session->set_flashdata('msg', 'Task Update successfully');
			    redirect( base_url('Interact/view_all_education/'));
			}else{
				$this->session->set_flashdata('msg', 'Task Not Updated successfully');
			    redirect( base_url('Interact/view_all_education/'));
			} 
		}else{ 
		            $_deatils = array('education_type'=> $education_type,'link_title'=> $link_title,'document_file' => $pre_doc_name);
					$serialized_array = serialize($_deatils);					
					$__data = array(
							'details' => $serialized_array,
							//'date' => date('Y-m-d h:i:s'),
							'task_type' => $task_type,
							'company_id' => $id,
						);	
					$response = $this->InteractModal->update_new_task_db($task_id,$__data );
		} 
  }  
/*=============preview email content=====================*/
	function preview_content(){
		    $content = $this->input->post('text_value');
            $logo_content = '<img src="'.base_url('assets/img/profiles/logo_(2).png').'" height="auto" width="100" alt="Logo">';	
			$login_deatil = 'Your credentials for login in interactACM account is: <br /> username: example@gmail.com <br /> Password: w3eLpu2 <br /> Login Url: '. base_url(). ' ';
			$content=nl2br($content);
			$content = str_replace("_NAME_","Rahul ",$content);
			$content = str_replace("_EMAIL_","example@gmail.com",$content);
			$content = str_replace("_PHONE_","090-414-6831",$content);
			$content = str_replace("_DATE_",date('d-F-Y'),$content);
		    $content = str_replace("_LOGO_",$logo_content,$content);
			$content = str_replace("_LOGIN_DEATILS_",$login_deatil,$content); ?>
			<div class="">
				<?php if(!empty($content)){
				echo $content; }
				else{
					echo "No Result found.";
				}?>
			</div>
	<?php  }
	
    } 
?>