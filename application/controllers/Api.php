<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct() {

		parent::__construct();
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");

		date_default_timezone_set('Asia/Kuala_Lumpur');
	}

	public function index() {

		$response = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.',
		);

		print_r( json_encode($response) );
	}

	public function comment( $method = '' ) {
		if( $method === 'POST' ) {

			header("Access-Control-Allow-Methods: POST");

			$error = false;

			$author_id = ! empty( $this->input->post('author_id') ) ? $this->input->post('author_id') : $error = true;
			$author_name = ! empty( $this->input->post('author_name') ) ? $this->input->post('author_name') : $error = true;
			$author_avatar = ! empty( $this->input->post('author_avatar') ) ? $this->input->post('author_avatar') : $error = true;
			$description = ! empty( $this->input->post('description') ) ? $this->input->post('description') : $error = true;
			$post_id = ! empty( $this->input->post('post_id') ) ? $this->input->post('post_id') : $error = true;

			if( ! $error ) {

				$input = array(
					'author_id'				=> 	$author_id,
					'author_name'			=> 	$author_name,
					'author_avatar'		=> 	$author_avatar,
					'description'			=> 	$description,
					'post_id'					=> 	$post_id,
					'created_on'			=>	date('Y-m-d H:i:s'),
				);

				$this->db->insert('post_comment', $input);

				$this->update_action_record('comment', 1, $post_id);
			} else {


			}

		} else if( $method === 'GET' ) {

			header("Access-Control-Allow-Methods: GET");

			//		GET specific post.
			//		/GET/id/ ${pid}
			if( ! empty($this->uri->segment(4)) ) {

				$post_id = $this->uri->segment(4);

				$table = 'post_comment';
				$query = 'SELECT * FROM ' . $table . ' WHERE post_id = ' . $post_id . ' ORDER BY id DESC';

				if( ! empty($this->uri->segment(6)) ) {
					$limit = $this->uri->segment(6);
					$query = $query . ' LIMIT ' . $limit;
				}

				if( ! empty($this->uri->segment(8)) ) {
					$offset = $this->uri->segment(8);
					$query = $query . ' OFFSET ' . $offset;
				}

				$result = $this->db->query($query)->result_array();


				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource has been fetched and is transmitted in the message body.',
					'body'	=> $result,
				);

				$response['total_comments'] = $this->db->count_all('post_comment');


				print_r( json_encode($response) );

				// TODO: post_count && last_post
			}
		} else {



		}
	}
	private function update_action_record( $action = '', $method = '', $post_id = '' ) {
		$record = $this->db->select($action . 's')
													 ->where('id', $post_id)
													 ->get('post')
													 ->row($action . 's');

		switch( $method ) {
			case 0:
				if( $record !== 0 ) {
					$new_record = $record - 1;
				}
				break;
			case 1:
				$new_record = $record + 1;
				break;
		}

		$this->db->where('id', $post_id)
						 ->update('post', array($action . 's' => $new_record));
	}
	private function update_post_action( $action = '', $post_id = '', $fb_id = '') {

		if( $action == 'like' ) {
			$is_exist_action = $this->db->where('post_id', $post_id)
																	->where('fb_id', $fb_id)
																	->get('post_action')
																	->num_rows();
			if( $is_exist_action > 0 ) {
				$action_status = $this->get_action_status('like', $post_id, $fb_id);

				if( $action_status == 0 ) {

					$input = array('is_liked' => 1);

					$this->db->where('post_id', $post_id)
									 ->where('fb_id', $fb_id)
									 ->update('post_action', $input);

					$this->update_action_record('like', 1, $post_id);

				} else if( $action_status == 1 ) {
					$input = array('is_liked' => 0);

					$this->db->where('post_id', $post_id)
									 ->where('fb_id', $fb_id)
									 ->update('post_action', $input);

					$this->update_action_record('like', 0, $post_id);
				}

			} else {
				// No record. Create new record and return false.
				$input = array(
					'post_id'		=>	$post_id,
					'fb_id'			=>	$fb_id,
					'is_liked'	=>	1,
				);
				$this->db->insert('post_action', $input);

				$this->update_action_record('like', 1, $post_id);
			}

		} else if( $action == 'comment' ) {
			$comment_count = $this->get_action_count('comment', $post_id, $fb_id);
			$input = array('comment_count' => $comment_count + 1);

			$this->db->where('post_id', $post_id)
							 ->where('fb_id', $fb_id)
							 ->update('post_action', $input);
		} else if( $action == 'share' ) {
			$share_count = $this->get_action_count('share', $post_id, $fb_id);
			$input = array('$share_count' => $share_count + 1);

			$this->db->where('post_id', $post_id)
							 ->where('fb_id', $fb_id)
							 ->update('post_action', $input);
		}

	}

	private function get_action_status( $action = '', $post_id = '', $fb_id = '' ) {
		$result = $this->db->select('is_' . $action . 'd')
									 		 ->where('post_id', $post_id)
									 	   ->where('fb_id', $fb_id)
									 	 	 ->get('post_action')
									 	 	 ->row('is_' . $action . 'd');

		return $result;
	}
	private function get_action_count( $action = '', $post_id = '', $fb_id = '' ) {

		$result = $this->db->select($action . '_count')
									 	 ->where('post_id', $post_id)
									 	 ->where('fb_id', $fb_id)
									 	 ->get('post_action')
									 	 ->row($action . '_count');

		return $result;
	}

	public function action( $method = '', $action = '' ) {

		if( $method === 'POST' ) {

			header("Access-Control-Allow-Methods: POST");

			$error = false;

			$post_id = ! empty( $this->input->post('post_id') ) ? $this->input->post('post_id') : $error = true;
			$fb_id = ! empty( $this->input->post('fb_id') ) ? $this->input->post('fb_id') : $error = true;

			if( ! $error ) {
				switch( $action ) {
					case 'like':
						$this->update_post_action('like', $post_id, $fb_id);

						if( $this->db->affected_rows() > 0 ) {
							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> '',
							);
							print_r( json_encode($response) );
						}
						break;
					case 'comment':
						$error = false;

						$author_name = ! empty( $this->input->post('author_name') ) ? $this->input->post('author_name') : $error = true;
						$author_avatar = ! empty( $this->input->post('author_avatar') ) ? $this->input->post('author_avatar') : $error = true;
						$description = ! empty( $this->input->post('description') ) ? $this->input->post('description') : $error = true;

						if( ! $error ) {
							$input = array(
								'author_id'		=>	$fb_id,
								'author_name'	=>	$author_name,
								'author_avatar'	=>	$author_avatar,
								'description'	=>	$description,
								'post_id'			=>	$post_id,
								'created_on'	=> date('Y-m-d H:i:s'),
							);

							$this->db->insert('post_comment', $input);

							if( $this->db->affected_rows() > 0 ) {
								$this->update_post_action('comment', $post_id, $fb_id);

								$response = array(
									'status'	=> '200',
									'code'		=> 'OK',
									'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
									'body'	=> '',
								);
								print_r( json_encode($response) );
							}
						} else {
							$response = array(
								'status'	=> '400',
								'code'		=> 'InvalidQueryParameterValue',
								'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
							);
							print_r( json_encode($response) );
						}

						break;
					case 'share':
						$this->update_post_action('share', $post_id, $fb_id);

						if( $this->db->affected_rows() > 0 ) {
							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> '',
							);
							print_r( json_encode($response) );
						}
						break;
					default:
						$response = array(
							'status'	=> '400',
							'code'		=> 'InvalidQueryParameterValue',
							'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
						);

						print_r( json_encode($response) );
						break;
				}

			} else {
				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
				);

				print_r( json_encode($response) );
			}

		} else if( $method === 'GET' && $action === 'all' ) {
		 	header("Access-Control-Allow-Methods: GET");

		 	$error = false;
		 	$fb_id = ! empty($this->uri->segment(5)) ? $this->uri->segment(5) : $error = true;

		 	if( ! $error ) {
		 		$liked = $this->db->select('post_id')
		 													->where('fb_id', $fb_id)
		 													->where('is_liked', 1)
		 													->get('post_action')
		 													->result_array();

				$liked_posts = [];
				foreach( $liked as $each ) {
					$liked_posts[] = $each['post_id'];
				}

				$shared = $this->db->select('post_id')
														->where('fb_id', $fb_id)
														->where('is_shared', 1)
														->get('post_action')
														->result_array();

				$shared_posts = [];
				foreach( $shared as $each ) {
					$shared_posts[] = $each['post_id'];
				}

				$body = array(
					'liked'		=>	$liked_posts,
					'shared'	=>	$shared_posts,
				);

				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource has been fetched and is transmitted in the message body.',
					'body'	=> $body,
				);

				print_r( json_encode($response) );
		 	} else {
				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
				);

				print_r( json_encode($response) );
		 	}

		} else {
			$response = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
			);

			print_r( json_encode($response) );
		}
	}

	public function post( $method = '' ) {

		if( $method === 'GET' ) {

			header("Access-Control-Allow-Methods: GET");

			//		GET specific post.
			//		/GET/id/ ${pid}
			if( ! empty($this->input->get('pid')) ) {

				// *****TODO*****

			//		GET all post.
			//		/GET/limit/ ${limit} /offset/ ${offset}
			}	else if( $this->uri->segment(4) === 'total') {

				$result['total_post'] = $this->db->count_all('post');

				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource has been fetched and is transmitted in the message body.',
					'body'	=> $result,
				);



				print_r( json_encode($response) );
			} else {

				$table = 'post';
				$query = 'SELECT * FROM ' . $table;

				if( ! empty($this->uri->segment(5)) ) {
					$limit = $this->uri->segment(5);
					$query = $query . ' LIMIT ' . $limit;
				}

				if( ! empty($this->uri->segment(7)) ) {
					$offset = $this->uri->segment(7);
					$query = $query . ' OFFSET ' . $offset;
				}

				$result = $this->db->query($query)->result_array();


				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource has been fetched and is transmitted in the message body.',
					'body'	=> $result,
				);

				print_r( json_encode($response) );

				// TODO: post_count && last_post
			}

		} else if( $method === 'POST' ) {

			header("Access-Control-Allow-Methods: POST");

			$error = false;

			$author_name = ! empty($this->input->post('author_name')) ? $this->input->post('author_name') : $error = true;
			$author_avatar = ! empty($this->input->post('author_avatar')) ? $this->input->post('author_avatar') : $error = true;
			$description = ! empty($this->input->post('description')) ? $this->input->post('description') : $error = true;
			$source = ! empty($this->input->post('source')) ? $this->input->post('source') : null;
			$quote_id = ! empty($this->input->post('quote_id')) ? $this->input->post('quote_id') : null;
			$user_id = ! empty($this->input->post('user_id')) ? $this->input->post('user_id') : $error = true;

			if( ! $error ) {

				$input = array(
					'author_name'		=>	$author_name,
					'author_avatar'	=>	$author_avatar,
					'description'		=>	$description,
					'source'				=>	$source,
					'quote_id'			=>	$quote_id,
					'is_approved'		=>	1,
					'created_on'		=>	date('Y-m-d H:i:s'),
				);

				$this->db->insert('post', $input);


				$input = array(
					'post_id' => $this->db->insert_id(),
					'user_id' => $user_id,
				);

				$this->db->insert('post_user', $input);

				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
					'body'	=> '',
				);

				$this->update_post_count($user_id);

				print_r( json_encode($response) );
			} else {

				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
				);

				print_r( json_encode($response) );
			}

		} else {

			$response = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
			);

			print_r( json_encode($response) );
		}
	}

	public function user( $method = '', $hash = '', $components = [] ) {

		if( $method === 'GET') {

			header("Access-Control-Allow-Methods: GET");

			$error = false;

			if( $this->uri->segment(4) == 'hash' ){

				$error = false;

				$bfp_hash = ! empty($this->uri->segment(5)) ? $this->uri->segment(5) : $error = true;

				if( ! $error ) {
					$bfp = $this->get_by('bfp_hash', $bfp_hash);

					if( ! empty($bfp) ) {
						$uid_by_bfp = $this->get_uid_by('bfp_hash', $bfp->id)[0]['user_id'];

						$this->clear_post_count($uid_by_bfp);

						$user = $this->get_by('id', $uid_by_bfp, 'user');
					} else {
						$user = [];
					}

					$response = array(
						'status'	=> '200',
						'code'		=> 'OK',
						'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
						'body'	=> $user,
					);

					print_r( json_encode($response) );

				} else {
					$output = array(
						'status'	=> '500',
						'code'		=> 'InternalError',
						'message'	=>	'The server encountered an internal error. Please retry the request.',
						'body'		=>	'',
					);

					print_r( json_encode($output) );
				}

			} else if (	$this->uri->segment(4) == 'fb_connect' ) {

				$error = false;

				$fb_id = ! empty($this->uri->segment(5)) ? $this->uri->segment(5) : $error = true;

				if( ! $error ) {
					$this->update_footprint('fb', array('fb_id' => $fb_id));

					$response = array(
						'status'	=> '200',
						'code'		=> 'OK',
						'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
						'body'	=> '',
					);

					print_r( json_encode($response) );
				} else {
					$output = array(
						'status'	=> '500',
						'code'		=> 'InternalError',
						'message'	=>	'The server encountered an internal error. Please retry the request.',
						'body'		=>	'',
					);

					print_r( json_encode($output) );
				}
			} else {

				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
					'body'		=>	'',
				);

				print_r( json_encode($output) );
			}

		} else if( $method === 'POST' ) {

			header("Access-Control-Allow-Methods: POST");

			$error = false;

			if( $this->uri->segment(4) == 'fb_connect' ){

				$error = false;

				$user_id = ! empty($this->input->post('user_id')) ? $this->input->post('user_id') : $error = true;
				$fb_id = ! empty($this->input->post('fb_id')) ? $this->input->post('fb_id') : $error = true;
				$email = ! empty($this->input->post('email')) ? $this->input->post('email') : $error = true;
				$full_name = ! empty($this->input->post('full_name')) ? $this->input->post('full_name') : $error = true;
				$first_name = ! empty($this->input->post('first_name')) ? $this->input->post('first_name') : $error = true;
				$last_name = ! empty($this->input->post('last_name')) ? $this->input->post('last_name') : $error = true;
				$age = ! empty($this->input->post('age')) ? $this->input->post('age') : $error = true;
				$gender = ! empty($this->input->post('gender')) ? $this->input->post('gender') : $error = true;
				$profile = ! empty($this->input->post('profile')) ? $this->input->post('profile') : $error = true;
				$profile_avatar = ! empty($this->input->post('profile_avatar')) ? $this->input->post('profile_avatar') : $error = true;
				$profile_cover = ! empty($this->input->post('profile_cover')) ? $this->input->post('profile_cover') : $error = true;
				$locale = ! empty($this->input->post('locale')) ? $this->input->post('locale') : $error = true;
				$timezone = ! empty($this->input->post('timezone')) ? $this->input->post('timezone') : $error = true;

				if( ! $error ) {

					if( ! $this->is_exist('fb_id', $fb_id, 'user_fb') ) {

						$input = array(
							'user_id'					=>	$user_id,
							'fb_id'						=>	$fb_id,
							'email'						=>	$email,
							'full_name'				=>	$full_name,
							'first_name'			=>	$first_name,
							'last_name'				=>	$last_name,
							'age'							=>	$age,
							'gender'					=>	$gender,
							'profile'					=>	$profile,
							'profile_avatar'	=>	$profile_avatar,
							'profile_cover'		=>	$profile_cover,
							'locale'					=>	$locale,
							'timezone'				=>	$timezone,
							'visit_count'			=>	1,
							'created_on'			=>	date('Y-m-d H:i:s'),
						);

						$this->db->insert('user_fb', $input);

						if( $this->db->affected_rows() > 0 ) {
							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> '',
							);

							print_r( json_encode($response) );
						}
					} else {
						$this->update_footprint('fb', array('fb_id' => $fb_id));

						$response = array(
							'status'	=> '200',
							'code'		=> 'OK',
							'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
							'body'	=> '',
						);

						print_r( json_encode($response) );

					}

				} else {
					$output = array(
						'status'	=> '500',
						'code'		=> 'InternalError',
						'message'	=>	'The server encountered an internal error. Please retry the request.',
						'body'		=>	'',
					);

					print_r( json_encode($output) );
				}

			} else {

				$error = false;

				$bfp_hash = ! empty($this->input->post('bfp_hash')) ? $this->input->post('bfp_hash') : $error = true;
				$bfp_components = ! empty($this->input->post('bfp_components')) ? json_encode($this->input->post('bfp_components')) : $error = true;

				$ip_address = file_get_contents('https://api.ipify.org/');
				$ip_components = file_get_contents('http://ip-api.com/json');

				if( ! $error ) {

					$exist_ip = $this->is_exist('ip_address', $ip_address);
					$exist_bfp = $this->is_exist('bfp_hash', $bfp_hash);

					// Both IP & BFP exist. Consider visitor as exist user.
					if( $exist_ip && $exist_bfp ) {

						$bfp_id = $this->get_by('bfp_hash', $bfp_hash)->id;
						$ip_id = $this->get_by('ip_address', $ip_address)->id;

						$uid_by_ip = $this->get_uid_by('ip_address', $ip_id);
						$uid_by_bfp = $this->get_uid_by('bfp_hash', $bfp_id);


						// IP address pointed to single user.
						if( count($uid_by_ip) == 1 ) {

							$uid_by_ip = $uid_by_ip[0]['user_id'];
							$uid_by_bfp = $uid_by_bfp[0]['user_id'];

							// Both BFP and IP are NOT pointed to the same user.
							// Consider user changes IP address.
							if( $uid_by_ip !== $uid_by_bfp ) {
								$user = $this->link_user_with($uid_by_bfp, $ip_id, 'ip_address');

							// Both BFP and IP are the same.
							} else {
								$user = $this->get_by('id', $uid_by_bfp, 'user');
							}

							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> $user,
							);

							print_r( json_encode($response) );

						// IP address pointed to multiple user.
						} else {

							$uid_by_bfp = $uid_by_bfp[0]['user_id'];

							$is_identical = false;
							foreach ( $uid_by_ip as $uid ) {
								if( $uid['user_id'] == $uid_by_bfp ) {
									$is_identical = true;
									break;
								}
							}

							if( $is_identical ) {
								$user = $this->get_by('id', $uid_by_bfp, 'user');
							// Consider user changes IP address.
							} else {
								$user = $this->link_user_with($uid_by_bfp, $ip_id, 'ip_address');
							}

							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> $user,
							);

							print_r( json_encode($response) );
						}

						$values = array(
							'uid'			=> isset($uid_by_bfp) ? $uid_by_bfp : $bfp_id,
							'bfp_id'	=> $bfp_id,
							'ip_id'		=> $ip_id,
						);

						$this->update_footprint('all', $values);

					// Either IP or BFP not exist.
					} else if( ! $exist_ip || ! $exist_bfp ) {

						// Both IP & BFP not exist.
						// Register visitor as new user.
						if( ! $exist_ip && ! $exist_bfp ) {

							// Mark new footprints.
							$input = array(
								'ip_address'		=>	$ip_address,
								'components'	=>	$ip_components,
							);

							$ip_id = $this->mark_footprint('ip_address', $input);

							$input = array(
								'bfp_hash'		=>	$bfp_hash,
								'components'	=>	$bfp_components,
							);

							$bfp_id = $this->mark_footprint('bfp_hash', $input);

							// Create user.
							$footprint = array(
								'bfp_id'		=>	$bfp_id,
								'ip_id'	=>	$ip_id,
							);

							$user = $this->user_create('anonymous', $footprint);

							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> $user,
							);

							print_r( json_encode($response) );

						// Only BFP exists.
						// Consider user changes IP address.
						} else if( ! $exist_ip && $exist_bfp ) {

							// Mark new footprints.
							$input = array(
								'ip_address'		=>	$ip_address,
								'components'	=>	$ip_components,
							);

							$ip_id = $this->mark_footprint('ip_address', $input);

							$bfp_id = $this->get_by('bfp_hash', $bfp_hash)->id;
							$uid_by_bfp = $this->get_uid_by('bfp_hash', $bfp_id)[0]['user_id'];

							$user = $this->link_user_with($uid_by_bfp, $ip_id, 'ip_address');

							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> $user,
							);

							print_r( json_encode($response) );

							$values = array(
								'uid'			=> isset($uid_by_bfp) ? $uid_by_bfp : $bfp_id,
								'bfp_id'	=> $bfp_id,
								'ip_id'		=> $ip_id,
							);

							$this->update_footprint('all', $values);

						// Only IP exists.
						// Consider visitor shares IP with others. Register visitor as new user.
						} else {

							// Mark new footprints.
							$input = array(
								'bfp_hash'		=>	$bfp_hash,
								'components'	=>	$bfp_components,
							);
							$bfp_id = $this->mark_footprint('bfp_hash', $input);

							$ip_id = $this->get_by('ip_address', $ip_address)->id;

							// Create user.
							$footprint = array(
								'bfp_id'		=>	$bfp_id,
								'ip_id'	=>	$ip_id,
							);

							$user = $this->user_create('anonymous', $footprint);

							$response = array(
								'status'	=> '200',
								'code'		=> 'OK',
								'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
								'body'	=> $user,
							);

							print_r( json_encode($response) );
						}

					}

				} else {
					$output = array(
						'status'	=> '500',
						'code'		=> 'InternalError',
						'message'	=>	'The server encountered an internal error. Please retry the request.',
						'body'		=>	'',
					);

					print_r( json_encode($output) );
				}

			}

		} else {

			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
				'body'		=>	'',
			);

			print_r( json_encode($output) );
		}
	}

	public function option( $method = '' ) {

		if( $method === 'GET' ) {

			header("Access-Control-Allow-Methods: GET");

			if( ! empty( $this->uri->segment(4) ) ) {

				switch( $this->uri->segment(4) ) {
					case 'mood':
						$moods = $this->get_all('mood');
						$response = array(
							'status'	=> '200',
							'code'		=> 'OK',
							'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
							'body'	=> $moods,
						);
						break;

					case 'avatar':
						$avatars = $this->get_all('avatar');
						$response = array(
							'status'	=> '200',
							'code'		=> 'OK',
							'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
							'body'	=> $avatars,
						);
						break;

					case 'source':
						$sources = $this->get_all('source');
						$response = array(
							'status'	=> '200',
							'code'		=> 'OK',
							'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
							'body'	=> $sources,
						);
						break;

					default:
						$response = array(
							'status'	=> '400',
							'code'		=> 'InvalidQueryParameterValue',
							'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
							'body'		=>	'',
						);
						break;
				}

				print_r( json_encode($response) );

			} else {

				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
					'body'		=>	'',
				);

				print_r( json_encode($response) );
			}

		} else {
			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
				'body'		=>	'',
			);

			print_r( json_encode($output) );
		}

	}

	// CHECK: Check if $object with $value exists in $table.
	private function is_exist( $object = '', $value = '', $table = '' ) {

		if( empty($table) ) {
			$table = $object;
		}

		$result = $this->db->where($object, $value)
											 ->get($table)
											 ->num_rows();

		if( $result > 0 ) {
			return true;
		} else {
			return false;
		}

	}


	// GET: Get all data under $table.
	private function get_all( $table = '' ) {

		$result = $this->db->get($table)
												 ->result_array();

		return $result;
	}

	// GET: Get data by $object with $value in $table.
	private function get_by( $object = '', $value = '', $table = '' ) {

		if( empty($table) ) {
			$table = $object;
		}

		$result = $this->db->where($object, $value)
											 ->get($table)
											 ->row();

		return $result;
	}

	//GET: Get user id by (bfp_hash|ip_address).
	private function get_uid_by( $method = '', $value = '' ) {

		switch( $method ) {
			case 'bfp_hash':
				$object = 'bfp_id';
				$table = 'bfp_hash';
				break;
			case 'ip_address':
				$object = 'ip_id';
				$table = 'ip_address';
				break;
		}

		$result = $this->db->select('user_id')
											 ->where($object, $value)
											 ->get('user_' . $table)
											 ->result_array();

		return $result;
	}

	// INSERT: Mark a new footprint (IP address or Browser Fingerprint).
	private function mark_footprint( $table = '', $input = [] ) {

		$input['last_visit'] = date('Y-m-d H:i:s');
		$input['created_on'] = date('Y-m-d H:i:s');

		$this->db->insert($table, $input);

		return $this->db->insert_id();
	}

	// INSERT: Create anonymous user with $bfp_id and $ip_id.
	private function user_create( $type = '', $footprint = []) {

		// Generate uuid.
		$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0C2f ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0x2Aff ), mt_rand( 0, 0xffD3 ), mt_rand( 0, 0xff4B )
    );

		while( $this->is_exist('uuid', $uuid, 'user') === true ) {
			$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
					mt_rand( 0, 0xffff ),
					mt_rand( 0, 0x0C2f ) | 0x4000,
					mt_rand( 0, 0x3fff ) | 0x8000,
					mt_rand( 0, 0x2Aff ), mt_rand( 0, 0xffD3 ), mt_rand( 0, 0xff4B )
			);
		}

		if( $type == 'anonymous' ) {
			$input = array(
				'uuid'		=> $uuid,
				'type'		=> 'anonymous',
				'last_visit'	=> date('Y-m-d H:i:s'),
				'created_on'	=> date('Y-m-d H:i:s'),
			);

			$this->db->insert('user', $input);

			$uid = $this->db->insert_id();

			if( ! empty($footprint['bfp_id']) ) {
				$input = array(
					'user_id'		=>	$uid,
					'bfp_id'		=>	$footprint['bfp_id'],
				);

				$this->db->insert('user_bfp_hash', $input);
			}

			if( ! empty($footprint['ip_id']) ) {
				$input = array(
					'user_id'		=>	$uid,
					'ip_id'			=>	$footprint['ip_id'],
				);

				$this->db->insert('user_ip_address', $input);
			}

			return $this->get_by('id', $uid, 'user');
		}
	}

	private function link_user_with( $uid, $object, $table ) {

		if( $table == 'ip_address' ) {
			$input = array(
				'user_id'	=>	$uid,
				'ip_id'		=>	$object,
			);

			$this->db->insert('user_' . $table, $input);

			return $this->get_by('id', $uid, 'user');

		} else if( $table == 'bfp_hash' ) {
			$input = array(
				'user_id'	=>	$uid,
				'bfp_id'		=>	$object,
			);

			$this->db->insert('user_' . $table, $input);

			return $this->get_by('id', $uid, 'user');
		}
	}


	// UPDATE: Update footprint.
	private function update_footprint( $option = '', $values = []) {

		$input = array(
			'last_visit' => date('Y-m-d H:i:s'),
		);

		if( $option == 'all' || $option == 'footprint' || $option == 'ip_address' ) {
			$this->db->where('id', $values['ip_id'])
							 ->update('ip_address', $input);
		}

		if( $option == 'all' || $option == 'footprint' || $option == 'bfp_hash' ) {
			$this->db->where('id', $values['bfp_id'])
							 ->update('bfp_hash', $input);
		}

		if( $option == 'all' || $option == 'user' ) {

			$last_visit = $this->get_by('id', $values['uid'], 'user')->last_visit;
			$curr_date = date('Y-m-d');
			preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $last_visit, $last_date);

			if( $last_date[0] !==  $curr_date ) {
				$visit_count = $this->get_by('id', $values['uid'], 'user')->visit_count;
				$input['visit_count'] = $visit_count + 1;
			}

			$this->db->where('id', $values['uid'])
							 ->update('user', $input);
		}

		if( $option == 'fb' ) {

			$last_visit = $this->get_by('fb_id', $values['fb_id'], 'user_fb')->last_visit;
			$curr_date = date('Y-m-d');
			preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $last_visit, $last_date);

			if( $last_date[0] !==  $curr_date ) {
				$visit_count = $this->get_by('fb_id', $values['fb_id'], 'user_fb')->visit_count;
				$input['visit_count'] = $visit_count + 1;
			}

			$this->db->where('fb_id', $values['fb_id'])
							 ->update('user_fb', $input);
		}

	}

	//
	private function clear_post_count( $uid = '' ) {
		$curr_date = date('Y-m-d');
		$last_post = $this->get_by('id', $uid, 'user')->last_post;

		if( $last_post !== $curr_date ) {
			$input['post_count'] = 0;
			$this->db->where('id', $uid)
							 ->update('user', $input);
		}
	}


	private function update_post_count( $uid = '' ) {

		$curr_date = date('Y-m-d');
		$last_post = $this->get_by('id', $uid, 'user')->last_post;
		$input['last_post']	= $curr_date;

		if( $last_post == $curr_date ) {
			$post_count = $this->get_by('id', $uid, 'user')->post_count;
			$input['post_count'] = $post_count + 1;
		} else {
			$input['post_count'] = 0;
		}

		$this->db->where('id', $uid)
						 ->update('user', $input);
	}



	// Delete.

		/*
		$output = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.'
		);

		$output = array(
			'status'	=> '400',
			'code'		=> 'ConditionHeadersNotSupported',
			'message'	=>	'Condition headers are not supported.'
		);

		$output = array(
			'status'	=> '404',
			'code'		=> 'ResourceNotFound',
			'message'	=>	'The specified resource does not exist.'
		);

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET");
		header("Content-Type: application/json; charset=UTF-8");
		*/
}
