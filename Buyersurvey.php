<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Buyersurvey extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->helper('form');
		$this->load->helper('url');
		$this->load->library('session');
		$this->load->database();
		$this->load->model('InteractModal');
		$this->load->library('pagination');
		$this->load->library('form_validation');
		$user_id = $this->session->userdata('id');		
	} 
		
	function index(){ 
		$session_data = $this->session->userdata();
		if( array_key_exists('role', $session_data ) && isset($session_data['role'])):
			redirect( base_url( 'Interact/dashboard' ));
		else: 
			redirect( base_url( 'Interact/login' ));
		endif;
	} 
	
	function AccessDenail(){ 
		$role = $this->session->userdata('role');
		$id  = $this->session->userdata('id');
		if(!empty( $role ) && $role == 'client'):
			$all_listing_as_buyer = $this->InteractModal->get_all_listing_buyer_seller( $id, 'for_buy' );
			$all_listing_as_seller = $this->InteractModal->get_all_listing_buyer_seller( $id, 'for_sale' );
			$footer_data['all_listing_as_buyer'] = $all_listing_as_buyer;
			$footer_data['all_listing_as_seller'] = $all_listing_as_seller;
			$footer_data['user__id'] = $id;
			$this->load->view('client/header');
			$this->load->view('access-denail');
			$this->load->view('client/footer',$footer_data);
		else:
			redirect( base_url() );
		endif;
	}
	public function survey(){
		$this->load->view('dashboard/header');
		$this->load->view('dashboard/buyer_survey_form');
		$this->load->view('dashboard/footer');
	}
	function register(){ 
	//== register a new user
	$table = 'users';
		$capability = '';
		$type = $this->input->post('type');
		$redirect__url = base_url();
		if( $type == 'client' ):
			$role = 'client';
			$redirect__url = base_url('Interact/add_client');
		else:
			$role = $this->input->post('role');
			//$redirect__url = base_url('Interact/add_user');
			$redirect__url = base_url('Interact/registration');
		endif;
		$attorney_id = $this->input->post('attorney_id');
		$user_name = $this->input->post('personInput');
		$companyName = $this->input->post('nameInput');
		$buyer_survey = $this->input->post('buyer_survey');
		$current_url = $this->input->post('current_url');
		$email = $this->input->post('emailInput');
		$password = $this->input->post('passInput');
		$confirm_password = $this->input->post('confirmPassInput');
		$phoneInput = $this->input->post('phoneInput');
		$screen_name = $this->input->post('screen_name');
		if( $role == 'admin' ):
			$customer_address = $this->input->post('customer_address');
			$street_number = $this->input->post('street_number');
			$route = $this->input->post('route');
			$city = $this->input->post('city');
			$state = $this->input->post('state');
			$postal_code = $this->input->post('postal_code');
			$country = $this->input->post('country');
		endif;
		//$countryInput = $this->input->post('countryInput');
		$check_data = array('user_email' => $email);
		$check_user_availability = $this->InteractModal->check_user_availability( $check_data, $table );
		if(!empty( $check_user_availability )):
			//echo 'User already exist with similar email! ';
			$this->session->set_flashdata('msg', 'User already exist with similar email!');
			if($buyer_survey=='buyer_survey'){
                redirect( $current_url );
            }else{
				redirect( $redirect__url ); 
			}
		else:
            if($buyer_survey=='buyer_survey'){            
				$data = array('user_email' => $email ,
						  'password' => md5( $password ),
						  'role' => $role,
						 'create_date' => date('Y-m-d h:i:s')
				);
            }else{           
				$data = array('user_email' => $email ,
						  'password' => md5( $password ),
						  'role' => $role,
						  'status' => 1,
						  'create_date' => date('Y-m-d h:i:s')
				);           
			}
			$response = $this->InteractModal->register_user($data, $table);
			
			if( array_key_exists('id', $response ) ): // update user meta in database 
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
					$this->InteractModal->add_user_meta( $user_id, 'route' , $route );
					$this->InteractModal->add_user_meta( $user_id, 'city' , $city );
					$this->InteractModal->add_user_meta( $user_id, 'state' , $state );
					$this->InteractModal->add_user_meta( $user_id, 'postal_code' , $postal_code );
					$this->InteractModal->add_user_meta( $user_id, 'country' , $country );					
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
					$logo_content = '<img src="'.base_url('assets/img/profiles/logo_(2).png').'" height="auto" width="100" alt="Logo">'; 
					
					$content=nl2br($content);
					$content = str_replace("_NAME_",$user_name,$content);
					//$content = str_replace("_LOGIN_DEATILS_",$login_deatil,$content);
					$content = str_replace("_DATE_",date('d-F-Y'),$content);
					$content = str_replace("_LOGO_",$logo_content,$content);
					$content = str_replace("_EMAIL_",$email,$content);
					$content = str_replace("_PHONE_",$phoneInput,$content);
					$content=nl2br($content);
					$decode_id = urlencode(base64_encode( $user_id ));
					$content =  base_url('Buyersurvey/serveyForm/'.$decode_id);
					if (strpos($content, '_LOGIN_DETAILS_') !== false) {
						 $content = str_replace("_LOGIN_DETAILS_",$login_deatil,$content);
					}else{
						 $content = $content.'<br>'.$login_deatil;
					}
				}else{
					$template_list = $this->InteractModal->get_email_template(1);
					foreach( $template_list as $template_lists ):
						$subject = $template_lists['subject']; 
						$decode_id = urlencode(base64_encode( $user_id ));
						$content =  base_url('Buyersurvey/serveyForm/'.$decode_id);	
						$content = $login_deatil;
					endforeach;				
				}			
				///get auto email content data 
				$email_data = array('email' => $email,
					'content' => $content,
					'subject' => $subject,
					);
				$this->send_email($email_data); 
			}else{
				$email_content = 'Welcome to interactACM. Your credentials for login in interactACM account is: <br /> username: '. $email .'<br /> Password: '. $password .'<br /> Login Url: '. $login_url. ' ';				
				$email_data = array('email' => $email,
									'content' => $email_content,
									'subject' => 'Welcome to interactACM'
									);
				$this->send_email( $email_data );
								
			}
				$regex = "/^(\(?\d{3}\)?)?[- .]?(\d{3})[- .]?(\d{4})$/"; 
				$mobile = preg_replace($regex, "\\1\\2\\3", $phoneInput); 
				//$this->send_text_message($mobile,$email);		
                if($buyer_survey=='buyer_survey'):
				$this->session->set_flashdata('msg', 'Buyer client registered successfully.login credentials and form link has been sent in registered email.');
                redirect( $current_url );
                endif;				
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
	
	// function for servey form  //
	public function serveyForm(){
		$user= $this->uri->segment(3);
		$user_id = base64_decode( urldecode( $user )); 
		$exist = $this->InteractModal->serveyId_exist($user_id);
		if(!empty($exist)){
			$user_details['user_detail'] = $this->InteractModal->getServeydata($user_id);
			$user_details['servey_data'] = $this->InteractModal->getServeydetail($user_id);
			$this->load->view('site/simple-header'); 
			$this->load->view('dashboard/servey_detail',$user_details);
			$this->load->view('site/simple-footer');
			
		}else{
			$this->load->view('site/simple-header'); 
			$this->load->view('servey-form');
			$this->load->view('site/simple-footer');
		}
		
	}	
	// function for get servey form data  by harpreet // 28-jan-2019//
	public function servey_data(){
		
		$this->form_validation->set_rules('bedroom', 'bedroom', 'required');
		$this->form_validation->set_rules('bathroom', 'bathroom', 'required'); 
		$this->form_validation->set_rules('min_price', 'min_price', 'required'); 
		$this->form_validation->set_rules('max_price', 'max_price', 'required'); 
		if($this->form_validation->run() == FAlSE){
			$this->load->view('site/simple-header'); 
			$this->load->view('servey-form');
			$this->load->view('site/simple-footer');
		}else{
			$bedroom = $this->input->post('bedroom');
			$bathroom = $this->input->post('bathroom');
			$min_price = $this->input->post('min_price');
			$max_price = $this->input->post('max_price');
			$user_id = $this->input->post('user_id');
			$data = array('user_id'=>$user_id,
				'bedroom'=>$bedroom,
				'bathroom'=> $bathroom,
				'min_price' =>$min_price,
				'max_price' =>$max_price
			);
			$servey_id = $this->InteractModal->insert_serveyForm($data); 
			if($servey_id){
				$response = $this->InteractModal->updateUser($user_id);
				$user_details['user_detail'] = $this->InteractModal->getServeydata($user_id);
				$user_details['servey_data'] = $this->InteractModal->getServeydetail($user_id);
				$this->load->view('site/simple-header'); 
				$this->load->view('dashboard/servey_detail',$user_details);
				$this->load->view('site/simple-footer'); 
				
			} 
		}
	}
	public function send_email( $data ){
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
			$response = $this->email->send();
			return $response;
		}
	public function serveyForm_test(){
		$this->load->view('site/simple-header'); 
		$this->load->view('test-servey');
		$this->load->view('site/simple-footer');
	}
	public function serveyForm_feedback(){
		$this->load->view('site/simple-header'); 
		$this->load->view('servey-feedback');
		$this->load->view('site/simple-footer');
	}
}