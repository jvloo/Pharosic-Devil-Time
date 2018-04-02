<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct() {

		parent::__construct();
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
	}

	public function index() {

		$response = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.',
		);

		print_r( json_encode($response) );
	}

	public function posts( $method = '' ) {

		if( $method === 'GET' ) {

			header("Access-Control-Allow-Methods: GET");

			//		GET specific post.
			//		/GET/id/ ${pid}
			if( ! empty($this->input->get('pid')) ) {

				// *****TODO*****

			//		GET all post.
			//		/GET/limit/ ${limit} /offset/ ${offset}
			} else {

				$table = 'post';
				$query = 'SELECT * FROM ' . $table;

				if( ! empty($this->input->get('limit')) ) {
					$limit = $this->input->get('limit');
					$query = $query . ' LIMIT ' . $limit;
				}

				if( ! empty($this->input->get('offset')) ) {
					$offset = $this->input->get('offset');
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
			}

		} elseif( $method === 'POST' ) {

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

				unset($input);

				$input = array(
					$post_id = $this->db->insert_id(),
					$user_id = $user_id,
				);

				$this->db->insert('post_user', $input);

				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
					'body'	=> $this->db->affected_rows(),
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

	public function user( $method = '' ) {

		if( $method === 'GET' ) {

			$error = false;

			$bfp_hash = ! empty($this->input->get('bfp_hash')) ? $this->input->get('bfp_hash') : $error = true;
			$bfp_components = ! empty($this->input->get('bfp_components')) ? $this->input->get('bfp_components') : $error = true;

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
					if( count($uid_by_ip) === 1 ) {

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
							if( $uid['user_id'] === $uid_by_bfp ) {
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
				} elseif( ! $exist_ip || ! $exist_bfp ) {

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
					} elseif( ! $exist_ip && $exist_bfp ) {

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
				);

				print_r( json_encode($output) );
			}

		} else {

			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
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

		if( $type === 'anonymous' ) {
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

		if( $table === 'ip_address' ) {
			$input = array(
				'user_id'	=>	$uid,
				'ip_id'		=>	$object,
			);

			$this->db->insert('user_' . $table, $input);

			return $this->get_by('id', $uid, 'user');

		} elseif( $table === 'bfp_hash' ) {
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

		if( $option === 'all' || $option === 'footprint' || $option === 'ip_address' ) {
			$this->db->where('id', $values['ip_id'])
							 ->update('ip_address', $input);
		}

		if( $option === 'all' || $option === 'footprint' || $option === 'bfp_hash' ) {
			$this->db->where('id', $values['bfp_id'])
							 ->update('bfp_hash', $input);
		}

		if( $option === 'all' || $option === 'user' ) {

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
